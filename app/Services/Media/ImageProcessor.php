<?php

namespace App\Services\Media;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;

/**
 * Every image that enters the system passes through here.
 *
 * Uploads are re-encoded rather than stored as received. That is a security
 * measure before it is a bandwidth one: a re-encode strips EXIF (which carries
 * the GPS coordinates of the photo — on a matrimonial site, someone's home
 * address) and destroys any polyglot payload hidden in a file that a MIME
 * check would happily accept.
 */
class ImageProcessor
{
    protected ImageManager $manager;

    public function __construct()
    {
        $this->manager = new ImageManager(new Driver);
    }

    /**
     * @return array{path: string, thumbnail_path: string, blurred_path: string, width: int, height: int, size_bytes: int, mime_type: string}
     */
    public function processProfilePhoto(UploadedFile $file, int $userId, string $disk): array
    {
        return $this->renderProfileVariants($file->getPathname(), $userId, $disk);
    }

    /**
     * The queued path. ProcessProfilePhoto works from the original already on
     * disk, so it has a local file rather than an UploadedFile — and a request
     * object has no business being serialised into a job payload.
     *
     * @return array{path: string, thumbnail_path: string, blurred_path: string, width: int, height: int, size_bytes: int, mime_type: string}
     */
    public function processStoredPhoto(string $localPath, int $userId, string $disk): array
    {
        return $this->renderProfileVariants($localPath, $userId, $disk);
    }

    /** The three derivatives every profile photo gets. */
    protected function renderProfileVariants(string $sourcePath, int $userId, string $disk): array
    {
        $config = config('matrimony.media.photo');
        $directory = "profiles/{$userId}";
        $name = Str::uuid()->toString();

        $image = $this->manager->read($sourcePath);

        // scaleDown, not scale: never upscale a small photo into a blurry
        // large one just to hit a target dimension.
        $image->scaleDown(width: $config['max_dimension'], height: $config['max_dimension']);

        $full = $image->toWebp($config['quality']);
        $fullPath = "{$directory}/{$name}.webp";
        Storage::disk($disk)->put($fullPath, (string) $full);

        $thumbnail = $this->manager->read($sourcePath)
            ->cover($config['thumbnail'], $config['thumbnail'])
            ->toWebp($config['quality']);
        $thumbnailPath = "{$directory}/{$name}_thumb.webp";
        Storage::disk($disk)->put($thumbnailPath, (string) $thumbnail);

        // A server-side blurred derivative, because a CSS blur still ships
        // every real pixel to a viewer who has not earned them — one devtools
        // toggle away from being no protection at all.
        $blurred = $this->manager->read($sourcePath)
            ->scaleDown(width: 400)
            ->blur($config['blur_radius'])
            ->toWebp(60);
        $blurredPath = "{$directory}/{$name}_blur.webp";
        Storage::disk($disk)->put($blurredPath, (string) $blurred);

        return [
            'path' => $fullPath,
            'thumbnail_path' => $thumbnailPath,
            'blurred_path' => $blurredPath,
            'width' => $image->width(),
            'height' => $image->height(),
            'size_bytes' => strlen((string) $full),
            'mime_type' => 'image/webp',
        ];
    }

    /**
     * Chat images live on the private disk and are served only through
     * short-lived signed URLs — a shared photo must not be a permanent public
     * link that outlives the conversation.
     */
    public function processChatImage(UploadedFile $file, int $conversationId): array
    {
        $config = config('matrimony.media.photo');
        $path = "chat/{$conversationId}/".Str::uuid()->toString().'.webp';

        $image = $this->manager->read($file->getPathname())
            ->scaleDown(width: 1280, height: 1280);

        $encoded = $image->toWebp($config['quality']);

        Storage::disk('private')->put($path, (string) $encoded);

        try {
            @chmod(Storage::disk('private')->path($path), 0644);
        } catch (\Throwable) {
            // Remote disk (S3) does not implement local path/chmod
        }

        return [
            'path' => $path,
            'width' => $image->width(),
            'height' => $image->height(),
            'size_bytes' => strlen((string) $encoded),
            'mime_type' => 'image/webp',
        ];
    }

    /** Verification documents are stored byte-for-byte: a re-encode could
     *  render a document unreadable, and legibility is the whole point. PDFs
     *  are passed through untouched for the same reason. */
    public function storeDocument(UploadedFile $file, int $userId): array
    {
        $path = "verification/{$userId}/".Str::uuid()->toString().'.'.$file->getClientOriginalExtension();

        Storage::disk('private')->putFileAs(dirname($path), $file, basename($path));

        try {
            @chmod(Storage::disk('private')->path($path), 0644);
        } catch (\Throwable) {
            // Remote disk (S3) does not implement local path/chmod
        }

        return [
            'path' => $path,
            'disk' => 'private',
            'mime_type' => $file->getMimeType(),
            'size_bytes' => $file->getSize(),
        ];
    }

    public function delete(string $disk, array $paths): void
    {
        foreach (array_filter($paths) as $path) {
            Storage::disk($disk)->delete($path);
        }
    }
}
