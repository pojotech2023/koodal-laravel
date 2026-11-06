<?php

namespace App\Actions\Admin;

use App\Actions\Photo\UploadProfilePhoto;
use App\Models\MediaUpload;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Puts a file on the public disk and records it in the media library.
 *
 * Modelled on {@see UploadProfilePhoto} — bytes to disk
 * first, row second — with three deliberate differences, all of them because
 * this file is *public and unprocessed* where a member photo is private until
 * moderated and only ever served as a derivative:
 *
 * 1. **The extension comes from the file's contents, never from its name.**
 *    `UploadProfilePhoto` uses `getClientOriginalExtension()`, which is a
 *    string the browser sent; it can afford to, because that path is only
 *    read back through ProcessProfilePhoto's re-encode. Here the stored file
 *    is served verbatim from a public URL, so a `.php` or `.svg` wearing an
 *    image MIME type would be a live file on the marketing site.
 *    `UploadedFile::extension()` guesses from the actual bytes.
 * 2. **No queue.** There is nothing to process — no resize, no thumbnail, no
 *    blur — so there is no job, and the path is usable the moment the
 *    request returns. That is what lets the upload answer with a path the
 *    caller pastes straight into `banners.image_path`.
 * 3. **It audits itself.** Every mutating admin action writes through
 *    AuditLogger (docs/06 §7), and an upload is the one admin action that
 *    puts a new, world-readable file on the internet.
 */
class StoreMediaUpload
{
    public function __construct(protected AuditLogger $audit) {}

    public function __invoke(UploadedFile $file, User $actor, string $collection = 'general'): MediaUpload
    {
        $disk = config('filesystems.default');

        // Dated directories, so the library never becomes one directory with
        // fifty thousand entries — the same reason mail stores shard by date.
        // The UUID is the filename because two campaigns both called
        // `hero.jpg` are the normal case, not the edge one.
        $path = 'cms/'.now()->format('Y/m').'/'.Str::uuid()->toString().'.'.$file->extension();

        Storage::disk($disk)->putFileAs(dirname($path), $file, basename($path));

        [$width, $height] = $this->dimensions($file);

        $upload = DB::transaction(fn () => MediaUpload::create([
            'uploaded_by' => $actor->id,
            'disk' => $disk,
            'path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType(),
            'size_bytes' => $file->getSize(),
            'width' => $width,
            'height' => $height,
            'collection' => $collection,
        ]));

        $this->audit->log(
            'media.uploaded',
            $upload,
            newValues: ['path' => $path, 'original_name' => $upload->original_name, 'collection' => $collection],
            description: 'Media uploaded to the library',
            actor: $actor,
        );

        return $upload;
    }

    /**
     * `getimagesize()` on the temporary file, not an image library: the only
     * thing wanted here is the pixel dimensions for the library listing, and
     * this runs before the request returns. A file that has passed the
     * FormRequest's `image` rule and still cannot be measured is not worth
     * failing the upload over — the columns are nullable for exactly that.
     *
     * @return array{0: ?int, 1: ?int}
     */
    protected function dimensions(UploadedFile $file): array
    {
        $size = @getimagesize($file->getRealPath());

        return $size ? [$size[0], $size[1]] : [null, null];
    }
}
