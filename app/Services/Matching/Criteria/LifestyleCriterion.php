<?php

namespace App\Services\Matching\Criteria;

use App\Models\UserProfile;
use App\Services\Matching\Criterion;

/**
 * Diet, smoking and drinking averaged into one dimension.
 *
 * They are treated together because they are rarely decisive alone but jointly
 * describe a compatible household — and because giving each its own weight
 * would let lifestyle quietly outweigh community, which is not how this market
 * chooses.
 */
class LifestyleCriterion implements Criterion
{
    protected const FACETS = ['diet', 'smoking', 'drinking'];

    public function score(UserProfile $viewer, UserProfile $candidate): float
    {
        $preferences = $viewer->user->preferences;

        if (! $preferences) {
            return 1.0;
        }

        $scores = [];

        foreach (static::FACETS as $facet) {
            $wanted = $preferences->{$facet};

            if ($wanted === null || $wanted === '' || $wanted === 'any') {
                continue;
            }

            $actual = $candidate->{$facet};

            $scores[] = match (true) {
                $actual === null => 0.5,          // unstated, not disqualifying
                $actual === $wanted => 1.0,
                default => 0.0,
            };
        }

        return $scores === [] ? 1.0 : array_sum($scores) / count($scores);
    }

    public function label(): string
    {
        return 'Similar lifestyle';
    }
}
