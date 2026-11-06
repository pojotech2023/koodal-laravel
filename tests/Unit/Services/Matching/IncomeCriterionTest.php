<?php

namespace Tests\Unit\Services\Matching;

use App\Models\IncomeRange;
use App\Services\Matching\Criteria\IncomeCriterion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\Concerns\BuildsMatchProfiles;
use Tests\TestCase;

/**
 * Like EducationCriterion, this one resolves the candidate's income *band*
 * via `IncomeRange::all()`, so the bands must be real, persisted rows.
 */
class IncomeCriterionTest extends TestCase
{
    use BuildsMatchProfiles, RefreshDatabase;

    protected function band(string $name, int $min, ?int $max): IncomeRange
    {
        return IncomeRange::create([
            'name' => $name,
            'slug' => Str::slug($name).'-'.$min,
            'min_amount' => $min,
            'max_amount' => $max,
        ]);
    }

    public function test_no_preference_never_penalises_a_candidate(): void
    {
        $band = $this->band('5-10L', 500_000, 1_000_000);
        $viewer = $this->matchProfile();
        $candidate = $this->matchProfile(['income_range_id' => $band->id]);

        $this->assertSame(1.0, (new IncomeCriterion)->score($viewer, $candidate));
    }

    public function test_a_candidate_with_no_recorded_income_scores_half(): void
    {
        $viewer = $this->withPreferences($this->matchProfile(), ['income_min' => 500_000, 'income_max' => 1_000_000]);
        $candidate = $this->matchProfile(['income_range_id' => null]);

        $this->assertSame(0.5, (new IncomeCriterion)->score($viewer, $candidate));
    }

    public function test_an_unresolvable_income_band_scores_half(): void
    {
        $viewer = $this->withPreferences($this->matchProfile(), ['income_min' => 500_000, 'income_max' => 1_000_000]);
        $candidate = $this->matchProfile(['income_range_id' => 999_999]);

        $this->assertSame(0.5, (new IncomeCriterion)->score($viewer, $candidate));
    }

    public function test_a_band_overlapping_the_requested_range_scores_perfectly(): void
    {
        $band = $this->band('5-10L', 500_000, 1_000_000);
        $viewer = $this->withPreferences($this->matchProfile(), ['income_min' => 800_000, 'income_max' => 1_500_000]);
        $candidate = $this->matchProfile(['income_range_id' => $band->id]);

        $this->assertSame(1.0, (new IncomeCriterion)->score($viewer, $candidate));
    }

    public function test_a_band_entirely_above_the_requested_ceiling_is_not_a_dealbreaker(): void
    {
        $band = $this->band('1Cr+', 10_000_000, null);
        $viewer = $this->withPreferences($this->matchProfile(), ['income_min' => 500_000, 'income_max' => 1_000_000]);
        $candidate = $this->matchProfile(['income_range_id' => $band->id]);

        $this->assertSame(0.8, (new IncomeCriterion)->score($viewer, $candidate));
    }

    public function test_a_band_entirely_below_the_requested_floor_scores_low(): void
    {
        $band = $this->band('0-1L', 0, 100_000);
        $viewer = $this->withPreferences($this->matchProfile(), ['income_min' => 500_000, 'income_max' => 1_000_000]);
        $candidate = $this->matchProfile(['income_range_id' => $band->id]);

        $this->assertSame(0.2, (new IncomeCriterion)->score($viewer, $candidate));
    }
}
