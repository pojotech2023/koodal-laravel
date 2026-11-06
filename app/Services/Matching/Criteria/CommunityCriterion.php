<?php

namespace App\Services\Matching\Criteria;

use App\Models\UserProfile;
use App\Services\Matching\Criterion;

class CommunityCriterion implements Criterion
{
    public function score(UserProfile $viewer, UserProfile $candidate): float
    {
        $preferred = $viewer->user->preferences?->community_ids ?? [];

        if ($preferred === []) {
            // Nothing stated: same community is still a signal in this market,
            // so it scores well without being required.
            return $candidate->community_id && $candidate->community_id === $viewer->community_id ? 1.0 : 0.6;
        }

        if (in_array($candidate->community_id, $preferred, true)) {
            return 1.0;
        }

        // Same religion, different community is a near miss rather than a
        // rejection — many families treat it as acceptable.
        return $candidate->religion_id === $viewer->religion_id ? 0.3 : 0.0;
    }

    public function label(): string
    {
        return 'Same community';
    }
}
