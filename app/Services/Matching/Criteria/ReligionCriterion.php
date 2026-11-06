<?php

namespace App\Services\Matching\Criteria;

use App\Models\UserProfile;
use App\Services\Matching\Criterion;

class ReligionCriterion implements Criterion
{
    public function score(UserProfile $viewer, UserProfile $candidate): float
    {
        $preferred = $viewer->user->preferences?->religion_ids ?? [];

        // No stated preference cannot penalise a candidate — see MatchScorer.
        if ($preferred === []) {
            return 1.0;
        }

        return in_array($candidate->religion_id, $preferred, true) ? 1.0 : 0.0;
    }

    public function label(): string
    {
        return 'Same religion';
    }
}
