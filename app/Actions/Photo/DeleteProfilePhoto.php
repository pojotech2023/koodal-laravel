<?php

namespace App\Actions\Photo;

use App\Models\ProfilePhoto;
use App\Models\User;
use App\Services\Media\ImageProcessor;
use Illuminate\Support\Facades\DB;

class DeleteProfilePhoto
{
    public function __construct(protected ImageProcessor $images) {}

    public function __invoke(User $user, ProfilePhoto $photo): void
    {
        abort_unless($photo->user_id === $user->id, 403);

        DB::transaction(function () use ($user, $photo) {
            $wasPrimary = $photo->is_primary;
            $paths = $photo->allPaths();
            $disk = $photo->disk;

            $photo->delete();

            // Promote the next photo rather than leaving the profile with no
            // primary — a profile that still has photos must still show one.
            if ($wasPrimary) {
                $next = $user->photos()->approved()->orderBy('sort_order')->first();

                $next?->update(['is_primary' => true]);
                $user->profile()->update(['primary_photo_id' => $next?->id]);
            }

            // Files are removed after the transaction commits: a rolled-back
            // delete that has already destroyed the bytes is unrecoverable.
            DB::afterCommit(fn () => $this->images->delete($disk, $paths));
        });
    }
}
