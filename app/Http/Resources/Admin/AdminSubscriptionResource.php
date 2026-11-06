<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A subscription as support inspects it.
 *
 * Unlike `SubscriptionResource`, this returns `features_snapshot`. That is the
 * whole reason the admin view exists: the member's complaint is always "I paid
 * for X and I do not have X", and the only thing that settles it is the frozen
 * map of what they actually bought, side by side with what the plan grants
 * today. The member-facing resource omits it because it is meaningless on a
 * history screen and doubles the payload; here it is the payload.
 */
class AdminSubscriptionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'status' => $this->status,
            'is_active' => $this->isActive(),

            'starts_at' => $this->starts_at?->toIso8601String(),
            'expires_at' => $this->expires_at?->toIso8601String(),
            'days_remaining' => $this->daysRemaining(),
            'auto_renew' => (bool) $this->auto_renew,

            'cancelled_at' => $this->cancelled_at?->toIso8601String(),
            'cancellation_reason' => $this->cancellation_reason,

            'plan' => $this->whenLoaded('plan', fn () => [
                'id' => $this->plan->id,
                'name' => $this->plan->name,
                'slug' => $this->plan->slug,
                'price' => (float) $this->plan->price,
                'duration_days' => $this->plan->duration_days,
                // Today's map, for comparison against the snapshot below.
                'features' => $this->plan->features ?? [],
            ]),

            'user' => $this->whenLoaded('user', fn () => [
                'id' => $this->user->id,
                'profile_code' => $this->user->profile_code,
                'name' => $this->user->name,
                'email' => $this->user->email,
                'mobile' => $this->user->mobile,
                'status' => $this->user->status,
            ]),

            'payment' => $this->whenLoaded('payment', fn () => $this->payment ? [
                'id' => $this->payment->id,
                'amount' => (float) $this->payment->amount,
                'status' => $this->payment->status,
                'invoice_number' => $this->payment->invoice_number,
            ] : null),

            'features_snapshot' => $this->features_snapshot ?? [],

            // Which expiry reminders have already fired. Support is regularly
            // asked "were they warned", and the alternative to showing this is
            // reading the notifications table by hand.
            'reminders_sent' => $this->reminders_sent ?? [],

            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
