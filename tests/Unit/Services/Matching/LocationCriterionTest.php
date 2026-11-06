<?php

namespace Tests\Unit\Services\Matching;

use App\Services\Matching\Criteria\LocationCriterion;
use Tests\Concerns\BuildsMatchProfiles;
use Tests\TestCase;

class LocationCriterionTest extends TestCase
{
    use BuildsMatchProfiles;

    public function test_a_candidate_in_the_preferred_city_list_scores_perfectly(): void
    {
        $viewer = $this->withPreferences($this->matchProfile(), ['city_ids' => [10, 11]]);
        $candidate = $this->matchProfile(['city_id' => 11]);

        $this->assertSame(1.0, (new LocationCriterion)->score($viewer, $candidate));
    }

    public function test_outside_the_preferred_city_list_scores_low(): void
    {
        $viewer = $this->withPreferences($this->matchProfile(), ['city_ids' => [10, 11]]);
        $candidate = $this->matchProfile(['city_id' => 99]);

        $this->assertSame(0.2, (new LocationCriterion)->score($viewer, $candidate));
    }

    public function test_no_preference_falls_back_to_same_city_as_the_viewer(): void
    {
        $viewer = $this->matchProfile(['city_id' => 5, 'state_id' => 1, 'country_id' => 1]);
        $candidate = $this->matchProfile(['city_id' => 5, 'state_id' => 1, 'country_id' => 1]);

        $this->assertSame(1.0, (new LocationCriterion)->score($viewer, $candidate));
    }

    public function test_no_preference_falls_back_to_same_state_when_city_differs(): void
    {
        $viewer = $this->matchProfile(['city_id' => 5, 'state_id' => 1, 'country_id' => 1]);
        $candidate = $this->matchProfile(['city_id' => 6, 'state_id' => 1, 'country_id' => 1]);

        $this->assertSame(0.7, (new LocationCriterion)->score($viewer, $candidate));
    }

    public function test_no_preference_falls_back_to_same_country_when_state_differs(): void
    {
        $viewer = $this->matchProfile(['city_id' => 5, 'state_id' => 1, 'country_id' => 1]);
        $candidate = $this->matchProfile(['city_id' => 6, 'state_id' => 2, 'country_id' => 1]);

        $this->assertSame(0.4, (new LocationCriterion)->score($viewer, $candidate));
    }

    public function test_no_preference_and_nothing_in_common_scores_the_floor(): void
    {
        $viewer = $this->matchProfile(['city_id' => 5, 'state_id' => 1, 'country_id' => 1]);
        $candidate = $this->matchProfile(['city_id' => 6, 'state_id' => 2, 'country_id' => 2]);

        $this->assertSame(0.1, (new LocationCriterion)->score($viewer, $candidate));
    }
}
