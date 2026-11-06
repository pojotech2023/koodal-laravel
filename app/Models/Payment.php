<?php

namespace App\Models;

use App\Models\Concerns\BelongsToUser;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * The financial record. Survives user deletion (nullOnDelete) because a
 * deleted account must not erase a transaction an accountant will be asked
 * about years later.
 */
class Payment extends Model
{
    use BelongsToUser, HasFactory;

    // The signature proves the payment; it is not a secret worth returning,
    // and echoing it back in an API response is a needless exposure.
    protected $hidden = ['razorpay_signature'];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'refunded_amount' => 'decimal:2',
            'notes' => 'array',
            'verified_at' => 'datetime',
        ];
    }

    // --- Relations ---------------------------------------------------------

    public function plan(): BelongsTo
    {
        return $this->belongsTo(MembershipPlan::class, 'membership_plan_id');
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    // --- Accessors ---------------------------------------------------------

    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }

    public function isRefundable(): bool
    {
        return $this->status === 'paid' && $this->refund_id === null;
    }

    public function amountInPaise(): int
    {
        return (int) round($this->amount * 100);
    }

    // --- Scopes ------------------------------------------------------------

    public function scopeStatus(Builder $query, ?string $status): Builder
    {
        return $status ? $query->where('status', $status) : $query;
    }

    public function scopePaid(Builder $query): Builder
    {
        return $query->where('status', 'paid');
    }
}
