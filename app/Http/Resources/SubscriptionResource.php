<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * One row of subscription history. `MembershipResource` renders the *current*
 * membership with its resolved features; this one is the ledger.
 *
 * `features_snapshot` is deliberately not returned. It is a frozen copy of what
 * a plan looked like at purchase — useful to support, meaningless on a history
 * screen, and large enough to double the size of the list.
 */
class SubscriptionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'plan' => $this->whenLoaded('plan', fn () => [
                'name' => $this->plan->name,
                'slug' => $this->plan->slug,
                'price' => (float) $this->plan->price,
            ]),

            'status' => $this->status,
            'is_active' => $this->isActive(),
            'starts_at' => $this->starts_at?->toIso8601String(),
            'expires_at' => $this->expires_at?->toIso8601String(),
            'days_remaining' => $this->daysRemaining(),
            'auto_renew' => (bool) $this->auto_renew,

            'cancelled_at' => $this->cancelled_at?->toIso8601String(),
            'cancellation_reason' => $this->cancellation_reason,
        ];
    }
}
