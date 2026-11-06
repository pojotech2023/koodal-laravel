<?php

namespace App\Actions\Profile;

use App\Models\User;
use App\Models\UserProfile;
use App\Services\Profile\ProfileCompletion;
use Illuminate\Support\Facades\DB;

/**
 * Applies one validated section and recomputes completion.
 *
 * Re-moderation is the interesting rule: editing the sections a moderator
 * actually reads (free text, photos-adjacent fields) sends an approved profile
 * back to `pending`. Without it, a profile is approved once and then edited
 * into whatever the user likes — which is how contact details end up in the
 * "about me" box, bypassing the paywall entirely.
 */
class UpdateProfileSection
{
    /** Sections whose contents a moderator judged. Editing these re-queues. */
    protected const MODERATED_SECTIONS = ['about', 'basic', 'religious'];

    public function __construct(protected ProfileCompletion $completion) {}

    public function __invoke(User $user, string $section, array $data): UserProfile
    {
        return DB::transaction(function () use ($user, $section, $data) {
            $profile = $user->profile ?? $user->profile()->create([]);

            $profile->fill($data);

            if ($this->requiresReapproval($profile, $section)) {
                $profile->approval_status = 'pending';
                $profile->rejection_reason = null;
            }

            $profile->save();

            $this->completion->recalculate($user->refresh());

            return $profile->refresh();
        });
    }

    protected function requiresReapproval(UserProfile $profile, string $section): bool
    {
        if (! config('matrimony.profile.approval_required')) {
            return false;
        }

        return $profile->approval_status === 'approved'
            && in_array($section, static::MODERATED_SECTIONS, true)
            && $profile->isDirty(['about_me', 'expectations', 'caste', 'gothra']);
    }
}
