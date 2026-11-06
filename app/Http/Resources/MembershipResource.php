<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * The subscription summary every client renders in its header.
 *
 * `features` is the resolved map — the snapshot for a paid subscription, the
 * free plan otherwise — so a client never has to work out which one applies.
 */
class MembershipResource extends JsonResource
{
    public function __construct($resource, protected array $features = [], protected array $usage = [])
    {
        parent::__construct($resource);
    }

    public function toArray(Request $request): array
    {
        return [
            'plan' => $this->resource?->plan?->name ?? 'Free',
            'plan_slug' => $this->resource?->plan?->slug ?? 'free',
            'is_premium' => (bool) ($this->resource?->plan?->price > 0),
            'status' => $this->resource?->status ?? 'active',
            'starts_at' => $this->resource?->starts_at?->toIso8601String(),
            'expires_at' => $this->resource?->expires_at?->toIso8601String(),
            'days_remaining' => $this->resource?->daysRemaining(),
            'auto_renew' => (bool) ($this->resource?->auto_renew ?? false),
            'features' => $this->features,
            'usage' => $this->usage,
        ];
    }
}
