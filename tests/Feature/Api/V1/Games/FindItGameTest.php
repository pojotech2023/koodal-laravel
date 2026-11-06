<?php

namespace Tests\Feature\Api\V1\Games;

use App\Models\GameRoom;
use App\Models\KoodalGameScore;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\PlaysGames;
use Tests\TestCase;

class FindItGameTest extends TestCase
{
    use PlaysGames, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->bootGames();
    }

    /** @return array{0: User, 1: User, 2: string} */
    protected function room(bool $start = true): array
    {
        [$a, $b] = $this->pair();
        $this->actingAsMember($a);
        $code = $this->postJson("/api/v1/games/partners/{$b->profile_code}/invites", ['game_type' => 'find_it'])
            ->json('data.room.code');

        if ($start) {
            $this->postJson("/api/v1/games/rooms/find_it/{$code}/start")->assertOk();
        }

        return [$a, $b, $code];
    }

    protected function claim(User $user, string $code, int $index)
    {
        $this->actAs($user);

        return $this->postJson("/api/v1/games/rooms/find_it/{$code}/claim", ['difference_index' => $index]);
    }

    public function test_the_lobby_serves_a_challenge_with_seven_hotspots(): void
    {
        [, , $code] = $this->room(start: false);

        $game = $this->getJson("/api/v1/games/rooms/find_it/{$code}")->assertOk()->json('data.game');

        $this->assertCount(7, $game['differences']);
        $this->assertSame(7, $game['challenge']['total']);
        $this->assertStringStartsWith('http', $game['challenge']['image_a']);
        // Untimed by default: no limit and no countdown to draw.
        $this->assertNull($game['time_limit_seconds']);
        $this->assertNull($game['seconds_left']);
    }

    public function test_an_untimed_round_never_runs_out(): void
    {
        [$a, $b, $code] = $this->room();
        $this->claim($a, $code, 0)->assertOk();

        GameRoom::where('room_code', $code)->update(['started_at' => now()->subHours(2)]);

        $state = $this->getJson("/api/v1/games/rooms/find_it/{$code}")->json('data');
        $this->assertSame('playing', $state['status']);
        $this->assertNull($state['game']['seconds_left']);

        $this->claim($b, $code, 1)->assertOk()->assertJsonPath('data.result', 'found');
    }

    public function test_the_room_reports_when_it_went_live_for_the_lead_in(): void
    {
        [, , $code] = $this->room(start: false);
        $this->getJson("/api/v1/games/rooms/find_it/{$code}")->assertOk()->assertJsonPath('data.started_at', null);

        $this->postJson("/api/v1/games/rooms/find_it/{$code}/start")->assertOk();
        $started = $this->getJson("/api/v1/games/rooms/find_it/{$code}")->json('data.started_at');

        $this->assertIsInt($started);
        $this->assertEqualsWithDelta(now()->getTimestamp(), $started, 5);
    }

    public function test_claims_are_refused_until_the_game_starts(): void
    {
        [$a, , $code] = $this->room(start: false);

        $this->claim($a, $code, 0)->assertStatus(409);
    }

    public function test_a_difference_belongs_to_whoever_found_it_first(): void
    {
        [$a, $b, $code] = $this->room();

        $this->claim($a, $code, 2)->assertOk()->assertJsonPath('data.result', 'found');
        $this->claim($a, $code, 2)->assertOk()->assertJsonPath('data.result', 'already_found');
        $this->claim($b, $code, 2)->assertOk()->assertJsonPath('data.result', 'claimed_by_partner');

        $state = $this->getJson("/api/v1/games/rooms/find_it/{$code}")->json('data.game');
        $this->assertSame(1, $state['partner_count']);
        $this->assertSame(0, $state['my_count']);
        $this->assertSame([['index' => 2, 'by' => 'partner']], $state['found']);
    }

    public function test_claims_stored_as_strings_by_older_website_rooms_still_count(): void
    {
        [$a, $b, $code] = $this->room();
        GameRoom::where('room_code', $code)->firstOrFail()->update(['user_a_answers' => ['2'], 'user_b_answers' => []]);

        $this->claim($a, $code, 2)->assertOk()->assertJsonPath('data.result', 'already_found');
        $this->claim($b, $code, 2)->assertOk()->assertJsonPath('data.result', 'claimed_by_partner');
    }

    public function test_an_out_of_range_difference_is_rejected(): void
    {
        [$a, , $code] = $this->room();

        $this->claim($a, $code, 7)->assertStatus(422);
        $this->claim($a, $code, -1)->assertStatus(422);
    }

    public function test_a_stranger_cannot_claim(): void
    {
        [, , $code] = $this->room();

        $this->claim($this->member(), $code, 0)->assertForbidden();
    }

    public function test_finding_all_seven_completes_the_game_and_scores_teamwork_server_side(): void
    {
        [$a, $b, $code] = $this->room();

        foreach ([0, 1, 2, 3] as $i) {
            $this->claim($a, $code, $i)->assertOk();
        }
        foreach ([4, 5, 6] as $i) {
            $this->claim($b, $code, $i)->assertOk();
        }

        $this->actAs($a);
        $game = $this->getJson("/api/v1/games/rooms/find_it/{$code}");
        $this->assertSame('completed', $game->json('data.status'));
        // 4 vs 3 is a difference of one: full balance, full completion.
        $this->assertSame(100, $game->json('data.game.score'));
        $this->assertSame(2, KoodalGameScore::where('game_type', 'find_it')->count());
    }

    public function test_a_lopsided_hunt_scores_lower(): void
    {
        [$a, , $code] = $this->room();

        foreach (range(0, 6) as $i) {
            $this->claim($a, $code, $i)->assertOk();
        }

        // 7 vs 0 → balance 5.
        $this->assertSame(5, $this->getJson("/api/v1/games/rooms/find_it/{$code}")->json('data.game.score'));
    }

    public function test_a_configured_clock_is_enforced_by_the_server(): void
    {
        config(['game.timing.find_it_limit' => 60]);
        [$a, $b, $code] = $this->room();
        $this->assertSame(60, $this->getJson("/api/v1/games/rooms/find_it/{$code}")->json('data.game.time_limit_seconds'));
        $this->claim($a, $code, 0)->assertOk();

        GameRoom::where('room_code', $code)->update(['started_at' => now()->subSeconds(120)]);

        $state = $this->getJson("/api/v1/games/rooms/find_it/{$code}")->json('data');
        $this->assertSame('completed', $state['status']);
        // One of seven found, perfectly "balanced" → 100 × 1/7.
        $this->assertSame(14, $state['game']['score']);

        $this->claim($b, $code, 1)->assertStatus(409);
    }

    public function test_a_claim_after_time_is_up_ends_the_game_instead(): void
    {
        config(['game.timing.find_it_limit' => 60]);
        [$a, , $code] = $this->room();
        GameRoom::where('room_code', $code)->update(['started_at' => now()->subSeconds(120)]);

        $this->claim($a, $code, 0)->assertOk()->assertJsonPath('data.result', 'time_up')
            ->assertJsonPath('data.status', 'completed');
    }

    public function test_reset_picks_a_new_round(): void
    {
        [$a, , $code] = $this->room();
        $this->claim($a, $code, 0)->assertOk();

        $this->postJson("/api/v1/games/rooms/find_it/{$code}/reset")->assertOk()
            ->assertJsonPath('data.status', 'lobby')
            ->assertJsonPath('data.game.my_count', 0);
    }
}
