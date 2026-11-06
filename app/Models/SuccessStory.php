<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class SuccessStory extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'marriage_date' => 'date',
            'is_published' => 'boolean',
            'is_featured' => 'boolean',
        ];
    }

    // --- Relations ---------------------------------------------------------

    public function groom(): BelongsTo
    {
        return $this->belongsTo(User::class, 'groom_user_id');
    }

    public function bride(): BelongsTo
    {
        return $this->belongsTo(User::class, 'bride_user_id');
    }

    // --- Accessors ---------------------------------------------------------

    public function photoUrl(): ?string
    {
        return $this->photo_path
            ? Storage::disk(config('filesystems.default'))->url($this->photo_path)
            : null;
    }

    // --- Scopes ------------------------------------------------------------

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true)->orderBy('sort_order');
    }
}
