<?php

namespace App\Models;

use App\Models\Concerns\BelongsToUser;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BlockedUser extends Model
{
    use BelongsToUser, HasFactory;

    // --- Relations ---------------------------------------------------------

    public function blockedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'blocked_user_id');
    }

    // --- Scopes ------------------------------------------------------------

    /**
     * Blocking is symmetric for visibility purposes: if either side blocked the
     * other, neither sees the other. Checking one direction only is the bug
     * that lets a harasser keep watching.
     */
    public function scopeBetween(Builder $query, int $a, int $b): Builder
    {
        return $query->where(fn (Builder $q) => $q
            ->where(fn (Builder $x) => $x->where('user_id', $a)->where('blocked_user_id', $b))
            ->orWhere(fn (Builder $x) => $x->where('user_id', $b)->where('blocked_user_id', $a)));
    }
}
