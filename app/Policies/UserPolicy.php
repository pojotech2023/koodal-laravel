<?php

namespace App\Policies;

use App\Models\User;
use App\Policies\Concerns\ActsAsStaff;
use Illuminate\Auth\Access\Response;

class UserPolicy
{
    use ActsAsStaff;

    public function viewAny(User $user): bool
    {
        return $this->staffCan($user, 'users.view');
    }

    public function view(User $user, User $target): bool
    {
        return $user->is($target) || $this->staffCan($user, 'users.view');
    }

    public function update(User $user, User $target): bool
    {
        return $user->is($target) || $this->staffCan($user, 'users.edit');
    }

    /**
     * Business rule before permission, so a super admin is bound by it too:
     * nobody suspends themselves, and nobody suspends another staff account
     * through the member moderation surface. Locking yourself out of the admin
     * panel with your own tooling is a support incident nobody can fix from
     * inside the application, and staff accounts are managed under
     * `staff.manage` where the audit trail is different.
     */
    public function suspend(User $user, User $target): Response
    {
        if ($user->is($target)) {
            return Response::deny('You cannot suspend your own account.');
        }

        if ($target->isStaff()) {
            return Response::deny('Staff accounts are managed from Staff, not from Members.');
        }

        return $this->staffCan($user, 'users.suspend')
            ? Response::allow()
            : Response::deny();
    }

    /**
     * Deletion is the member's own right (RequestAccountDeletion) or a staff
     * action under its own permission. The 30-day grace period lives in the
     * Action — a Policy cannot express "yes, but not until the 12th".
     */
    public function delete(User $user, User $target): Response
    {
        if ($user->is($target)) {
            return Response::allow();
        }

        if ($target->isStaff()) {
            return Response::deny('Staff accounts are managed from Staff, not from Members.');
        }

        return $this->staffCan($user, 'users.delete')
            ? Response::allow()
            : Response::deny();
    }

    public function verify(User $user): bool
    {
        return $this->staffCan($user, 'users.verify');
    }

    /**
     * A CSV of the member base is the single most damaging thing this system
     * can emit, so it is its own permission rather than riding on `users.view`.
     */
    public function export(User $user): bool
    {
        return $this->staffCan($user, 'users.export');
    }
}
