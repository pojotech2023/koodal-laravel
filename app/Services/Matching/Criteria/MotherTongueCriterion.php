<?php

namespace App\Services\Matching\Criteria;

use App\Models\UserProfile;
use App\Services\Matching\Criterion;

class MotherTongueCriterion implements Criterion
{
    public function score(UserProfile $viewer, UserProfile $candidate): float
    {
        $preferred = $viewer->user->preferences?->mother_tongue_ids ?? [];

        if ($preferred === []) {
            return $candidate->mother_tongue_id
                && $candidate->mother_tongue_id === $viewer->mother_tongue_id ? 1.0 : 0.5;
        }

        return in_array($candidate->mother_tongue_id, $preferred, true) ? 1.0 : 0.2;
    }

    public function label(): string
    {
        return 'Speaks your language';
    }
}
