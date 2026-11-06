<?php

namespace Tests\Feature\Api\V1\Search;

use App\Models\Religion;
use App\Models\Role;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\Concerns\SeedsCoreData;
use Tests\TestCase;

/**
 * `SearchService::discoverable()` + `RunSearch::assertFiltersAllowed()`: the
 * exclusion rules every candidate must pass before appearing in a result set,
 * and the "basic filters are free, everything else needs advanced_search"
 * gate. None of this had a test before.
 */
class SearchFilterTest extends TestCase
{
    use RefreshDatabase, SeedsCoreData;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedRoles();
        $this->seedPlans();
        $this->seedMasterData();
    }

    protected function candidate(array $userAttrs = [], array $profileAttrs = []): User
    {
        $user = User::factory()->create(array_merge([
            'role_id' => Role::firstWhere('name', 'user')?->id,
            'status' => 'active',
            'mobile_verified_at' => now(),
            'gender' => 'female',
        ], $userAttrs));

        UserProfile::factory()->for($user)->create(array_merge(
            ['approval_status' => 'approved'],
            $profileAttrs,
        ));

        $user->privacySettings()->create(config('matrimony.privacy.defaults'));

        return $user->refresh();
    }

    protected function profileCodesIn(TestResponse $response): array
    {
        return array_column($response->json('data'), 'profile_code');
    }

    // --- Exclusion rules -----------------------------------------------------

    public function test_search_only_returns_the_opposite_gender(): void
    {
        $this->actingAsMember(attributes: ['gender' => 'male']);
        $her = $this->candidate(['gender' => 'female']);
        $him = $this->candidate(['gender' => 'male']);

        $response = $this->getJson('/api/v1/search');

        $codes = $this->profileCodesIn($response);
        $this->assertContains($her->profile_code, $codes);
        $this->assertNotContains($him->profile_code, $codes);
    }

    public function test_search_excludes_a_profile_still_pending_approval(): void
    {
        $this->actingAsMember(attributes: ['gender' => 'male']);
        $pending = $this->candidate([], ['approval_status' => 'pending']);

        $response = $this->getJson('/api/v1/search');

        $this->assertNotContains($pending->profile_code, $this->profileCodesIn($response));
    }

    public function test_search_excludes_a_suspended_users_profile(): void
    {
        $this->actingAsMember(attributes: ['gender' => 'male']);
        $suspended = $this->candidate(['status' => 'suspended']);

        $response = $this->getJson('/api/v1/search');

        $this->assertNotContains($suspended->profile_code, $this->profileCodesIn($response));
    }

    public function test_search_excludes_a_profile_that_opted_out_of_appearing_in_search(): void
    {
        $this->actingAsMember(attributes: ['gender' => 'male']);
        $hidden = $this->candidate();
        $hidden->privacySettings()->update(['appear_in_search' => false]);

        $response = $this->getJson('/api/v1/search');

        $this->assertNotContains($hidden->profile_code, $this->profileCodesIn($response));
    }

    public function test_search_excludes_a_profile_the_viewer_has_blocked(): void
    {
        $viewer = $this->actingAsMember(attributes: ['gender' => 'male']);
        $blocked = $this->candidate();
        $viewer->blocks()->create(['blocked_user_id' => $blocked->id]);

        $response = $this->getJson('/api/v1/search');

        $this->assertNotContains($blocked->profile_code, $this->profileCodesIn($response));
    }

    public function test_search_excludes_a_profile_that_has_blocked_the_viewer(): void
    {
        $viewer = $this->actingAsMember(attributes: ['gender' => 'male']);
        $blocker = $this->candidate();
        $blocker->blocks()->create(['blocked_user_id' => $viewer->id]);

        $response = $this->getJson('/api/v1/search');

        $this->assertNotContains($blocker->profile_code, $this->profileCodesIn($response));
    }

    // --- Basic filters: free to everyone --------------------------------------

    public function test_a_basic_religion_filter_narrows_results_on_a_free_plan(): void
    {
        $this->actingAsMember(attributes: ['gender' => 'male']);
        $religions = Religion::inRandomOrder()->take(2)->get();
        $this->assertCount(2, $religions, 'MasterDataSeeder must seed at least two religions.');
        [$wanted, $other] = $religions;

        $matching = $this->candidate([], ['religion_id' => $wanted->id]);
        $nonMatching = $this->candidate([], ['religion_id' => $other->id]);

        $response = $this->getJson('/api/v1/search?'.http_build_query(['religion_id' => $wanted->id]));

        $response->assertOk();
        $codes = $this->profileCodesIn($response);
        $this->assertContains($matching->profile_code, $codes);
        $this->assertNotContains($nonMatching->profile_code, $codes);
    }

    public function test_a_basic_age_range_filter_excludes_candidates_outside_it(): void
    {
        $this->actingAsMember(attributes: ['gender' => 'male']);
        $inRange = $this->candidate(['date_of_birth' => now()->subYears(28)]);
        $outOfRange = $this->candidate(['date_of_birth' => now()->subYears(50)]);

        $response = $this->getJson('/api/v1/search?'.http_build_query(['age_min' => 25, 'age_max' => 32]));

        $response->assertOk();
        $codes = $this->profileCodesIn($response);
        $this->assertContains($inRange->profile_code, $codes);
        $this->assertNotContains($outOfRange->profile_code, $codes);
    }

    // --- Advanced filters: gated behind the advanced_search feature -----------

    public function test_an_advanced_filter_is_rejected_for_a_free_plan_viewer(): void
    {
        $this->actingAsMember(attributes: ['gender' => 'male']);

        $response = $this->getJson('/api/v1/search?'.http_build_query(['is_verified' => 1]));

        $response->assertStatus(403);
        $response->assertJsonPath('errors.upgrade_required', true);
        $response->assertJsonPath('errors.required_feature', 'advanced_search');
    }

    public function test_an_advanced_filter_is_honoured_for_a_subscribed_viewer(): void
    {
        $viewer = $this->actingAsMember(attributes: ['gender' => 'male']);
        $this->subscribe($viewer, 'gold');

        $verified = $this->candidate([], ['is_verified' => true]);
        $unverified = $this->candidate([], ['is_verified' => false]);

        $response = $this->getJson('/api/v1/search?'.http_build_query(['is_verified' => 1]));

        $response->assertOk();
        $codes = $this->profileCodesIn($response);
        $this->assertContains($verified->profile_code, $codes);
        $this->assertNotContains($unverified->profile_code, $codes);
    }

    public function test_an_empty_advanced_filter_value_does_not_trigger_the_paywall(): void
    {
        $this->actingAsMember(attributes: ['gender' => 'male']);

        // education_ids sent as an empty array — RunSearch::assertFiltersAllowed
        // filters out empty values before deciding what counts as "used".
        $response = $this->getJson('/api/v1/search?'.http_build_query(['education_ids' => []]));

        $response->assertOk();
    }

    // --- quick() bypasses the plan gate entirely -------------------------------

    public function test_quick_search_finds_a_profile_by_code_regardless_of_plan(): void
    {
        $this->actingAsMember(attributes: ['gender' => 'male']);
        $her = $this->candidate();

        $response = $this->getJson('/api/v1/search/quick?'.http_build_query(['q' => $her->profile_code]));

        $response->assertOk();
        $this->assertContains($her->profile_code, $this->profileCodesIn($response));
    }

    // --- filters() reports what a free plan cannot use -------------------------

    public function test_the_filters_endpoint_locks_advanced_filters_for_a_free_plan_viewer(): void
    {
        $this->actingAsMember(attributes: ['gender' => 'male']);

        $response = $this->getJson('/api/v1/search/filters');

        $response->assertOk();
        $response->assertJsonPath('data.locked', fn ($locked) => in_array('is_verified', $locked, true));
    }

    public function test_the_filters_endpoint_unlocks_everything_for_a_subscribed_viewer(): void
    {
        $viewer = $this->actingAsMember(attributes: ['gender' => 'male']);
        $this->subscribe($viewer, 'gold');

        $response = $this->getJson('/api/v1/search/filters');

        $response->assertOk();
        $response->assertJsonPath('data.locked', []);
    }
}
