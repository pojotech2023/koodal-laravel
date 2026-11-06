<?php

namespace App\Policies;

use App\Models\ContactEnquiry;
use App\Models\User;
use App\Policies\Concerns\ActsAsStaff;
use Illuminate\Auth\Access\Response;

/**
 * The only Policy in this directory with no member-facing side.
 *
 * An enquiry has no owner among the members — the sender is usually not
 * registered, which is why the row carries a name and an email of its own
 * rather than a `user_id`. So every ability here is a staff ability, and the
 * per-record rule is about *which* staff member.
 */
class ContactEnquiryPolicy
{
    use ActsAsStaff;

    public function viewAny(User $user): bool
    {
        return $this->staffCan($user, 'enquiries.view');
    }

    public function view(User $user, ContactEnquiry $enquiry): bool
    {
        return $this->staffCan($user, 'enquiries.view');
    }

    /**
     * Whoever holds the enquiry answers it.
     *
     * The rule is not "you claimed it" but "nobody else did": an unclaimed
     * enquiry is fair game, and one already claimed belongs to the person who
     * claimed it until they hand it back. Without this, a shared inbox produces
     * two different replies to the same person, signed by two people, neither
     * of whom knows the other wrote.
     *
     * A super admin is bound by this too — `ActsAsStaff` explains why the
     * permission half bypasses and the business half does not. Reassignment is
     * a `claim` by the new owner after the current one releases it, which is one
     * request, not a second code path.
     */
    public function respond(User $user, ContactEnquiry $enquiry): Response
    {
        if (! $this->staffCan($user, 'enquiries.respond')) {
            return Response::deny('You may not answer contact enquiries.');
        }

        return $enquiry->handled_by === null || $enquiry->handled_by === $user->id
            ? Response::allow()
            : Response::deny('Another staff member is handling this enquiry.');
    }
}
