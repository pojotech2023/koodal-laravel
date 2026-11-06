<?php

namespace App\Services\Matching\Criteria;

use App\Models\UserProfile;
use App\Services\Matching\Criterion;

class OccupationCriterion implements Criterion
{
    public function score(UserProfile $viewer, UserProfile $candidate): float
    {
        $preferred = $viewer->user->preferences?->occupation_ids ?? [];

        if ($preferred === []) {
            return 1.0;
        }

        return in_array($candidate->occupation_id, $preferred, true) ? 1.0 : 0.25;
    }

    public function label(): string
    {
        return 'Profession matches';
    }
}
