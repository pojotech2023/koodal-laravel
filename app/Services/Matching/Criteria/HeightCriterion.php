<?php

namespace App\Services\Matching\Criteria;

use App\Models\UserProfile;
use App\Services\Matching\Criterion;

class HeightCriterion implements Criterion
{
    public function score(UserProfile $viewer, UserProfile $candidate): float
    {
        $preferences = $viewer->user->preferences;
        $min = $preferences?->height_min_cm;
        $max = $preferences?->height_max_cm;

        if (($min === null && $max === null) || ! $candidate->height_cm) {
            return 1.0;
        }

        $height = $candidate->height_cm;
        $min ??= 120;
        $max ??= 220;

        if ($height >= $min && $height <= $max) {
            return 1.0;
        }

        // Tapered by centimetres rather than a cliff: 2 cm outside a stated
        // range is not a different person.
        $distance = $height < $min ? $min - $height : $height - $max;

        return match (true) {
            $distance <= 3 => 0.8,
            $distance <= 8 => 0.5,
            $distance <= 15 => 0.2,
            default => 0.0,
        };
    }

    public function label(): string
    {
        return 'Height matches your preference';
    }
}
