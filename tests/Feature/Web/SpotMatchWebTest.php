<?php

namespace Tests\Feature\Web;

use App\Models\GameMatch;
use App\Models\Puzzle;
use App\Models\PuzzleDifference;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\Concerns\PlaysGames;
use Tests\TestCase;

/**
 * The website's Spot the Difference pages referenced a policy, a form request,
 * a resource and three tables that did not exist. This pins the pages that now
 * work, so they do not regress into a 500 again.
 */
class SpotMatchWebTest extends TestCase
{
    use PlaysGames, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->bootGames();
    }

    public function test_the_challenge_flow_renders_and_only_participants_can_see_a_match(): void
    {
        Mail::fake();

        $puzzle = Puzzle::factory()->create();
        PuzzleDifference::factory()->create(['puzzle_id' => $puzzle->id]);

        [$a, $b] = $this->pair();
        $this->actingAs($a, 'web');

        $this->get('/spot-matches')->assertOk();
        $this->get('/spot-matches/create')->assertOk();
        $this->get('/spot-matches/leaderboard')->assertOk();

        $this->post('/spot-matches', ['puzzle_id' => $puzzle->id, 'invited_email' => $b->email])
            ->assertRedirect();

        $match = GameMatch::query()->latest('id')->firstOrFail();

        $this->get("/spot-matches/{$match->id}")->assertOk();
        $this->getJson("/spot-matches/{$match->id}/state")
            ->assertOk()
            ->assertJsonPath('data.id', $match->id)
            ->assertJsonPath('data.puzzle.total_differences', 1);

        $this->app['auth']->forgetGuards();
        $this->actingAs($this->member(), 'web');
        $this->get("/spot-matches/{$match->id}")->assertForbidden();
        $this->getJson("/spot-matches/{$match->id}/state")->assertForbidden();
    }
}
