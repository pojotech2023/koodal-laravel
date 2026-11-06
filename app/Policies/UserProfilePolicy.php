<?php

namespace App\Policies;

use App\Models\User;
use App\Models\UserProfile;
use App\Policies\Concerns\ActsAsStaff;
use App\Services\Profile\ProfileVisibility;
use Illuminate\Auth\Access\Response;

class UserProfilePolicy
{
    use ActsAsStaff;

    public function __construct(protected ProfileVisibility $visibility) {}

    /**
     * Delegates to ProfileVisibility rather than restating its rules — five
     * inputs (the viewer's plan, the owner's privacy settings, blocks in both
     * directions, the connection state, the owner's approval status) collapse
     * into this one answer, and a second implementation of them would drift.
     *
     * `denyAsNotFound` because the first rule there is that a blocked viewer
     * gets "does not exist", never "forbidden".
     */
    public function view(User $user, UserProfile $profile): Response
    {
        if ($this->staffCan($user, 'profiles.view')) {
            return Response::allow();
        }

        return $profile->user_id === $user->id || $this->visibility->canViewProfile($user, $profile->user)
            ? Response::allow()
            : Response::denyAsNotFound();
    }

    /** Your own profile only. Staff edit through the admin surface's own audit trail. */
    public function update(User $user, UserProfile $profile): bool
    {
        return $profile->user_id === $user->id;
    }

    public function moderate(User $user): bool
    {
        return $this->staffCan($user, 'profiles.moderate');
    }
}
