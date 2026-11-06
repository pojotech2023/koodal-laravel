<?php

namespace Tests;

use App\Models\MembershipPlan;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Testing\TestResponse;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Every test asserts against the envelope, so the JSON header is set
        // once here rather than on 400 individual calls.
        $this->withHeaders(['Accept' => 'application/json', 'X-Client' => 'web']);
    }

    /**
     * A member with a verified mobile, an approved profile and a token that
     * carries the `user` ability — the state 95% of endpoints assume.
     */
    protected function member(array $attributes = [], array $profile = []): User
    {
        $user = User::factory()->create(array_merge([
            'role_id' => Role::firstWhere('name', 'user')?->id,
            'status' => 'active',
            'mobile_verified_at' => now(),
        ], $attributes));

        $user->profile()->create(array_merge([
            'approval_status' => 'approved',
            'completion_percentage' => 80,
        ], $profile));

        $user->privacySettings()->create(config('matrimony.privacy.defaults'));

        return $user->refresh();
    }

    protected function actingAsMember(?User $user = null, array $attributes = []): User
    {
        $user ??= $this->member($attributes);

        $this->withToken($user->createToken('test', ['user'])->plainTextToken);

        return $user;
    }

    protected function actingAsStaff(string $role = 'admin', array $permissions = []): User
    {
        $roleModel = Role::firstWhere('name', $role);

        if ($permissions !== []) {
            $roleModel->permissions()->syncWithoutDetaching(
                Permission::whereIn('name', $permissions)->pluck('id')
            );
        }

        $staff = User::factory()->create(['role_id' => $roleModel->id, 'status' => 'active']);

        $this->withToken($staff->createToken('test', ['admin'])->plainTextToken);

        return $staff;
    }

    /** Subscribes the given user to a plan so gated endpoints open up. */
    protected function subscribe(User $user, string $planSlug = 'gold'): User
    {
        $plan = MembershipPlan::firstWhere('slug', $planSlug);

        $user->subscriptions()->create([
            'membership_plan_id' => $plan->id,
            'starts_at' => now(),
            'expires_at' => now()->addDays($plan->duration_days),
            'status' => 'active',
            'features_snapshot' => $plan->features,
        ]);

        return $user->refresh();
    }

    /** Asserts the standard envelope rather than repeating it in every test. */
    protected function assertEnvelope(TestResponse $response, bool $success = true): TestResponse
    {
        $response->assertJsonStructure($success ? ['success', 'message', 'data'] : ['success', 'message']);
        $response->assertJsonPath('success', $success);

        return $response;
    }
}
