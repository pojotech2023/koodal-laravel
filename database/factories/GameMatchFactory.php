<?php

namespace Database\Factories;

use App\Models\GameMatch;
use App\Models\Puzzle;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\GameMatch>
 */
class GameMatchFactory extends Factory
{
    protected $model = GameMatch::class;

    public function definition(): array
    {
        return [
            'puzzle_id' => Puzzle::factory(),
            'host_id' => User::factory(),
            'status' => 'waiting',
            'started_at' => null,
            'ends_at' => null,
            'finished_at' => null,
            'winner_id' => null,
        ];
    }
}
