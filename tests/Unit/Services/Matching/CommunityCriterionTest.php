<?php

namespace Tests\Unit\Services\Matching;

use App\Services\Matching\Criteria\CommunityCriterion;
use Tests\Concerns\BuildsMatchProfiles;
use Tests\TestCase;

class CommunityCriterionTest extends TestCase
{
    use BuildsMatchProfiles;

    public function test_no_preference_but_same_community_as_viewer_scores_perfectly(): void
    {
        $viewer = $this->matchProfile(['community_id' => 1, 'religion_id' => 1]);
        $candidate = $this->matchProfile(['community_id' => 1, 'religion_id' => 1]);

        $this->assertSame(1.0, (new CommunityCriterion)->score($viewer, $candidate));
    }

    public function test_no_preference_and_a_different_community_still_scores_well(): void
    {
        $viewer = $this->matchProfile(['community_id' => 1, 'religion_id' => 1]);
        $candidate = $this->matchProfile(['community_id' => 2, 'religion_id' => 1]);

        $this->assertSame(0.6, (new CommunityCriterion)->score($viewer, $candidate));
    }

    public function test_a_candidate_in_the_preferred_community_list_scores_perfectly(): void
    {
        $viewer = $this->withPreferences(
            $this->matchProfile(['religion_id' => 1]),
            ['community_ids' => [5, 6]],
        );
        $candidate = $this->matchProfile(['community_id' => 6, 'religion_id' => 1]);

        $this->assertSame(1.0, (new CommunityCriterion)->score($viewer, $candidate));
    }

    public function test_outside_the_preferred_list_but_same_religion_is_a_near_miss(): void
    {
        $viewer = $this->withPreferences(
            $this->matchProfile(['religion_id' => 1]),
            ['community_ids' => [5, 6]],
        );
        $candidate = $this->matchProfile(['community_id' => 9, 'religion_id' => 1]);

        $this->assertSame(0.3, (new CommunityCriterion)->score($viewer, $candidate));
    }

    public function test_outside_the_preferred_list_and_a_different_religion_scores_zero(): void
    {
        $viewer = $this->withPreferences(
            $this->matchProfile(['religion_id' => 1]),
            ['community_ids' => [5, 6]],
        );
        $candidate = $this->matchProfile(['community_id' => 9, 'religion_id' => 2]);

        $this->assertSame(0.0, (new CommunityCriterion)->score($viewer, $candidate));
    }
}
