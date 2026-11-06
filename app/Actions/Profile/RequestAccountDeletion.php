<?php

namespace App\Actions\Profile;

use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\DB;

/**
 * Soft-deletes now; PruneStaleRecords purges the storage and the rows after
 * the retention window.
 *
 * The delay is not indecision. It is the window in which a person who deleted
 * in anger, or whose account was deleted by someone else with their phone, can
 * be restored — and the window support needs to answer a fraud report about an
 * account that has just vanished.
 */
class RequestAccountDeletion
{
    public function __construct(protected AuditLogger $audit) {}

    /**
     * @param  User|null  $actor  the staff member deleting someone else's
     *                            account, when that is what this is. The trail
     *                            has to name whoever pressed the button, and
     *                            an admin deletion recorded as though the
     *                            member requested it is the one entry nobody
     *                            can challenge later.
     */
    public function __invoke(User $user, ?string $reason = null, ?User $actor = null): void
    {
        DB::transaction(function () use ($user, $reason, $actor) {
            $this->audit->log(
                $actor && ! $actor->is($user) ? 'account.deleted_by_staff' : 'account.deletion_requested',
                $user,
                description: $reason,
                actor: $actor ?? $user,
            );

            $user->update([
                'status' => 'deleted',
                'suspension_reason' => $reason,
            ]);

            $user->privacySettings()->update(['appear_in_search' => false]);
            $user->sentInterests()->where('status', 'pending')->update(['status' => 'cancelled']);
            $user->tokens()->delete();
            $user->deviceTokens()->delete();
            $user->fcmTokens()->delete();

            $user->profile?->delete();
            $user->delete();
        });
    }
}
