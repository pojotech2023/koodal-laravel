<?php

namespace App\Models;

use App\Models\Concerns\BelongsToUser;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class ProfilePhoto extends Model
{
    use BelongsToUser, HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'is_primary' => 'boolean',
            'moderated_at' => 'datetime',
            'size_bytes' => 'integer',
            'width' => 'integer',
            'height' => 'integer',
        ];
    }

    // --- Relations ---------------------------------------------------------

    public function moderator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'moderated_by');
    }

    // --- Accessors ---------------------------------------------------------

    public function isApproved(): bool
    {
        return $this->approval_status === 'approved';
    }

    /**
     * The blurred variant is a separate stored derivative, not a CSS filter:
     * a client-side blur still ships every real pixel to a viewer who has not
     * earned them.
     */
    public function urlFor(string $variant = 'thumbnail'): ?string
    {
        $path = match ($variant) {
            'blurred' => $this->blurred_path ?? $this->thumbnail_path ?? $this->path,
            'thumbnail' => $this->thumbnail_path ?? $this->path,
            default => $this->path,
        };

        return $path ? Storage::disk($this->disk)->url($path) : null;
    }

    /** Every stored derivative, for deletion. Missing one leaks storage cost. */
    public function allPaths(): array
    {
        return array_filter([$this->path, $this->thumbnail_path, $this->blurred_path]);
    }

    // --- Scopes ------------------------------------------------------------

    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('approval_status', 'approved');
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('approval_status', 'pending');
    }
}
