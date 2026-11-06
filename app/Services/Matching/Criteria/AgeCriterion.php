<?php

namespace App\Services\Matching\Criteria;

use App\Models\UserProfile;
use App\Services\Matching\Criterion;

/**
 * Range criterion with tapering rather than a cliff: someone one year outside
 * a stated range is a far better recommendation than someone ten years
 * outside, and a hard 0.0 at the boundary makes the ranking feel arbitrary.
 */
class AgeCriterion implements Criterion
{
    public function score(UserProfile $viewer, UserProfile $candidate): float
    {
        $preferences = $viewer->user->preferences;
        $age = $candidate->user->age();

        $min = $preferences?->age_min;
        $max = $preferences?->age_max;

        if ($min === null && $max === null) {
            return 1.0;
        }

        $min ??= 18;
        $max ??= 75;

        if ($age >= $min && $age <= $max) {
            return 1.0;
        }

        $distance = $age < $min ? $min - $age : $age - $max;
        $span = max(1, $max - $min);
        $relative = $distance / $span;

        return match (true) {
            $relative <= 0.10 => 0.7,
            $relative <= 0.25 => 0.4,
            default => 0.0,
        };
    }

    public function label(): string
    {
        return 'Age matches your preference';
    }
}
