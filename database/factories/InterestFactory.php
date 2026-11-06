<?php

namespace Database\Factories;

use App\Models\Interest;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Interest>
 */
class InterestFactory extends Factory
{
    protected $model = Interest::class;

    public function definition(): array
    {
        return [
            'sender_id' => User::factory(),
            'receiver_id' => User::factory(),
            'status' => 'pending',
            'message' => fake()->optional()->sentence(),
        ];
    }

    public function accepted(): static
    {
        return $this->state(fn () => ['status' => 'accepted', 'responded_at' => now()]);
    }

    public function declined(): static
    {
        return $this->state(fn () => ['status' => 'declined', 'responded_at' => now()]);
    }
}
