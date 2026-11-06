<?php

namespace Database\Factories;

use App\Models\Puzzle;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Puzzle>
 */
class PuzzleFactory extends Factory
{
    protected $model = Puzzle::class;

    public function definition(): array
    {
        return [
            'title' => $this->faker->sentence(3),
            'original_image' => 'puzzles/sample_original.png',
            'modified_image' => 'puzzles/sample_modified.png',
            'width' => 800,
            'height' => 600,
            'difficulty' => $this->faker->randomElement(['easy', 'medium', 'hard']),
            'time_limit_seconds' => 180,
            'is_active' => true,
        ];
    }
}
