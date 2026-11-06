<?php

namespace App\Support;

use App\Models\MembershipPlan;
use Illuminate\Support\Facades\Cache;

/**
 * Which plans to offer when a paywall fires.
 *
 * The upgrade sheet is only useful if it shows plans that actually unlock the
 * thing the user just tried to do. Listing every plan — including ones that do
 * not grant the feature — is how an upgrade converts and still leaves the user
 * blocked, which is a refund request.
 */
class SuggestedPlans
{
    /** @return array<int, array<string, mixed>> */
    public static function for(string $feature): array
    {
        return Cache::remember("suggested-plans:{$feature}", 3600, function () use ($feature) {
            return MembershipPlan::query()
                ->active()
                ->where('price', '>', 0)
                ->get()
                ->filter(fn (MembershipPlan $plan) => static::grants($plan, $feature))
                ->sortBy('price')
                ->take(3)
                ->map(fn (MembershipPlan $plan) => [
                    'id' => $plan->id,
                    'slug' => $plan->slug,
                    'name' => $plan->name,
                    'price' => (float) $plan->price,
                    'currency' => $plan->currency,
                    'duration_days' => $plan->duration_days,
                    'badge_text' => $plan->badge_text,
                ])
                ->values()
                ->all();
        });
    }

    /**
     * A capability is granted when true; a quota when it is unlimited or
     * simply larger than zero.
     */
    protected static function grants(MembershipPlan $plan, string $feature): bool
    {
        $value = $plan->feature($feature);

        return match (true) {
            is_bool($value) => $value,
            is_int($value) => $value === -1 || $value > 0,
            is_string($value) => $value !== '',
            default => false,
        };
    }

    public static function flush(): void
    {
        foreach (array_keys(config('matrimony.membership.features')) as $feature) {
            Cache::forget("suggested-plans:{$feature}");
        }
    }
}
