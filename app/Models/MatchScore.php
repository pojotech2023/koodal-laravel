<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Precomputed nightly. A composite primary key and no auto-increment id, so
 * the recompute can upsert without a lookup.
 */
class MatchScore extends Model
{
    protected $table = 'match_scores';

    public $incrementing = false;

    public $timestamps = false;

    protected $primaryKey = null;

    protected function casts(): array
    {
        return [
            'breakdown' => 'array',
            'score' => 'integer',
            'computed_at' => 'datetime',
        ];
    }

    // --- Relations ---------------------------------------------------------

    public function candidate(): BelongsTo
    {
        return $this->belongsTo(User::class, 'candidate_id');
    }

    // --- Scopes ------------------------------------------------------------

    public function scopeTopFor(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId)->orderByDesc('score');
    }
}
