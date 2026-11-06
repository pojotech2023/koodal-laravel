<?php

namespace App\Jobs;

use App\Models\ProfilePhoto;
use App\Services\Media\ImageProcessor;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Turns the stored original into the three variants every photo needs: a
 * re-encoded full size, a thumbnail, and the blurred derivative gated viewers
 * receive.
 *
 * Runs on the `media` queue rather than in the request. A 5 MB upload over a
 * patchy mobile connection is already slow; making the user wait for three
 * re-encodes on top is how uploads get abandoned halfway.
 *
 * Only the id is carried in the payload, never the model. A serialised model
 * is a snapshot of a row that a moderator may have acted on in the meantime.
 */
class ProcessProfilePhoto implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    /** Widening gaps: a transient disk or memory failure is rarely over instantly. */
    public array $backoff = [10, 60, 300];

    public function __construct(public int $photoId)
    {
        $this->onQueue(config('queue.queues.media'));
    }

    public function handle(ImageProcessor $processor): void
    {
        $photo = ProfilePhoto::find($this->photoId);

        // Deleted between upload and processing — a user who changed their mind
        // in the ten seconds the queue was behind. Nothing to do, and retrying
        // would never help.
        if (! $photo) {
            return;
        }

        $original = $photo->path;

        // Materialised locally because the disk may be S3: Intervention needs a
        // real file, and a stream copy is cheaper than pulling the same object
        // three times.
        $temporary = tempnam(sys_get_temp_dir(), 'photo_');

        try {
            file_put_contents($temporary, Storage::disk($photo->disk)->get($original));

            $variants = $processor->processStoredPhoto($temporary, $photo->user_id, $photo->disk);

            DB::transaction(function () use ($photo, $variants) {
                $photo->update($variants);

                // The first approved photo becomes the profile's primary. Set
                // here rather than at upload because a profile card pointing at
                // an unprocessed path renders a broken image.
                if ($photo->is_primary) {
                    $photo->user->profile()->update(['primary_photo_id' => $photo->id]);
                }
            });

            // Only after the derivatives are safely written. Deleting first and
            // failing second would leave the user with no photo at all.
            Storage::disk($photo->disk)->delete($original);
        } finally {
            @unlink($temporary);
        }
    }
}
