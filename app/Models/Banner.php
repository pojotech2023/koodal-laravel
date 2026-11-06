<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Storage;

class Banner extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
        ];
    }

    // --- Accessors ---------------------------------------------------------

    public function imageUrl(bool $mobile = false): ?string
    {
        $path = ($mobile ? $this->mobile_image_path : null) ?? $this->image_path;

        return $path ? Storage::disk(config('filesystems.default'))->url($path) : null;
    }

    // --- Scopes ------------------------------------------------------------

    /** Live means active *and* inside its window — a scheduled banner is not live. */
    public function scopeLive(Builder $query): Builder
    {
        return $query->where('is_active', true)
            ->where(fn (Builder $q) => $q->whereNull('starts_at')->orWhere('starts_at', '<=', now()))
            ->where(fn (Builder $q) => $q->whereNull('ends_at')->orWhere('ends_at', '>=', now()))
            ->orderBy('sort_order');
    }

    public function scopeAtPosition(Builder $query, ?string $position): Builder
    {
        return $position ? $query->where('position', $position) : $query;
    }
}
