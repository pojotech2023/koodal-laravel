<?php

namespace App\Models;

use App\Models\Concerns\IsMasterData;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * One table for diet / smoking / drinking / body_type / complexion. They share
 * a shape, are always fetched together, and adding a sixth kind should not mean
 * a migration.
 */
class LifestyleOption extends Model
{
    use HasFactory, IsMasterData;

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    // --- Scopes ------------------------------------------------------------

    public function scopeOfType(Builder $query, ?string $type): Builder
    {
        return $type ? $query->where('type', $type) : $query;
    }
}
