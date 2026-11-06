<?php

namespace Tests\Unit\Services\Matching;

use App\Services\Matching\Criteria\ReligionCriterion;
use Tests\Concerns\BuildsMatchProfiles;
use Tests\TestCase;

class ReligionCriterionTest extends TestCase
{
    use BuildsMatchProfiles;

    public function test_no_preference_never_penalises_a_candidate(): void
    {
        $viewer = $this->matchProfile();
        $candidate = $this->matchProfile(['religion_id' => 2]);

        $this->assertSame(1.0, (new ReligionCriterion)->score($viewer, $candidate));
    }

    public function test_a_candidate_in_the_preferred_list_scores_perfectly(): void
    {
        $viewer = $this->withPreferences($this->matchProfile(), ['religion_ids' => [1, 2]]);
        $candidate = $this->matchProfile(['religion_id' => 2]);

        $this->assertSame(1.0, (new ReligionCriterion)->score($viewer, $candidate));
    }

    public function test_outside_the_preferred_list_scores_zero(): void
    {
        $viewer = $this->withPreferences($this->matchProfile(), ['religion_ids' => [1, 2]]);
        $candidate = $this->matchProfile(['religion_id' => 9]);

        $this->assertSame(0.0, (new ReligionCriterion)->score($viewer, $candidate));
    }
}
