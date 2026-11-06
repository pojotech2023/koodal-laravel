<?php

namespace App\Services\Matching\Criteria;

use App\Models\IncomeRange;
use App\Models\UserProfile;
use App\Services\Matching\Criterion;
use Illuminate\Database\Eloquent\Collection;

/**
 * Compares the candidate's income *band* against a stated range. Bands are
 * intervals, so "does the band overlap the preference" is the honest question —
 * comparing midpoints would exclude a band that clearly contains the number the
 * user asked for.
 */
class IncomeCriterion implements Criterion
{
    protected ?Collection $bands = null;

    public function score(UserProfile $viewer, UserProfile $candidate): float
    {
        $preferences = $viewer->user->preferences;
        $min = $preferences?->income_min;
        $max = $preferences?->income_max;

        if ($min === null && $max === null) {
            return 1.0;
        }

        if (! $candidate->income_range_id) {
            // Income is the most-skipped field on the form. Treating a blank as
            // a mismatch would bury otherwise strong candidates.
            return 0.5;
        }

        $band = $this->bands()->get($candidate->income_range_id);

        if (! $band) {
            return 0.5;
        }

        if ($band->overlaps($min, $max)) {
            return 1.0;
        }

        // Above the requested ceiling is nobody's dealbreaker; below the floor
        // is the case the preference was actually expressing.
        return $min !== null && $band->min_amount > $min ? 0.8 : 0.2;
    }

    public function label(): string
    {
        return 'Income matches your preference';
    }

    /**
     * A dozen rows, held for the life of the instance. The nightly recompute
     * runs thousands of candidates through one Criterion object, and a find()
     * per candidate is a query per row for data that never changes mid-run.
     */
    protected function bands(): Collection
    {
        return $this->bands ??= IncomeRange::all()->keyBy('id');
    }
}
