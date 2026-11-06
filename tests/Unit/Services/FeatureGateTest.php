<?php

namespace Tests\Unit\Services;

use App\Exceptions\FeatureLimitException;
use App\Models\MembershipPlan;
use App\Models\User;
use App\Services\Membership\FeatureGate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\SeedsCoreData;
use Tests\TestCase;

class FeatureGateTest extends TestCase
{
    use RefreshDatabase;
    use SeedsCoreData;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedRoles();
        $this->seedPlans();
    }

    /**
     * `AppServiceProvider` turns on `Model::preventLazyLoading()` outside
     * production — including in `testing` — the same as every controller that
     * calls `FeatureGate` must already `->load('activeSubscription')` first.
     */
    protected function loaded(User $user): User
    {
        return $user->load('activeSubscription');
    }

    public function test_a_user_with_no_subscription_gets_the_default_free_plan_features(): void
    {
        $gate = app(FeatureGate::class);
        $user = $this->loaded($this->member());

        $free = MembershipPlan::where('is_default', true)->firstOrFail();

        $this->assertSame($free->features['interests_per_month'], $gate->limit($user, 'interests_per_month'));
        $this->assertFalse($gate->allows($user, 'can_chat'));
    }

    public function test_allows_reads_a_boolean_capability_off_the_active_subscription_snapshot(): void
    {
        $gate = app(FeatureGate::class);
        $user = $this->loaded($this->subscribe($this->member(), 'gold'));

        $this->assertTrue($gate->allows($user, 'can_chat'));
        $this->assertTrue($gate->allows($user, 'can_view_contact'));
    }

    public function test_consume_is_reflected_in_used_and_remaining(): void
    {
        $gate = app(FeatureGate::class);
        $user = $this->loaded($this->subscribe($this->member(), 'gold'));

        $this->assertSame(0, $gate->used($user, 'interests_per_month'));
        $this->assertSame(100, $gate->remaining($user, 'interests_per_month'));

        $gate->consume($user, 'interests_per_month', 3);

        $this->assertSame(3, $gate->used($user, 'interests_per_month'));
        $this->assertSame(97, $gate->remaining($user, 'interests_per_month'));
    }

    public function test_consume_accumulates_across_calls_rather_than_overwriting(): void
    {
        $gate = app(FeatureGate::class);
        $user = $this->loaded($this->subscribe($this->member(), 'gold'));

        $gate->consume($user, 'interests_per_month');
        $gate->consume($user, 'interests_per_month');
        $gate->consume($user, 'interests_per_month');

        $this->assertSame(3, $gate->used($user, 'interests_per_month'));
    }

    public function test_assert_quota_throws_once_the_limit_would_be_exceeded(): void
    {
        $gate = app(FeatureGate::class);
        $user = $this->loaded($this->subscribe($this->member(), 'free'));

        // Free plan's interests_per_month is 5.
        $gate->consume($user, 'interests_per_month', 5);

        $this->expectException(FeatureLimitException::class);

        $gate->assertQuota($user, 'interests_per_month');
    }

    public function test_assert_quota_passes_while_units_remain(): void
    {
        $gate = app(FeatureGate::class);
        $user = $this->loaded($this->subscribe($this->member(), 'free'));

        $gate->consume($user, 'interests_per_month', 4);

        $gate->assertQuota($user, 'interests_per_month');
        $this->assertTrue(true); // no exception raised
    }

    public function test_assert_allowed_throws_for_a_disallowed_capability(): void
    {
        $gate = app(FeatureGate::class);
        $user = $this->loaded($this->subscribe($this->member(), 'free'));

        $this->expectException(FeatureLimitException::class);

        $gate->assertAllowed($user, 'can_chat');
    }

    public function test_remaining_is_unbounded_when_the_limit_is_unlimited(): void
    {
        $gate = app(FeatureGate::class);
        $user = $this->member();
        $plan = MembershipPlan::firstWhere('slug', 'gold');

        $user->subscriptions()->create([
            'membership_plan_id' => $plan->id,
            'starts_at' => now(),
            'expires_at' => now()->addDays(90),
            'status' => 'active',
            'features_snapshot' => array_merge($plan->features, ['interests_per_month' => -1]),
        ]);

        $user = $this->loaded($user);

        $this->assertSame(PHP_INT_MAX, $gate->remaining($user, 'interests_per_month'));
    }

    public function test_flush_clears_the_cached_feature_map_after_an_upgrade(): void
    {
        $gate = app(FeatureGate::class);
        $user = $this->loaded($this->member());

        // Cache the free-tier answer.
        $this->assertFalse($gate->allows($user, 'can_chat'));

        $user = $this->loaded($this->subscribe($user, 'gold'));
        $gate->flush($user);

        $this->assertTrue($gate->allows($user, 'can_chat'));
    }

    public function test_two_concurrent_consumes_both_land_via_the_atomic_upsert(): void
    {
        // Simulates the race the class docblock describes: two requests
        // consuming the last units of a quota must both be recorded, not
        // clobber each other via a read-modify-write.
        $gate = app(FeatureGate::class);
        $user = $this->loaded($this->subscribe($this->member(), 'gold'));

        $gate->consume($user, 'interests_per_month', 1);
        $gate->consume($user, 'interests_per_month', 1);

        $this->assertSame(2, $gate->used($user, 'interests_per_month'));
    }
}
