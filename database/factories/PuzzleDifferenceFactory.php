<?php

namespace Database\Factories;

use App\Models\Puzzle;
use App\Models\PuzzleDifference;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PuzzleDifference>
 */
class PuzzleDifferenceFactory extends Factory
{
    protected $model = PuzzleDifference::class;

    public function definition(): array
    {
        return [
            'puzzle_id' => Puzzle::factory(),
            'label' => $this->faker->word(),
            'x_pct' => $this->faker->randomFloat(4, 10, 90),
            'y_pct' => $this->faker->randomFloat(4, 10, 90),
            'radius_pct' => 5.0,
            'animation_type' => $this->faker->randomElement(['fly_away', 'fall', 'swing_open', 'drift', 'pulse', 'blink', 'fade_out']),
            'animate_on' => $this->faker->randomElement(['original', 'modified']),
            'sprite_path' => null,
            'lottie_path' => null,
            'sort_order' => 0,
        ];
    }
}
