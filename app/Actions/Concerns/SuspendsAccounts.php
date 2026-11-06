<?php

namespace App\Actions\Concerns;

use App\Models\User;

/**
 * The mechanics of taking an account offline, and putting it back.
 *
 * Extracted because two paths reach it — a moderator resolving a report, and
 * an admin acting directly on a member from the Users screen — and the three
 * statements below have to stay identical between them. They are not obvious
 * individually, and dropping any one of them produces a suspension that does
 * not suspend:
 *
 *   • the status change is what the login path checks;
 *   • the token revoke is what stops the *current* session, because Sanctum
 *     checks the token and not the account status on every request but the
 *     login — without it a suspended member keeps using the app until their
 *     token expires;
 *   • dropping `appear_in_search` is what removes them from other members'
 *     results immediately rather than at some later reindex. A suspended
 *     profile that still appears in search is the original complaint,
 *     unresolved.
 *
 * Callers are responsible for the transaction, the audit row and the
 * notification: those differ per path and are not shared here.
 */
trait SuspendsAccounts
{
    protected function applySuspension(User $user, ?string $reason): void
    {
        $user->update([
            'status' => 'suspended',
            'suspension_reason' => $reason,
        ]);

        $user->tokens()->delete();

        $user->privacySettings()->update(['appear_in_search' => false]);
    }

    /**
     * Reinstatement is not the exact inverse: `appear_in_search` is restored
     * because the suspension is what removed it, but nothing else that a
     * suspension cascaded into is undone. Tokens stay revoked — the member
     * signs in again, which is the point at which we know it is them.
     */
    protected function liftSuspension(User $user): void
    {
        $user->update([
            'status' => 'active',
            'suspension_reason' => null,
        ]);

        $user->privacySettings()->update(['appear_in_search' => true]);
    }
}
