<?php

namespace Tests\Unit\Services\Matching;

use App\Services\Matching\Criteria\OccupationCriterion;
use Tests\Concerns\BuildsMatchProfiles;
use Tests\TestCase;

class OccupationCriterionTest extends TestCase
{
    use BuildsMatchProfiles;

    public function test_no_preference_never_penalises_a_candidate(): void
    {
        $viewer = $this->matchProfile();
        $candidate = $this->matchProfile(['occupation_id' => 1]);

        $this->assertSame(1.0, (new OccupationCriterion)->score($viewer, $candidate));
    }

    public function test_a_candidate_in_the_preferred_list_scores_perfectly(): void
    {
        $viewer = $this->withPreferences($this->matchProfile(), ['occupation_ids' => [4, 5]]);
        $candidate = $this->matchProfile(['occupation_id' => 5]);

        $this->assertSame(1.0, (new OccupationCriterion)->score($viewer, $candidate));
    }

    public function test_outside_the_preferred_list_scores_a_quarter(): void
    {
        $viewer = $this->withPreferences($this->matchProfile(), ['occupation_ids' => [4, 5]]);
        $candidate = $this->matchProfile(['occupation_id' => 9]);

        $this->assertSame(0.25, (new OccupationCriterion)->score($viewer, $candidate));
    }
}
