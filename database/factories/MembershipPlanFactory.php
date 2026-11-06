<?php

namespace Database\Factories;

use App\Models\MembershipPlan;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<MembershipPlan>
 */
class MembershipPlanFactory extends Factory
{
    protected $model = MembershipPlan::class;

    public function definition(): array
    {
        $name = fake()->unique()->word();

        return [
            'name' => ucfirst($name),
            'slug' => Str::slug($name),
            'price' => fake()->randomElement([999, 2999, 4999, 7999]),
            'currency' => 'INR',
            'duration_days' => fake()->randomElement([30, 90, 180, 365]),
            'features' => [
                'interests_per_month' => 50,
                'profile_views_per_day' => 100,
                'contact_views_per_month' => 25,
                'photo_limit' => 10,
                'can_chat' => true,
                'can_view_contact' => true,
                'can_see_profile_viewers' => true,
                'advanced_search' => true,
                'featured_profile' => false,
                'priority_matching' => true,
                'support_priority' => 'standard',
            ],
            'is_active' => true,
            'is_default' => false,
        ];
    }

    /** The free tier: every capability off, small quotas, never expires. */
    public function free(): static
    {
        return $this->state(fn () => [
            'name' => 'Free',
            'slug' => 'free',
            'price' => 0,
            'duration_days' => null,
            'is_default' => true,
            'features' => [
                'interests_per_month' => 5,
                'profile_views_per_day' => 10,
                'contact_views_per_month' => 0,
                'photo_limit' => 3,
                'can_chat' => false,
                'can_view_contact' => false,
                'can_see_profile_viewers' => false,
                'advanced_search' => false,
                'featured_profile' => false,
                'priority_matching' => false,
                'support_priority' => 'standard',
            ],
        ]);
    }
}
