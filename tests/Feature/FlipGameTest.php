<?php

namespace Tests\Feature;

use App\Models\FlipGame;
use App\Models\FlipSetting;
use App\Models\FlipTileImage;
use App\Services\FlipGameService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FlipGameTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed 16 active images
        for ($i = 1; $i <= 16; $i++) {
            FlipTileImage::create([
                'name' => "Image {$i}",
                'path' => "flip-tiles/test_{$i}.png",
                'is_active' => true,
            ]);
        }

        FlipSetting::instance();
    }

    public function test_board_has_16_tiles_with_8_pairs()
    {
        $service = app(FlipGameService::class);
        $game = $service->createGame('Groom', 'token_p1');

        $this->assertCount(16, $game->board);

        $counts = array_count_values($game->board);
        $this->assertCount(8, $counts);
        foreach ($counts as $imgId => $count) {
            $this->assertEquals(2, $count, "Image ID {$imgId} should appear exactly twice.");
        }
    }

    public function test_two_games_have_different_board_arrangements()
    {
        $service = app(FlipGameService::class);

        $game1 = $service->createGame('Player 1', 'token_1');
        $game2 = $service->createGame('Player 2', 'token_2');

        $this->assertNotEquals($game1->code, $game2->code);
        $this->assertNotEquals($game1->board, $game2->board);
    }

    public function test_creation_blocked_with_fewer_than_8_active_images()
    {
        FlipTileImage::query()->update(['is_active' => false]);
        FlipTileImage::limit(5)->update(['is_active' => true]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Minimum 8 active images required.');

        $service = app(FlipGameService::class);
        $service->createGame('Player 1', 'token_1');
    }

    public function test_flipping_out_of_turn_returns_403()
    {
        $service = app(FlipGameService::class);
        $game = $service->createGame('P1', 'token_p1');
        $service->join($game, 'P2', 'token_p2');
        $service->toggleReady($game, 'token_p1');
        $service->toggleReady($game, 'token_p2');

        $response = $this->withSession(['flip_player_token' => 'token_p2'])
            ->postJson("/flip/{$game->code}/flip", ['tile' => 0]);

        $response->assertStatus(403);
    }

    public function test_matching_increments_score_and_keeps_turn()
    {
        $service = app(FlipGameService::class);
        $game = $service->createGame('P1', 'token_p1');
        $service->join($game, 'P2', 'token_p2');
        $service->toggleReady($game, 'token_p1');
        $service->toggleReady($game, 'token_p2');

        // Find a matching pair in board
        $board = $game->board;
        $firstId = $board[0];
        $secondIndex = null;
        for ($i = 1; $i < 16; $i++) {
            if ($board[$i] === $firstId) {
                $secondIndex = $i;
                break;
            }
        }

        // First flip (Player 1)
        $res1 = $this->withSession(['flip_player_token' => 'token_p1'])
            ->postJson("/flip/{$game->code}/flip", ['tile' => 0]);
        $res1->assertStatus(200);

        // Second flip (Player 2)
        $res2 = $this->withSession(['flip_player_token' => 'token_p2'])
            ->postJson("/flip/{$game->code}/flip", ['tile' => $secondIndex]);
        $res2->assertStatus(200);
        $res2->assertJson(['matched' => true]);

        $game->refresh();
        $this->assertEquals(1, $game->p2_score);
    }

    public function test_mismatch_switches_turn_after_delay()
    {
        $service = app(FlipGameService::class);
        $game = $service->createGame('P1', 'token_p1');
        $service->join($game, 'P2', 'token_p2');
        $service->toggleReady($game, 'token_p1');
        $service->toggleReady($game, 'token_p2');

        // Find mismatch pair
        $board = $game->board;
        $firstId = $board[0];
        $secondIndex = null;
        for ($i = 1; $i < 16; $i++) {
            if ($board[$i] !== $firstId) {
                $secondIndex = $i;
                break;
            }
        }

        // Flip first tile (Player 1)
        $this->withSession(['flip_player_token' => 'token_p1'])
            ->postJson("/flip/{$game->code}/flip", ['tile' => 0]);

        // Flip second tile (Player 2 mismatch)
        $res2 = $this->withSession(['flip_player_token' => 'token_p2'])
            ->postJson("/flip/{$game->code}/flip", ['tile' => $secondIndex]);
        $res2->assertStatus(200);
        $res2->assertJson(['matched' => false]);

        $game->refresh();
        $this->assertNotNull($game->mismatch_until);
    }

    public function test_preview_works_only_once()
    {
        $service = app(FlipGameService::class);
        $game = $service->createGame('P1', 'token_p1');
        $service->join($game, 'P2', 'token_p2');
        $service->toggleReady($game, 'token_p1');
        $service->toggleReady($game, 'token_p2');

        // First preview
        $res1 = $this->withSession(['flip_player_token' => 'token_p1'])
            ->postJson("/flip/{$game->code}/preview");
        $res1->assertStatus(200);

        // Second preview attempt
        $res2 = $this->withSession(['flip_player_token' => 'token_p1'])
            ->postJson("/flip/{$game->code}/preview");
        $res2->assertStatus(409);
    }

    public function test_game_finishes_after_8_matches()
    {
        $service = app(FlipGameService::class);
        $game = $service->createGame('P1', 'token_p1');
        $service->join($game, 'P2', 'token_p2');
        $service->toggleReady($game, 'token_p1');
        $service->toggleReady($game, 'token_p2');

        $board = $game->board;
        // Group indices by image ID
        $pairs = [];
        foreach ($board as $idx => $imgId) {
            $pairs[$imgId][] = $idx;
        }

        // Match all 8 pairs (Current turn player opens 1st tile, opponent opens 2nd tile)
        foreach ($pairs as $imgId => $indices) {
            $t1 = $game->turn === 1 ? 'token_p1' : 'token_p2';
            $t2 = $t1 === 'token_p1' ? 'token_p2' : 'token_p1';
            $service->flip($game, $indices[0], $t1);
            $service->flip($game, $indices[1], $t2);
            $game->refresh();
        }

        $this->assertEquals('finished', $game->status);
        $this->assertEquals(8, $game->p1_score + $game->p2_score);
    }

    public function test_players_must_click_ready_before_game_starts()
    {
        $service = app(FlipGameService::class);
        $game = $service->createGame('P1', 'token_p1');
        $service->join($game, 'P2', 'token_p2');

        $game->refresh();
        $this->assertEquals('waiting', $game->status);

        // Player 1 ready
        $service->toggleReady($game, 'token_p1');
        $game->refresh();
        $this->assertTrue($game->p1_ready);
        $this->assertEquals('waiting', $game->status);

        // Player 2 ready -> changes status to playing
        $service->toggleReady($game, 'token_p2');
        $game->refresh();
        $this->assertTrue($game->p2_ready);
        $this->assertEquals('playing', $game->status);
    }
}
