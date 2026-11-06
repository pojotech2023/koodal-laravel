<?php

namespace App\Policies;

use App\Models\MembershipPlan;
use App\Models\User;
use App\Policies\Concerns\ActsAsStaff;
use Illuminate\Auth\Access\Response;

class MembershipPlanPolicy
{
    use ActsAsStaff;

    /** The price list is public. There is nothing to gate. */
    public function viewAny(?User $user): bool
    {
        return true;
    }

    public function view(?User $user, MembershipPlan $plan): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $this->staffCan($user, 'memberships.manage');
    }

    public function update(User $user, MembershipPlan $plan): bool
    {
        return $this->staffCan($user, 'memberships.manage');
    }

    /**
     * A plan somebody bought is never deleted, whatever the permission says.
     * Subscriptions reference it for their history, invoices name it, and
     * `features_snapshot` exists precisely so an edit cannot reach backwards —
     * deleting the row reaches backwards anyway. Deactivating is the operation
     * that was actually wanted, and it is an update.
     */
    public function delete(User $user, MembershipPlan $plan): Response
    {
        if ($plan->is_default) {
            return Response::deny('The default plan cannot be deleted — every free member resolves their features through it.');
        }

        if ($plan->subscriptions()->exists()) {
            return Response::deny('This plan has subscribers. Deactivate it instead so existing memberships keep working.');
        }

        return $this->staffCan($user, 'memberships.manage')
            ? Response::allow()
            : Response::deny();
    }
}
