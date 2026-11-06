<?php

namespace App\Services\Matching\Criteria;

use App\Models\Education;
use App\Models\UserProfile;
use App\Services\Matching\Criterion;
use Illuminate\Database\Eloquent\Collection;

/**
 * Education is ordinal, not categorical. A post-graduate is a near match for
 * someone who asked for a graduate; a school leaver is not. Scoring it as a
 * plain set membership throws that ordering away.
 */
class EducationCriterion implements Criterion
{
    protected ?Collection $educations = null;

    public function score(UserProfile $viewer, UserProfile $candidate): float
    {
        $preferred = $viewer->user->preferences?->education_ids ?? [];

        if ($preferred === []) {
            return 1.0;
        }

        if (in_array($candidate->education_id, $preferred, true)) {
            return 1.0;
        }

        if (! $candidate->education_id) {
            return 0.3;
        }

        $candidateTier = $this->educations()->get($candidate->education_id)?->tier();
        $preferredTiers = $this->educations()->only($preferred)->map->tier();

        if ($candidateTier === null || $preferredTiers->isEmpty()) {
            return 0.3;
        }

        $distance = $preferredTiers->map(fn (int $tier) => abs($tier - $candidateTier))->min();

        return match ($distance) {
            0 => 1.0,
            1 => 0.6,
            2 => 0.3,
            default => 0.1,
        };
    }

    public function label(): string
    {
        return 'Education matches';
    }

    /**
     * A few dozen rows, held for the life of the instance. The nightly
     * recompute scores thousands of candidates through one Criterion object;
     * looking this table up per candidate turned that into two queries a row.
     */
    protected function educations(): Collection
    {
        return $this->educations ??= Education::all()->keyBy('id');
    }
}
