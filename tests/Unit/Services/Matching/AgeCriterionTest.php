<?php

namespace Tests\Unit\Services\Matching;

use App\Models\UserProfile;
use App\Services\Matching\Criteria\AgeCriterion;
use Tests\Concerns\BuildsMatchProfiles;
use Tests\TestCase;

class AgeCriterionTest extends TestCase
{
    use BuildsMatchProfiles;

    protected function candidateAged(int $years): UserProfile
    {
        return $this->matchProfile([], ['date_of_birth' => now()->subYears($years)->subDays(10)]);
    }

    public function test_no_age_preference_never_penalises_a_candidate(): void
    {
        $viewer = $this->matchProfile();
        $candidate = $this->candidateAged(70);

        $this->assertSame(1.0, (new AgeCriterion)->score($viewer, $candidate));
    }

    public function test_a_candidate_inside_the_stated_range_scores_perfectly(): void
    {
        $viewer = $this->withPreferences($this->matchProfile(), ['age_min' => 25, 'age_max' => 35]);
        $candidate = $this->candidateAged(30);

        $this->assertSame(1.0, (new AgeCriterion)->score($viewer, $candidate));
    }

    public function test_a_candidate_slightly_outside_the_range_tapers_to_seven_tenths(): void
    {
        // Range 25-35 (span 10). One year over -> relative 0.1, the boundary
        // of the top taper band.
        $viewer = $this->withPreferences($this->matchProfile(), ['age_min' => 25, 'age_max' => 35]);
        $candidate = $this->candidateAged(36);

        $this->assertSame(0.7, (new AgeCriterion)->score($viewer, $candidate));
    }

    public function test_a_candidate_moderately_outside_the_range_tapers_to_four_tenths(): void
    {
        // relative 0.25 exactly.
        $viewer = $this->withPreferences($this->matchProfile(), ['age_min' => 25, 'age_max' => 35]);
        $candidate = $this->candidateAged(37);

        $this->assertSame(0.4, (new AgeCriterion)->score($viewer, $candidate));
    }

    public function test_a_candidate_far_outside_the_range_scores_zero(): void
    {
        $viewer = $this->withPreferences($this->matchProfile(), ['age_min' => 25, 'age_max' => 35]);
        $candidate = $this->candidateAged(60);

        $this->assertSame(0.0, (new AgeCriterion)->score($viewer, $candidate));
    }
}
