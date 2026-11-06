<?php

namespace App\Services\Matching\Criteria;

use App\Models\UserProfile;
use App\Services\Matching\Criterion;

class LocationCriterion implements Criterion
{
    public function score(UserProfile $viewer, UserProfile $candidate): float
    {
        $preferred = $viewer->user->preferences?->city_ids ?? [];

        if ($preferred !== []) {
            return in_array($candidate->city_id, $preferred, true) ? 1.0 : 0.2;
        }

        // Fall back to proximity to the viewer when nothing is stated.
        return match (true) {
            $candidate->city_id && $candidate->city_id === $viewer->city_id => 1.0,
            $candidate->state_id && $candidate->state_id === $viewer->state_id => 0.7,
            $candidate->country_id && $candidate->country_id === $viewer->country_id => 0.4,
            default => 0.1,
        };
    }

    public function label(): string
    {
        return 'Same city';
    }
}
