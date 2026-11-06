<?php

namespace App\Actions\Photo;

use App\Models\ProfilePhoto;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Exactly one primary photo per user, enforced in a transaction rather than by
 * a partial unique index — MySQL has no portable `UNIQUE WHERE is_primary = 1`,
 * and a full unique on (user_id, is_primary) would allow only one *non*-primary
 * photo, which is the opposite of what is wanted.
 */
class SetPrimaryPhoto
{
    public function __invoke(User $user, ProfilePhoto $photo): ProfilePhoto
    {
        abort_unless($photo->user_id === $user->id, 403);

        // Only an approved photo may represent the profile: a pending one would
        // put unmoderated content on every search card.
        abort_unless($photo->isApproved(), 422, 'This photo is still being reviewed.');

        return DB::transaction(function () use ($user, $photo) {
            $user->photos()->where('id', '!=', $photo->id)->update(['is_primary' => false]);

            $photo->update(['is_primary' => true]);

            $user->profile()->update(['primary_photo_id' => $photo->id]);

            return $photo->refresh();
        });
    }
}
