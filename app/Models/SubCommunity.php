<?php

namespace App\Models;

use App\Models\Concerns\IsMasterData;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubCommunity extends Model
{
    use HasFactory, IsMasterData;

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    // --- Relations ---------------------------------------------------------

    public function community(): BelongsTo
    {
        return $this->belongsTo(Community::class);
    }

    // --- Scopes ------------------------------------------------------------

    public function scopeOfCommunity(Builder $query, ?int $communityId): Builder
    {
        return $communityId ? $query->where('community_id', $communityId) : $query;
    }
}
