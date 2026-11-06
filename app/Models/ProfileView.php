<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Deliberately not unique on (viewer, viewed): repeat views are signal, and an
 * enumeration pattern has to be visible in the raw data. Presentation dedupes
 * by (viewer, day); a scheduled job prunes rows past the retention window.
 */
class ProfileView extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return ['viewed_at' => 'datetime'];
    }

    // --- Relations ---------------------------------------------------------

    public function viewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'viewer_id');
    }

    public function viewed(): BelongsTo
    {
        return $this->belongsTo(User::class, 'viewed_id');
    }

    // --- Scopes ------------------------------------------------------------

    public function scopeReceivedBy(Builder $query, int $userId): Builder
    {
        return $query->where('viewed_id', $userId);
    }

    public function scopeSince(Builder $query, \DateTimeInterface $since): Builder
    {
        return $query->where('viewed_at', '>=', $since);
    }
}
