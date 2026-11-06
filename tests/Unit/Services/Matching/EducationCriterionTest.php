<?php

namespace Tests\Unit\Services\Matching;

use App\Models\Education;
use App\Services\Matching\Criteria\EducationCriterion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsMatchProfiles;
use Tests\TestCase;

/**
 * The one Criteria class (besides Income) that hits the database: it ranks
 * education by an ordinal "tier" derived from `Education::category`, read via
 * `Education::all()`. These rows must be persisted, unlike every other
 * Criteria test in this directory.
 */
class EducationCriterionTest extends TestCase
{
    use BuildsMatchProfiles, RefreshDatabase;

    protected function education(string $category): Education
    {
        static $n = 0;
        $n++;

        return Education::create([
            'name' => "{$category}-{$n}",
            'slug' => "{$category}-{$n}",
            'category' => $category,
        ]);
    }

    public function test_no_preference_never_penalises_a_candidate(): void
    {
        $graduate = $this->education('graduate');
        $viewer = $this->matchProfile();
        $candidate = $this->matchProfile(['education_id' => $graduate->id]);

        $this->assertSame(1.0, (new EducationCriterion)->score($viewer, $candidate));
    }

    public function test_an_exact_id_match_scores_perfectly(): void
    {
        $graduate = $this->education('graduate');
        $viewer = $this->withPreferences($this->matchProfile(), ['education_ids' => [$graduate->id]]);
        $candidate = $this->matchProfile(['education_id' => $graduate->id]);

        $this->assertSame(1.0, (new EducationCriterion)->score($viewer, $candidate));
    }

    public function test_a_candidate_with_no_recorded_education_scores_low(): void
    {
        $graduate = $this->education('graduate');
        $viewer = $this->withPreferences($this->matchProfile(), ['education_ids' => [$graduate->id]]);
        $candidate = $this->matchProfile(['education_id' => null]);

        $this->assertSame(0.3, (new EducationCriterion)->score($viewer, $candidate));
    }

    public function test_a_different_id_at_the_same_tier_scores_perfectly(): void
    {
        // Two different rows, both "graduate" (tier 3) -> tier distance 0,
        // even though neither id matches the other directly.
        $preferred = $this->education('graduate');
        $sameTier = $this->education('graduate');
        $viewer = $this->withPreferences($this->matchProfile(), ['education_ids' => [$preferred->id]]);
        $candidate = $this->matchProfile(['education_id' => $sameTier->id]);

        $this->assertSame(1.0, (new EducationCriterion)->score($viewer, $candidate));
    }

    public function test_one_tier_away_scores_six_tenths(): void
    {
        $graduate = $this->education('graduate');       // tier 3
        $postGraduate = $this->education('post_graduate'); // tier 4
        $viewer = $this->withPreferences($this->matchProfile(), ['education_ids' => [$graduate->id]]);
        $candidate = $this->matchProfile(['education_id' => $postGraduate->id]);

        $this->assertSame(0.6, (new EducationCriterion)->score($viewer, $candidate));
    }

    public function test_two_tiers_away_scores_three_tenths(): void
    {
        $graduate = $this->education('graduate'); // tier 3
        $doctorate = $this->education('doctorate'); // tier 5
        $viewer = $this->withPreferences($this->matchProfile(), ['education_ids' => [$graduate->id]]);
        $candidate = $this->matchProfile(['education_id' => $doctorate->id]);

        $this->assertSame(0.3, (new EducationCriterion)->score($viewer, $candidate));
    }

    public function test_three_or_more_tiers_away_scores_one_tenth(): void
    {
        $school = $this->education('school');   // tier 1
        $doctorate = $this->education('doctorate'); // tier 5
        $viewer = $this->withPreferences($this->matchProfile(), ['education_ids' => [$school->id]]);
        $candidate = $this->matchProfile(['education_id' => $doctorate->id]);

        $this->assertSame(0.1, (new EducationCriterion)->score($viewer, $candidate));
    }
}
