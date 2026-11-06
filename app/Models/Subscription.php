<?php

namespace App\Models;

use App\Models\Concerns\BelongsToUser;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Subscription extends Model
{
    use BelongsToUser, HasFactory;

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'expires_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'auto_renew' => 'boolean',
            'features_snapshot' => 'array',
            'reminders_sent' => 'array',
        ];
    }

    // --- Relations ---------------------------------------------------------

    public function plan(): BelongsTo
    {
        return $this->belongsTo(MembershipPlan::class, 'membership_plan_id');
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    // --- Accessors ---------------------------------------------------------

    public function isActive(): bool
    {
        return $this->status === 'active'
            && ($this->expires_at === null || $this->expires_at->isFuture());
    }

    public function daysRemaining(): ?int
    {
        return $this->expires_at ? max(0, (int) now()->diffInDays($this->expires_at, false)) : null;
    }

    /**
     * Reads the snapshot, never the live plan. An admin repricing Gold must not
     * silently take away what people already bought.
     */
    public function feature(string $key): mixed
    {
        return $this->features_snapshot[$key] ?? null;
    }

    public function hasSentReminder(int $days): bool
    {
        return in_array($days, (array) ($this->reminders_sent ?? []), true);
    }

    // --- Scopes ------------------------------------------------------------

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active')
            ->where(fn (Builder $q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()));
    }

    public function scopeExpiring(Builder $query, int $inDays): Builder
    {
        return $query->where('status', 'active')
            ->whereBetween('expires_at', [now(), now()->addDays($inDays)]);
    }
}
