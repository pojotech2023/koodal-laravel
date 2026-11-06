<?php

namespace App\Models;

use App\Models\Concerns\IsMasterData;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Community extends Model
{
    use HasFactory, IsMasterData;

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    // --- Relations ---------------------------------------------------------

    public function religion(): BelongsTo
    {
        return $this->belongsTo(Religion::class);
    }

    public function subCommunities(): HasMany
    {
        return $this->hasMany(SubCommunity::class);
    }

    // --- Scopes ------------------------------------------------------------

    public function scopeOfReligion(Builder $query, ?int $religionId): Builder
    {
        return $religionId ? $query->where('religion_id', $religionId) : $query;
    }
}
