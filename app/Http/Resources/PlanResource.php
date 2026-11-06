<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A plan as the pricing page renders it.
 *
 * `features` is expanded from the raw stored map into labelled rows using the
 * schema in `config('matrimony.membership.features')`, so the comparison table
 * is built from one response and a new feature appears in every client without
 * a release. -1 always means unlimited, and is rendered as such here rather
 * than in three separate clients.
 */
class PlanResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'name' => $this->name,
            'description' => $this->description,

            'price' => (float) $this->price,
            'currency' => $this->currency,
            'duration_days' => $this->duration_days,
            'is_free' => $this->isFree(),
            'is_featured' => (bool) $this->is_featured,
            'badge_text' => $this->badge_text,

            // Present only when the caller counted it, which is the admin plan
            // list and nowhere else. `whenCounted` is what keeps it off the
            // public pricing page — how many people bought a plan is not a
            // number to publish next to its price.
            'subscribers' => $this->whenCounted('subscriptions'),

            // Delegates to MembershipPlan::featureRows() — shared with
            // Web\MembershipController's plans.blade.php, see that method's
            // docblock.
            'features' => $this->resource->featureRows(),
            // The raw map as well: the client gates its own UI affordances off
            // this, and matching on a label would break the moment copy changes.
            'feature_map' => $this->features ?? [],
        ];
    }
}
