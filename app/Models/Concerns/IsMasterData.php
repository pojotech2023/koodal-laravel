<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

/**
 * Shared behaviour for the twelve lookup tables. They differ only in their
 * extra columns, so scopes, slug generation and the active ordering live here
 * once instead of twelve times.
 */
trait IsMasterData
{
    protected static function bootIsMasterData(): void
    {
        // Slugs are the stable public identifier — a client may hold a slug in
        // a saved search long after an admin renames the label.
        static::creating(function ($model) {
            $model->slug ??= Str::slug($model->name);
        });
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        return $term ? $query->where('name', 'like', $term.'%') : $query;
    }
}
