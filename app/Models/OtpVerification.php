<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * An OTP is a credential, so only its hash is stored and a row is consumed
 * exactly once. Attempts are counted on the row rather than in the cache —
 * a cache flush must not hand an attacker a fresh set of guesses.
 */
class OtpVerification extends Model
{
    use HasFactory;

    protected $hidden = ['code_hash'];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'consumed_at' => 'datetime',
            'attempts' => 'integer',
        ];
    }

    // --- Accessors ---------------------------------------------------------

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    public function isConsumed(): bool
    {
        return $this->consumed_at !== null;
    }

    public function isUsable(): bool
    {
        return ! $this->isExpired()
            && ! $this->isConsumed()
            && $this->attempts < config('matrimony.otp.max_attempts', 5);
    }

    // --- Scopes ------------------------------------------------------------

    public function scopeUsable(Builder $query): Builder
    {
        return $query->whereNull('consumed_at')->where('expires_at', '>', now());
    }

    public function scopeFor(Builder $query, string $identifier, string $purpose): Builder
    {
        return $query->where('identifier', $identifier)->where('purpose', $purpose);
    }
}
