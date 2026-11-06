<?php

namespace App\Actions\Profile;

use App\Events\ProfileViewed;
use App\Models\ProfileView;
use App\Models\User;
use App\Services\Membership\FeatureGate;
use Illuminate\Support\Facades\DB;

/**
 * Records that one member opened another's profile, and meters it.
 *
 * The rule that makes this feel fair: a repeat view of the same person on the
 * same day is free. It does not cost a unit of the daily quota, it does not
 * increment the owner's view counter, and it does not notify them again. A user
 * who taps back and re-opens a profile must not burn their allowance on it,
 * and an owner must not see "12 views" from one indecisive visitor.
 *
 * The row itself IS written every time. Repeat views are signal — an
 * enumeration pattern has to be visible in the raw data even though the
 * presentation layer dedupes by (viewer, day).
 */
class RecordProfileView
{
    public function __construct(protected FeatureGate $features) {}

    public function __invoke(User $viewer, User $owner, string $source = 'direct'): ?ProfileView
    {
        // Viewing your own profile is not a view. It would also let a user
        // exhaust their own quota browsing themselves.
        if ($viewer->is($owner)) {
            return null;
        }

        $repeat = $this->viewedToday($viewer, $owner);

        // Only a first look costs quota. Checking before the write means a
        // failed insert never charges the user.
        if (! $repeat) {
            $this->features->assertQuota($viewer, 'profile_views_per_day');
        }

        $view = DB::transaction(function () use ($viewer, $owner, $source, $repeat) {
            $view = ProfileView::create([
                'viewer_id' => $viewer->id,
                'viewed_id' => $owner->id,
                'source' => $source,
                // Hashed, never stored raw: the audit value is "were these
                // views from one machine", which a hash answers just as well.
                'ip_hash' => hash('sha256', (string) request()->ip()),
                'viewed_at' => now(),
            ]);

            if (! $repeat) {
                $owner->profile()->increment('profile_views_count');
            }

            return $view;
        });

        if (! $repeat) {
            $this->features->consume($viewer, 'profile_views_per_day');

            // Digested, not sent immediately — see the notifications config.
            event(new ProfileViewed($view));

            try {
                app(\App\Services\FirebaseNotificationService::class)->sendToUser(
                    $owner->id,
                    'Someone viewed your profile',
                    "{$viewer->name} viewed your profile today.",
                    [
                        'type' => 'profile_viewed',
                        'reference_id' => (string) $viewer->id,
                        'viewer_id' => (string) $viewer->id,
                        'profile_code' => (string) $viewer->profile_code,
                    ],
                    'profile_viewed',
                    true,
                    // ProfileViewed's listener already wrote the in-app row (a
                    // digest type, so it sends no push of its own).
                    false
                );
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('Profile view push notification failed: '.$e->getMessage());
            }
        }

        return $view;
    }

    protected function viewedToday(User $viewer, User $owner): bool
    {
        return ProfileView::query()
            ->where('viewer_id', $viewer->id)
            ->where('viewed_id', $owner->id)
            ->where('viewed_at', '>=', now()->startOfDay())
            ->exists();
    }
}
