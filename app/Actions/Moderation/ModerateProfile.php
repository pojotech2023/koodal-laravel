<?php

namespace App\Actions\Moderation;

use App\Models\User;
use App\Models\UserProfile;
use App\Services\AuditLogger;
use App\Services\Notification\NotificationDispatcher;
use Illuminate\Support\Facades\DB;

/**
 * The profile approval queue's one write.
 *
 * Approval is what makes a profile visible: `SearchService` and
 * `ProfileVisibility` both filter on `approval_status`, so until this runs the
 * member exists and can browse but nobody can find them. That is the intended
 * shape — a matrimonial platform that lists profiles nobody has looked at is
 * one bad actor away from a press story — but it means this queue is the
 * critical path for every new signup, and a rejection with no usable reason is
 * a member who edits nothing and re-submits.
 *
 * Hence: a rejection reason is mandatory and comes from the configured list.
 * Free text would leak inconsistent tone into the notification a member reads,
 * and the same complaint would be worded four ways by four moderators.
 */
class ModerateProfile
{
    public function __construct(
        protected AuditLogger $audit,
        protected NotificationDispatcher $notifications,
    ) {}

    public function __invoke(UserProfile $profile, string $decision, User $moderator, ?string $reason = null): UserProfile
    {
        abort_unless(in_array($decision, ['approve', 'reject'], true), 422, 'Unknown moderation decision.');

        // Re-deciding overwrites who decided and when, which is the whole
        // evidentiary value of the row. A member whose profile was rejected
        // edits and re-submits, and that path — `UpdateProfileSection` —
        // is what puts it back in the queue.
        abort_unless(
            $profile->isPendingApproval(),
            409,
            'This profile has already been reviewed.',
        );

        abort_if($decision === 'reject' && blank($reason), 422, 'A rejection reason is required.');

        $profile = DB::transaction(function () use ($profile, $decision, $moderator, $reason) {
            $profile->update([
                'approval_status' => $decision === 'approve' ? 'approved' : 'rejected',
                'rejection_reason' => $decision === 'approve' ? null : $reason,
            ]);

            // The account itself moves to `active` on first approval. A member
            // left `pending` after their profile was approved is one who can
            // be found in search and cannot log in.
            if ($decision === 'approve' && $profile->user?->status === 'pending') {
                $profile->user->update(['status' => 'active']);
            }

            // Also approve any pending jadhagam for this user when their profile is approved
            if ($decision === 'approve' && $profile->user) {
                $profile->user->jadhagams()->where('approval_status', 'pending')->update([
                    'approval_status' => 'approved',
                    'moderated_by' => $moderator->id,
                    'moderated_at' => now(),
                ]);
            }

            $this->audit->log(
                "profile.{$decision}d",
                $profile,
                newValues: ['approval_status' => $profile->approval_status],
                description: $reason,
                actor: $moderator,
            );

            return $profile->fresh();
        });

        try {
            $this->notify($profile, $decision, $reason);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Profile notification failed: '.$e->getMessage(), [
                'profile_id' => $profile->id,
            ]);
        }

        return $profile;
    }

    protected function notify(UserProfile $profile, string $decision, ?string $reason): void
    {
        if (! $user = $profile->user) {
            return;
        }

        if ($decision === 'approve') {
            $this->notifications->send(
                user: $user,
                type: 'profile_approved',
                title: 'Your profile is live',
                body: 'Your profile has been approved and is now visible to other members.',
            );

            // The dispatcher above sends the push; also tell matching members.
            try {
                \App\Jobs\NotifyUsersOfNewProfileJob::dispatch($user->id);
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('New profile notification dispatch skipped: '.$e->getMessage());
            }

            return;
        }

        // The reason is the body, not a footnote. "Rejected" on its own
        // produces a member who re-submits the identical profile.
        $this->notifications->send(
            user: $user,
            type: 'profile_rejected',
            title: 'Your profile needs changes',
            body: $reason,
            force: true,
        );
    }
}
