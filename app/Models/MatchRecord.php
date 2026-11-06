<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A mutual connection. Named MatchRecord rather than Match because `match` is
 * a reserved word in PHP 8.
 *
 * The pair is stored canonically (user_one_id < user_two_id), which is what
 * makes the unique index meaningful in both directions — without it, A→B and
 * B→A are two rows and "are these two matched" becomes an OR scan.
 */
class MatchRecord extends Model
{
    use HasFactory;

    protected $table = 'matches';

    protected function casts(): array
    {
        return ['matched_at' => 'datetime'];
    }

    // --- Relations ---------------------------------------------------------

    public function userOne(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_one_id');
    }

    public function userTwo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_two_id');
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    // --- Accessors ---------------------------------------------------------

    /** Orders any two ids canonically. The one place this rule is expressed. */
    public static function pair(int $a, int $b): array
    {
        return $a < $b ? [$a, $b] : [$b, $a];
    }

    public function otherUserId(int $userId): int
    {
        return $this->user_one_id === $userId ? $this->user_two_id : $this->user_one_id;
    }

    // --- Scopes ------------------------------------------------------------

    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where(fn (Builder $q) => $q
            ->where('user_one_id', $userId)
            ->orWhere('user_two_id', $userId));
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }
}
