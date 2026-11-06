<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Interest extends Model
{
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return ['responded_at' => 'datetime'];
    }

    // --- Relations ---------------------------------------------------------

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'receiver_id');
    }

    // --- Accessors ---------------------------------------------------------

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * A declined interest may be re-sent after a cooldown. Without one, a
     * determined sender can re-send the moment they are declined, which is
     * harassment with extra steps.
     */
    public function canResend(): bool
    {
        return $this->status === 'declined'
            && $this->responded_at?->lt(now()->subDays(config('matrimony.interests.resend_cooldown_days')));
    }

    // --- Scopes ------------------------------------------------------------

    public function scopeStatus(Builder $query, ?string $status): Builder
    {
        return $status ? $query->where('status', $status) : $query;
    }

    public function scopeBetween(Builder $query, int $a, int $b): Builder
    {
        return $query->where(fn (Builder $q) => $q
            ->where(fn (Builder $x) => $x->where('sender_id', $a)->where('receiver_id', $b))
            ->orWhere(fn (Builder $x) => $x->where('sender_id', $b)->where('receiver_id', $a)));
    }
}
