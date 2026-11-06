<?php

namespace App\Actions\Profile;

use App\Models\ContactView;
use App\Models\User;
use App\Services\Membership\FeatureGate;
use App\Services\Profile\ProfileVisibility;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

/**
 * Hands over a mobile number and an email address — the moment the product
 * actually gets paid for, so all three gates are checked server-side.
 *
 * Metering is per target per period, not per view: opening the same person's
 * number twice this month costs one unit. Anything else produces a support
 * ticket that is impossible to argue with.
 *
 * The unique index on (viewer, viewed, period) is the real guard. A
 * check-then-insert races two taps on a slow connection into two charges.
 */
class RevealContact
{
    public function __construct(
        protected FeatureGate $features,
        protected ProfileVisibility $visibility,
    ) {}

    /**
     * @return array{mobile: string|null, email: string|null, views_remaining: int|null}
     */
    public function __invoke(User $viewer, User $owner): array
    {
        // Never confirm that a profile hiding from this viewer exists.
        if (! $this->visibility->canViewProfile($viewer, $owner)) {
            throw new ModelNotFoundException('Profile not found.');
        }

        $isPaid = $viewer->hasPaidPlan();
        $isApproved = false;

        if ($viewer->id !== $owner->id) {
            $approval = \App\Models\ContactApproval::where('requester_id', $viewer->id)
                ->where('owner_id', $owner->id)
                ->first();
            $isApproved = ($approval && $approval->status === 'approved');

            // Free users MUST have owner approval before revealing contact
            if (! $isPaid && ! $isApproved) {
                abort(response()->json([
                    'success' => false,
                    'message' => 'Contact details can only be viewed after the owner approves your request.',
                    'approval_required' => true,
                    'approval_status' => $approval?->status ?? 'none',
                ], 403));
            }
        }

        // Paid users consume monthly plan quota (no approval needed)
        if ($isPaid) {
            $this->features->assertAllowed($viewer, 'can_view_contact');
            $period = now()->format('Y-m');
            $alreadyPaid = $this->hasPaidFor($viewer, $owner, $period);

            if (! $alreadyPaid) {
                $this->features->assertQuota($viewer, 'contact_views_per_month');
                $this->record($viewer, $owner, $period);
            }
        }

        return [
            'mobile' => $owner->mobile,
            'email' => $owner->email,
            'views_remaining' => $isPaid ? $this->remaining($viewer) : null,
        ];
    }

    protected function record(User $viewer, User $owner, string $period): void
    {
        try {
            DB::transaction(function () use ($viewer, $owner, $period) {
                ContactView::create([
                    'viewer_id' => $viewer->id,
                    'viewed_id' => $owner->id,
                    'period' => $period,
                ]);
            });
        } catch (QueryException $e) {
            // 1062 = duplicate key. Two taps raced; the first one paid, and
            // this one is free rather than an error.
            if ($e->errorInfo[1] !== 1062) {
                throw $e;
            }

            return;
        }

        $this->features->consume($viewer, 'contact_views_per_month');
    }

    protected function hasPaidFor(User $viewer, User $owner, string $period): bool
    {
        return ContactView::query()
            ->where('viewer_id', $viewer->id)
            ->where('viewed_id', $owner->id)
            ->where('period', $period)
            ->exists();
    }

    /** Null when the plan is unlimited — a client renders that as no counter. */
    protected function remaining(User $viewer): ?int
    {
        $limit = $this->features->limit($viewer, 'contact_views_per_month');

        return $limit === -1 ? null : $this->features->remaining($viewer, 'contact_views_per_month');
    }
}
