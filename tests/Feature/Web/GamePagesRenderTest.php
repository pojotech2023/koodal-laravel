<?php

namespace Tests\Feature\Web;

use App\Models\GameRoom;
use App\Models\MemorySnapGame;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\PlaysGames;
use Tests\TestCase;

/**
 * Every game page the security pass touched still renders for its players.
 */
class GamePagesRenderTest extends TestCase
{
    use PlaysGames, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->bootGames();
        $this->withHeaders(['Accept' => 'text/html']);
    }

    protected function as(User $user): void
    {
        $this->app['auth']->forgetGuards();
        $this->actingAs($user, 'web');
    }

    public function test_the_partner_game_pages_render(): void
    {
        $this->seedSyncQuestions();
        [$a, $b] = $this->pair();
        $this->as($a);

        $pages = [
            'hub' => "/games/{$b->id}",
            'sync' => "/games/{$b->id}/sync",
            'find_it' => "/games/{$b->id}/find-it",
            'memory' => "/games/{$b->id}/memory",
            'spot' => "/games/{$b->id}/spot",
            'vibe' => "/games/{$b->id}/vibe",
        ];

        $this->postJson("/games/{$b->id}/invite", ['game_type' => 'sync'])->assertOk();
        $pages['live_sync'] = '/games/room/'.GameRoom::where('game_type', 'sync')->latest('id')->value('room_code');
        $this->postJson("/games/{$b->id}/invite", ['game_type' => 'find_it'])->assertOk();
        $pages['live_find_it'] = '/games/room/'.GameRoom::where('game_type', 'find_it')->latest('id')->value('room_code');

        $this->get("/memory-snap/start/{$b->id}")->assertRedirect();
        $pages['memory_snap'] = '/memory-snap/room/'.MemorySnapGame::latest('id')->value('code');
        $this->get('/memory-snap/start')->assertRedirect();
        $pages['memory_snap_open'] = '/memory-snap/room/'.MemorySnapGame::latest('id')->value('code');

        $this->postJson("/games/{$b->id}/invite", ['game_type' => 'flip'])->assertOk();
        $pages['flip'] = '/flip/'.\App\Models\FlipGame::latest('id')->value('code');

        // A non-numeric partner is a 404, not a type error from the controller.
        $this->get('/games/not-a-member')->assertNotFound();

        foreach ($pages as $name => $url) {
            $this->get($url)->assertOk();
        }
    }
}
