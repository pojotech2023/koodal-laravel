<?php

namespace Tests\Unit\Services\Matching;

use App\Services\Matching\Criteria\LifestyleCriterion;
use Tests\Concerns\BuildsMatchProfiles;
use Tests\TestCase;

class LifestyleCriterionTest extends TestCase
{
    use BuildsMatchProfiles;

    public function test_no_preferences_row_at_all_never_penalises_a_candidate(): void
    {
        $viewer = $this->matchProfile();
        $candidate = $this->matchProfile(['diet' => 'non-vegetarian', 'smoking' => 'yes', 'drinking' => 'yes']);

        $this->assertSame(1.0, (new LifestyleCriterion)->score($viewer, $candidate));
    }

    public function test_every_facet_stated_as_any_or_blank_never_penalises_a_candidate(): void
    {
        $viewer = $this->withPreferences($this->matchProfile(), [
            'diet' => 'any', 'smoking' => null, 'drinking' => '',
        ]);
        $candidate = $this->matchProfile(['diet' => 'non-vegetarian', 'smoking' => 'yes', 'drinking' => 'yes']);

        $this->assertSame(1.0, (new LifestyleCriterion)->score($viewer, $candidate));
    }

    public function test_every_facet_matching_scores_perfectly(): void
    {
        $viewer = $this->withPreferences($this->matchProfile(), [
            'diet' => 'vegetarian', 'smoking' => 'no', 'drinking' => 'no',
        ]);
        $candidate = $this->matchProfile(['diet' => 'vegetarian', 'smoking' => 'no', 'drinking' => 'no']);

        $this->assertSame(1.0, (new LifestyleCriterion)->score($viewer, $candidate));
    }

    public function test_a_stated_facet_the_candidate_left_blank_is_a_half_not_a_mismatch(): void
    {
        $viewer = $this->withPreferences($this->matchProfile(), ['diet' => 'vegetarian']);
        $candidate = $this->matchProfile(['diet' => null]);

        $this->assertSame(0.5, (new LifestyleCriterion)->score($viewer, $candidate));
    }

    public function test_mixed_facets_average_the_three_contributions(): void
    {
        // diet matches (1.0), smoking mismatches (0.0), drinking unstated by
        // candidate (0.5) -> average 0.5.
        $viewer = $this->withPreferences($this->matchProfile(), [
            'diet' => 'vegetarian', 'smoking' => 'no', 'drinking' => 'no',
        ]);
        $candidate = $this->matchProfile(['diet' => 'vegetarian', 'smoking' => 'yes', 'drinking' => null]);

        $this->assertSame(0.5, (new LifestyleCriterion)->score($viewer, $candidate));
    }
}
