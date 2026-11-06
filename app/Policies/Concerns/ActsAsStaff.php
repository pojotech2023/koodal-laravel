<?php

namespace App\Policies\Concerns;

use App\Models\User;

/**
 * The staff half of every Policy in this directory.
 *
 * `Gate::before` in AuthServiceProvider lets a super admin bypass any ability
 * whose name contains a dot — that is, the permission gates. Policy abilities
 * are bare verbs (`view`, `refund`, `moderate`), so they do NOT get that
 * bypass, by design: a super admin should still be refused a second refund on
 * the same payment. But the *permission* half of a Policy check must behave
 * identically either way, or the same person is allowed through
 * `Gate::allows('payments.refund')` and refused by
 * `Gate::allows('refund', $payment)` for reasons that have nothing to do with
 * the business rule.
 *
 * So: business rules stay in the Policy where a super admin is bound by them,
 * and permission checks go through here where a super admin is not.
 */
trait ActsAsStaff
{
    protected function staffCan(User $user, string $permission): bool
    {
        return $user->isSuperAdmin() || $user->hasPermission($permission);
    }
}
