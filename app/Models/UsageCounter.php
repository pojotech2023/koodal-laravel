<?php

namespace App\Models;

use App\Models\Concerns\BelongsToUser;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Quota consumption. Rows are upserted with an atomic increment in FeatureGate
 * rather than read-modify-written here — two concurrent taps must not both
 * consume the last unit of a quota.
 */
class UsageCounter extends Model
{
    use BelongsToUser, HasFactory;

    protected function casts(): array
    {
        return ['used' => 'integer'];
    }

    /** Monthly quotas key on 2026-08; daily on 2026-08-13. */
    public static function periodFor(string $granularity): string
    {
        return match ($granularity) {
            'day' => now()->toDateString(),
            'month' => now()->format('Y-m'),
            default => 'lifetime',
        };
    }
}
