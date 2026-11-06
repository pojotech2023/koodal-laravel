<?php

namespace App\Policies;

use App\Models\ProfilePhoto;
use App\Models\User;
use App\Policies\Concerns\ActsAsStaff;
use Illuminate\Auth\Access\Response;

class ProfilePhotoPolicy
{
    use ActsAsStaff;

    /**
     * Whether the *file* may be seen is ProfileVisibility's question, and
     * PhotoResource already answers it by handing a gated viewer the blurred
     * derivative — a different file, not a flag. This is the narrower one:
     * whose row is this.
     */
    public function update(User $user, ProfilePhoto $photo): Response
    {
        return $photo->user_id === $user->id
            ? Response::allow()
            : Response::denyAsNotFound();
    }

    public function delete(User $user, ProfilePhoto $photo): Response
    {
        return $this->update($user, $photo);
    }

    public function moderate(User $user): bool
    {
        return $this->staffCan($user, 'photos.moderate');
    }
}
