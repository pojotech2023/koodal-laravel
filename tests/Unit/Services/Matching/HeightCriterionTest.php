<?php

namespace Tests\Unit\Services\Matching;

use App\Services\Matching\Criteria\HeightCriterion;
use Tests\Concerns\BuildsMatchProfiles;
use Tests\TestCase;

class HeightCriterionTest extends TestCase
{
    use BuildsMatchProfiles;

    public function test_no_height_preference_never_penalises_a_candidate(): void
    {
        $viewer = $this->matchProfile();
        $candidate = $this->matchProfile(['height_cm' => 150]);

        $this->assertSame(1.0, (new HeightCriterion)->score($viewer, $candidate));
    }

    public function test_a_candidate_with_no_recorded_height_is_never_penalised(): void
    {
        $viewer = $this->withPreferences($this->matchProfile(), ['height_min_cm' => 160, 'height_max_cm' => 175]);
        $candidate = $this->matchProfile(['height_cm' => null]);

        $this->assertSame(1.0, (new HeightCriterion)->score($viewer, $candidate));
    }

    public function test_a_height_inside_the_range_scores_perfectly(): void
    {
        $viewer = $this->withPreferences($this->matchProfile(), ['height_min_cm' => 160, 'height_max_cm' => 175]);
        $candidate = $this->matchProfile(['height_cm' => 168]);

        $this->assertSame(1.0, (new HeightCriterion)->score($viewer, $candidate));
    }

    public function test_three_cm_outside_the_range_tapers_to_eight_tenths(): void
    {
        $viewer = $this->withPreferences($this->matchProfile(), ['height_min_cm' => 160, 'height_max_cm' => 175]);
        $candidate = $this->matchProfile(['height_cm' => 178]);

        $this->assertSame(0.8, (new HeightCriterion)->score($viewer, $candidate));
    }

    public function test_eight_cm_outside_the_range_tapers_to_half(): void
    {
        $viewer = $this->withPreferences($this->matchProfile(), ['height_min_cm' => 160, 'height_max_cm' => 175]);
        $candidate = $this->matchProfile(['height_cm' => 183]);

        $this->assertSame(0.5, (new HeightCriterion)->score($viewer, $candidate));
    }

    public function test_fifteen_cm_outside_the_range_tapers_to_two_tenths(): void
    {
        $viewer = $this->withPreferences($this->matchProfile(), ['height_min_cm' => 160, 'height_max_cm' => 175]);
        $candidate = $this->matchProfile(['height_cm' => 190]);

        $this->assertSame(0.2, (new HeightCriterion)->score($viewer, $candidate));
    }

    public function test_far_outside_the_range_scores_zero(): void
    {
        $viewer = $this->withPreferences($this->matchProfile(), ['height_min_cm' => 160, 'height_max_cm' => 175]);
        $candidate = $this->matchProfile(['height_cm' => 200]);

        $this->assertSame(0.0, (new HeightCriterion)->score($viewer, $candidate));
    }
}
