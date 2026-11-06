<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

/**
 * One file in the admin media library.
 *
 * These land on the **default** (public) disk, not `private`, because that is
 * what they are for: a banner image is served to every visitor of the home
 * page. Nothing member-confidential belongs here — verification documents and
 * report evidence have their own private-disk paths and their own signed-URL
 * routes, and must never be routed through this table.
 */
class MediaUpload extends Model
{
    // --- Relations ---------------------------------------------------------

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    // --- Accessors ---------------------------------------------------------

    /**
     * Resolved through the row's own `disk`, not `config('filesystems.default')`
     * — a file uploaded before the app moved from `public` to `s3` still has
     * to render. Banner::imageUrl() and SuccessStory::photoUrl() read the
     * current default because they only hold a bare path with no disk column;
     * this one knows better and should say so.
     */
    public function url(): string
    {
        return Storage::disk($this->disk)->url($this->path);
    }

    public function humanSize(): string
    {
        $kb = $this->size_bytes / 1024;

        return $kb >= 1024
            ? number_format($kb / 1024, 1).' MB'
            : number_format($kb, 0).' KB';
    }

    public function dimensions(): ?string
    {
        return $this->width && $this->height ? "{$this->width}×{$this->height}" : null;
    }

    // --- Scopes ------------------------------------------------------------

    public function scopeInCollection(Builder $query, ?string $collection): Builder
    {
        return $collection ? $query->where('collection', $collection) : $query;
    }
}
