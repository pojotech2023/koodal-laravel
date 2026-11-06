<?php

namespace Database\Factories;

use App\Models\City;
use App\Models\Community;
use App\Models\Education;
use App\Models\IncomeRange;
use App\Models\MaritalStatus;
use App\Models\MotherTongue;
use App\Models\Occupation;
use App\Models\Religion;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UserProfile>
 */
class UserProfileFactory extends Factory
{
    protected $model = UserProfile::class;

    public function definition(): array
    {
        $city = City::inRandomOrder()->first();
        $religion = Religion::inRandomOrder()->first();

        return [
            'user_id' => User::factory(),
            'profile_created_by' => fake()->randomElement(['self', 'parent', 'sibling', 'relative']),
            'height_cm' => fake()->numberBetween(150, 190),
            'weight_kg' => fake()->numberBetween(45, 95),
            'marital_status_id' => MaritalStatus::inRandomOrder()->value('id'),
            'physical_status' => 'normal',

            'religion_id' => $religion?->id,
            'community_id' => $religion
                ? Community::where('religion_id', $religion->id)->inRandomOrder()->value('id')
                : null,
            'mother_tongue_id' => MotherTongue::inRandomOrder()->value('id'),
            'manglik' => fake()->randomElement(['yes', 'no', 'dont_know']),

            'education_id' => Education::inRandomOrder()->value('id'),
            'occupation_id' => Occupation::inRandomOrder()->value('id'),
            'income_range_id' => IncomeRange::inRandomOrder()->value('id'),
            'job_type' => fake()->randomElement(['govt', 'private', 'business', 'self_employed']),

            'country_id' => $city?->state?->country_id,
            'state_id' => $city?->state_id,
            'city_id' => $city?->id,

            'family_type' => fake()->randomElement(['joint', 'nuclear']),
            'family_status' => fake()->randomElement(['middle_class', 'upper_middle', 'rich']),
            'family_values' => fake()->randomElement(['traditional', 'moderate', 'liberal']),

            'diet' => fake()->randomElement(['vegetarian', 'non-vegetarian', 'eggetarian']),
            'smoking' => fake()->randomElement(['no', 'occasionally']),
            'drinking' => fake()->randomElement(['no', 'occasionally']),

            'about_me' => fake()->paragraph(3),
            'expectations' => fake()->paragraph(2),

            'completion_percentage' => fake()->numberBetween(40, 100),
            'approval_status' => 'approved',
            'is_verified' => fake()->boolean(30),
        ];
    }

    public function pending(): static
    {
        return $this->state(fn () => ['approval_status' => 'pending']);
    }

    public function verified(): static
    {
        return $this->state(fn () => ['is_verified' => true, 'verified_at' => now()]);
    }
}
