<?php

namespace Database\Seeders;

use App\Models\MembershipPlan;
use Illuminate\Database\Seeder;

/**
 * Starting points for the business, not constants in code. Everything here is
 * editable in the admin panel, and FeatureGate reads whatever is in the row.
 *
 * -1 means unlimited.
 */
class MembershipPlanSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            [
                'name' => 'Free',
                'slug' => 'free',
                'price' => 0,
                'duration_days' => null,     // never expires
                'is_default' => true,        // exactly one row carries this
                'sort_order' => 1,
                'description' => 'Create a profile and start exploring.',
                'features' => [
                    'interests_per_month' => 5,
                    'profile_views_per_day' => 20,
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
            ],
            [
                'name' => 'Silver',
                'slug' => 'silver',
                'price' => 1999,
                'duration_days' => 90,
                'sort_order' => 2,
                'description' => 'More reach, more conversations.',
                'features' => [
                    'interests_per_month' => 30,
                    'profile_views_per_day' => 100,
                    'contact_views_per_month' => 10,
                    'photo_limit' => 6,
                    'can_chat' => true,
                    'can_view_contact' => true,
                    'can_see_profile_viewers' => false,
                    'advanced_search' => true,
                    'featured_profile' => false,
                    'priority_matching' => false,
                    'support_priority' => 'standard',
                ],
            ],
            [
                'name' => 'Gold',
                'slug' => 'gold',
                'price' => 4999,
                'duration_days' => 90,
                'is_featured' => true,
                'badge_text' => 'Most popular',
                'sort_order' => 3,
                'description' => 'Full contact access and priority matches.',
                'features' => [
                    'interests_per_month' => 100,
                    'profile_views_per_day' => 300,
                    'contact_views_per_month' => 50,
                    'photo_limit' => 10,
                    'can_chat' => true,
                    'can_view_contact' => true,
                    'can_see_profile_viewers' => true,
                    'advanced_search' => true,
                    'featured_profile' => false,
                    'priority_matching' => true,
                    'support_priority' => 'standard',
                ],
            ],
            [
                'name' => 'Premium',
                'slug' => 'premium',
                'price' => 9999,
                'duration_days' => 180,
                'sort_order' => 4,
                'description' => 'Maximum visibility and unlimited communication.',
                'features' => [
                    'interests_per_month' => -1,
                    'profile_views_per_day' => -1,
                    'contact_views_per_month' => -1,
                    'photo_limit' => 20,
                    'can_chat' => true,
                    'can_view_contact' => true,
                    'can_see_profile_viewers' => true,
                    'advanced_search' => true,
                    'featured_profile' => true,
                    'priority_matching' => true,
                    'support_priority' => 'priority',
                ],
            ],
        ];

        foreach ($plans as $plan) {
            MembershipPlan::updateOrCreate(['slug' => $plan['slug']], $plan);
        }
    }
}
