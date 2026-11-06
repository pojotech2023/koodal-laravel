<?php

namespace Tests\Unit\Services;

use App\Models\City;
use App\Models\Community;
use App\Models\Education;
use App\Models\IncomeRange;
use App\Models\MotherTongue;
use App\Models\Occupation;
use App\Models\Religion;
use App\Models\User;
use App\Models\UserProfile;
use App\Services\Matching\CriterionRegistry;
use App\Services\Matching\MatchScorer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\SeedsCoreData;
use Tests\TestCase;

class MatchScorerTest extends TestCase
{
    use RefreshDatabase;
    use SeedsCoreData;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedRoles();
        $this->seedMasterData();
    }

    protected function scorer(): MatchScorer
    {
        return new MatchScorer(app(CriterionRegistry::class));
    }

    /** Loads exactly what every Criterion touches, matching the RecomputeMatchScores job. */
    protected function forScoring(UserProfile $profile): UserProfile
    {
        return $profile->fresh(['user.preferences']);
    }

    public function test_a_candidate_who_matches_every_stated_preference_scores_far_above_one_who_matches_none(): void
    {
        $religionA = Religion::first();
        $communityA = Community::where('religion_id', $religionA->id)->first();
        $religionB = Religion::where('id', '!=', $religionA->id)->first();
        $communityB = Community::where('religion_id', $religionB->id)->first();

        $mtA = MotherTongue::first();
        $mtB = MotherTongue::where('id', '!=', $mtA->id)->first();

        $cityA = City::whereNotNull('state_id')->first();
        $cityB = City::where('state_id', '!=', $cityA->state_id)->first();

        $eduA = Education::orderBy('id')->first();
        $eduB = Education::orderBy('id', 'desc')->first();

        $occA = Occupation::first();
        $occB = Occupation::where('id', '!=', $occA->id)->first();

        $incomeBands = IncomeRange::orderBy('min_amount')->get();
        $incomeA = $incomeBands->last();
        $incomeB = $incomeBands->first();

        $this->assertNotNull($religionB, 'seeded master data needs 2+ religions for this test');
        $this->assertNotNull($cityB, 'seeded master data needs cities across 2+ states for this test');
        $this->assertGreaterThanOrEqual(2, $incomeBands->count(), 'seeded master data needs 2+ income bands for this test');

        // The viewer, with an explicit preference in every dimension the
        // matching config weighs.
        $viewerUser = User::factory()->aged(30)->create();
        $viewerProfile = UserProfile::factory()->create([
            'user_id' => $viewerUser->id,
            'religion_id' => $religionA->id,
            'community_id' => $communityA->id,
            'mother_tongue_id' => $mtA->id,
            'city_id' => $cityA->id,
            'state_id' => $cityA->state_id,
            'country_id' => $cityA->state->country_id,
        ]);
        $viewerUser->preferences()->create([
            'age_min' => 20,
            'age_max' => 35,
            'religion_ids' => [$religionA->id],
            'community_ids' => [$communityA->id],
            'education_ids' => [$eduA->id],
            'occupation_ids' => [$occA->id],
            'height_min_cm' => 165,
            'height_max_cm' => 180,
            'income_min' => $incomeA->min_amount,
            'income_max' => $incomeA->max_amount ?? $incomeA->min_amount * 2,
            'diet' => 'vegetarian',
            'smoking' => 'no',
            'drinking' => 'no',
        ]);

        $shared = [
            'is_verified' => true,
            'completion_percentage' => 90,
        ];

        $goodUser = User::factory()->aged(28)->create(['last_active_at' => now()]);
        $goodProfile = UserProfile::factory()->create(array_merge($shared, [
            'user_id' => $goodUser->id,
            'religion_id' => $religionA->id,
            'community_id' => $communityA->id,
            'mother_tongue_id' => $mtA->id,
            'city_id' => $cityA->id,
            'state_id' => $cityA->state_id,
            'country_id' => $cityA->state->country_id,
            'education_id' => $eduA->id,
            'occupation_id' => $occA->id,
            'income_range_id' => $incomeA->id,
            'height_cm' => 170,
            'diet' => 'vegetarian',
            'smoking' => 'no',
            'drinking' => 'no',
        ]));
        $goodUser->preferences()->create([]);

        $poorUser = User::factory()->aged(50)->create(['last_active_at' => now()]);
        $poorProfile = UserProfile::factory()->create(array_merge($shared, [
            'user_id' => $poorUser->id,
            'religion_id' => $religionB->id,
            'community_id' => $communityB->id,
            'mother_tongue_id' => $mtB->id,
            'city_id' => $cityB->id,
            'state_id' => $cityB->state_id,
            'country_id' => $cityB->state->country_id,
            'education_id' => $eduB->id,
            'occupation_id' => $occB->id,
            'income_range_id' => $incomeB->id,
            'height_cm' => 140,
            'diet' => 'non-vegetarian',
            'smoking' => 'occasionally',
            'drinking' => 'occasionally',
        ]));
        $poorUser->preferences()->create([]);

        $scorer = $this->scorer();
        $viewer = $this->forScoring($viewerProfile);

        $good = $scorer->score($viewer, $this->forScoring($goodProfile));
        $poor = $scorer->score($viewer, $this->forScoring($poorProfile));

        $this->assertGreaterThan($poor->value, $good->value);
        $this->assertGreaterThanOrEqual(85, $good->value);
        $this->assertLessThanOrEqual(60, $poor->value);
        $this->assertContains('Same community', $good->breakdown);
    }

    public function test_score_is_clamped_between_0_and_100(): void
    {
        $viewerUser = User::factory()->aged(30)->create();
        $viewerProfile = UserProfile::factory()->create(['user_id' => $viewerUser->id]);
        $viewerUser->preferences()->create([]);

        $candidateUser = User::factory()->aged(28)->create();
        $candidateProfile = UserProfile::factory()->create(['user_id' => $candidateUser->id]);
        $candidateUser->preferences()->create([]);

        $score = $this->scorer()->score($this->forScoring($viewerProfile), $this->forScoring($candidateProfile));

        $this->assertGreaterThanOrEqual(0, $score->value);
        $this->assertLessThanOrEqual(100, $score->value);
    }

    public function test_an_empty_preference_does_not_penalise_a_candidate(): void
    {
        // MatchScorer's own docblock: an empty preference scores 1.0, not 0.0
        // — most users fill in a few preferences and leave the rest blank.
        $religion = Religion::first();

        $viewerUser = User::factory()->aged(30)->create();
        $viewerProfile = UserProfile::factory()->create([
            'user_id' => $viewerUser->id,
            'religion_id' => $religion->id,
        ]);
        // No preferences row at all — every *_ids preference is null.
        $viewerUser->preferences()->create([]);

        $candidateUser = User::factory()->aged(29)->create(['last_active_at' => now()]);
        $candidateProfile = UserProfile::factory()->create([
            'user_id' => $candidateUser->id,
            'religion_id' => Religion::where('id', '!=', $religion->id)->value('id'),
            'is_verified' => true,
            'completion_percentage' => 100,
        ]);
        $candidateUser->preferences()->create([]);

        $score = $this->scorer()->score($this->forScoring($viewerProfile), $this->forScoring($candidateProfile));

        // With no stated preference anywhere, religion mismatch alone must not
        // tank the score — most other criteria also default to 1.0.
        $this->assertGreaterThan(50, $score->value);
    }
}
