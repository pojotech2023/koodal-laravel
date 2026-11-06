<?php

namespace Database\Factories;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected $model = User::class;

    public function definition(): array
    {
        $gender = fake()->randomElement(['male', 'female']);

        return [
            // Sequence-free and collision-free: factories run inside
            // transactions where a MAX(id) lookup would see uncommitted state.
            'profile_code' => 'MAT'.now()->format('Y').Str::padLeft((string) fake()->unique()->numberBetween(1, 999_999), 6, '0'),
            'name' => $gender === 'male' ? fake()->name('male') : fake()->name('female'),
            'email' => fake()->unique()->safeEmail(),
            'mobile' => '+919'.fake()->unique()->numerify('#########'),
            'password' => 'Password@123',
            'gender' => $gender,
            'date_of_birth' => fake()->dateTimeBetween('-40 years', '-22 years')->format('Y-m-d'),
            'role_id' => fn () => Role::firstWhere('name', 'user')?->id ?? Role::factory(),
            'status' => 'active',
            'mobile_verified_at' => now(),
            'last_active_at' => fake()->dateTimeBetween('-30 days', 'now'),
            'registration_source' => fake()->randomElement(['web', 'android', 'ios']),
        ];
    }

    public function male(): static
    {
        return $this->state(fn () => ['gender' => 'male', 'name' => fake()->name('male')]);
    }

    public function female(): static
    {
        return $this->state(fn () => ['gender' => 'female', 'name' => fake()->name('female')]);
    }

    public function pending(): static
    {
        return $this->state(fn () => ['status' => 'pending', 'mobile_verified_at' => null]);
    }

    public function suspended(): static
    {
        return $this->state(fn () => ['status' => 'suspended', 'suspension_reason' => 'Policy violation']);
    }

    public function staff(string $role = 'admin'): static
    {
        return $this->state(fn () => ['role_id' => Role::firstWhere('name', $role)?->id]);
    }

    public function aged(int $years): static
    {
        return $this->state(fn () => ['date_of_birth' => now()->subYears($years)->subDays(30)->toDateString()]);
    }
}
