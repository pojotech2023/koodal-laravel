<?php

namespace App\Actions\Moderation;

use App\Models\ProfilePhoto;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\Notification\NotificationDispatcher;
use Illuminate\Support\Facades\DB;

/**
 * The highest-volume write in the admin surface — a moderator clears hundreds
 * of these in a shift — so the shape is built around that rather than around
 * one photo at a time.
 *
 * A rejected photo is **not** deleted. Two reasons: the member is told what was
 * wrong and can see which photo it was, and a repeat offender's history is the
 * evidence behind a later suspension. It is simply never served — every read
 * path filters on `approval_status`, and `PhotoResource` hands a gated viewer
 * the blurred derivative rather than a flag.
 */
class ModeratePhoto
{
    public function __construct(
        protected AuditLogger $audit,
        protected NotificationDispatcher $notifications,
    ) {}

    public function __invoke(ProfilePhoto $photo, string $decision, User $moderator, ?string $reason = null): ProfilePhoto
    {
        abort_unless(in_array($decision, ['approve', 'reject'], true), 422, 'Unknown moderation decision.');

        abort_unless($photo->approval_status === 'pending', 409, 'This photo has already been reviewed.');

        abort_if($decision === 'reject' && blank($reason), 422, 'A rejection reason is required.');

        $photo = DB::transaction(function () use ($photo, $decision, $moderator, $reason) {
            $photo->update([
                'approval_status' => $decision === 'approve' ? 'approved' : 'rejected',
                'rejection_reason' => $decision === 'approve' ? null : $reason,
                'moderated_by' => $moderator->id,
                'moderated_at' => now(),
            ]);

            if ($decision === 'approve') {
                $this->syncPrimary($photo);
            } else {
                $this->demoteIfPrimary($photo);
            }

            $this->audit->log(
                "photo.{$decision}d",
                $photo,
                newValues: ['approval_status' => $photo->approval_status],
                description: $reason,
                actor: $moderator,
            );

            return $photo->fresh();
        });

        $this->notify($photo, $decision, $reason);

        return $photo;
    }

    /**
     * Bulk approve or reject, one transaction per photo.
     *
     * Deliberately not a single `whereIn(...)->update(...)`: the per-photo
     * rules above — the already-reviewed guard, the primary-photo fixup, one
     * audit row each, one notification each — are exactly what a mass update
     * would skip, and a moderator clearing a hundred photos in a batch is
     * precisely when those matter most. Photos that cannot be moderated are
     * reported back rather than failing the batch, so one already-reviewed row
     * that arrived from a stale queue does not discard ninety-nine decisions.
     *
     * @param  array<int, int>  $ids
     * @return array{moderated: int, skipped: array<int, int>}
     */
    public function bulk(array $ids, string $decision, User $moderator, ?string $reason = null): array
    {
        $photos = ProfilePhoto::query()
            ->whereIn('id', $ids)
            ->where('approval_status', 'pending')
            ->get();

        $moderated = [];

        foreach ($photos as $photo) {
            $this($photo, $decision, $moderator, $reason);
            $moderated[] = $photo->id;
        }

        return [
            'moderated' => count($moderated),
            'skipped' => array_values(array_diff($ids, $moderated)),
        ];
    }

    // --- Internals ---------------------------------------------------------

    /**
     * A member whose first photo was still in the queue has `primary_photo_id`
     * pointing at a row nothing will serve, so their profile renders as a blank
     * avatar until they upload again. Approval is the moment to fix that.
     */
    protected function syncPrimary(ProfilePhoto $photo): void
    {
        $profile = $photo->user?->profile;

        if (! $profile) {
            return;
        }

        $current = $profile->primary_photo_id;

        $currentIsServable = $current !== null && ProfilePhoto::query()
            ->whereKey($current)
            ->where('approval_status', 'approved')
            ->exists();

        if (! $currentIsServable) {
            $photo->update(['is_primary' => true]);
            $profile->update(['primary_photo_id' => $photo->id]);
        }
    }

    /**
     * Rejecting the photo a profile points at leaves the profile pointing at a
     * rejected file. The next approved photo takes over; if there is none, the
     * pointer is cleared rather than left dangling.
     */
    protected function demoteIfPrimary(ProfilePhoto $photo): void
    {
        $profile = $photo->user?->profile;

        if (! $profile || $profile->primary_photo_id !== $photo->id) {
            return;
        }

        $next = ProfilePhoto::query()
            ->where('user_id', $photo->user_id)
            ->where('id', '!=', $photo->id)
            ->approved()
            ->orderBy('sort_order')
            ->first();

        $photo->update(['is_primary' => false]);
        $next?->update(['is_primary' => true]);
        $profile->update(['primary_photo_id' => $next?->id]);
    }

    protected function notify(ProfilePhoto $photo, string $decision, ?string $reason): void
    {
        if (! $user = $photo->user) {
            return;
        }

        if ($decision === 'approve') {
            $this->notifications->send(
                user: $user,
                type: 'photo_approved',
                title: 'Your photo is live',
                body: 'Your photo has been approved and is now visible on your profile.',
                data: ['photo_id' => $photo->id],
            );

            return;
        }

        $this->notifications->send(
            user: $user,
            type: 'photo_rejected',
            title: 'A photo was not approved',
            body: $reason,
            data: ['photo_id' => $photo->id],
            force: true,
        );
    }
}
