<?php

namespace App\Policies;

use App\Models\Interest;
use App\Models\User;
use App\Policies\Concerns\ActsAsStaff;
use Illuminate\Auth\Access\Response;

class InterestPolicy
{
    use ActsAsStaff;

    public function view(User $user, Interest $interest): Response
    {
        return $interest->sender_id === $user->id || $interest->receiver_id === $user->id
            ? Response::allow()
            : Response::denyAsNotFound();
    }

    /**
     * Only the receiver answers. The 409 for an interest that was already
     * answered stays in AcceptInterest/DeclineInterest — "you may not" and
     * "there is nothing left to do" are different answers and a Policy can only
     * give one of them.
     */
    public function respond(User $user, Interest $interest): Response
    {
        return $interest->receiver_id === $user->id
            ? Response::allow()
            : Response::deny('This interest was not sent to you.');
    }

    /** Only the sender withdraws. */
    public function cancel(User $user, Interest $interest): Response
    {
        return $interest->sender_id === $user->id
            ? Response::allow()
            : Response::denyAsNotFound();
    }

    /**
     * Staff read interests when investigating a report — a pattern of interests
     * to fifty people in an hour is what a harassment case looks like in data.
     */
    public function viewAny(User $user): bool
    {
        return $this->staffCan($user, 'interests.view');
    }
}
