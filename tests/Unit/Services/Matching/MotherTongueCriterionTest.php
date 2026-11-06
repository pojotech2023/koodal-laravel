<?php

namespace Tests\Unit\Services\Matching;

use App\Services\Matching\Criteria\MotherTongueCriterion;
use Tests\Concerns\BuildsMatchProfiles;
use Tests\TestCase;

class MotherTongueCriterionTest extends TestCase
{
    use BuildsMatchProfiles;

    public function test_no_preference_but_same_mother_tongue_as_viewer_scores_perfectly(): void
    {
        $viewer = $this->matchProfile(['mother_tongue_id' => 3]);
        $candidate = $this->matchProfile(['mother_tongue_id' => 3]);

        $this->assertSame(1.0, (new MotherTongueCriterion)->score($viewer, $candidate));
    }

    public function test_no_preference_and_a_different_mother_tongue_scores_half(): void
    {
        $viewer = $this->matchProfile(['mother_tongue_id' => 3]);
        $candidate = $this->matchProfile(['mother_tongue_id' => 4]);

        $this->assertSame(0.5, (new MotherTongueCriterion)->score($viewer, $candidate));
    }

    public function test_a_candidate_in_the_preferred_list_scores_perfectly(): void
    {
        $viewer = $this->withPreferences($this->matchProfile(['mother_tongue_id' => 3]), [
            'mother_tongue_ids' => [7, 8],
        ]);
        $candidate = $this->matchProfile(['mother_tongue_id' => 8]);

        $this->assertSame(1.0, (new MotherTongueCriterion)->score($viewer, $candidate));
    }

    public function test_outside_the_preferred_list_scores_low(): void
    {
        $viewer = $this->withPreferences($this->matchProfile(['mother_tongue_id' => 3]), [
            'mother_tongue_ids' => [7, 8],
        ]);
        $candidate = $this->matchProfile(['mother_tongue_id' => 3]);

        $this->assertSame(0.2, (new MotherTongueCriterion)->score($viewer, $candidate));
    }
}
