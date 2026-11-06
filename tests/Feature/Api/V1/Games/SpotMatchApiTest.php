<?php

namespace Tests\Feature\Api\V1\Games;

use App\Models\GameMatch;
use App\Models\MatchClaim;
use App\Models\MatchPlayer;
use App\Models\Puzzle;
use App\Models\PuzzleDifference;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\PlaysGames;
use Tests\TestCase;

class SpotMatchApiTest extends TestCase
{
    use PlaysGames, RefreshDatabase;

    protected Puzzle $puzzle;

    protected function setUp(): void
    {
        parent::setUp();
        $this->bootGames();

        $this->puzzle = Puzzle::factory()->create(['width' => 800, 'height' => 600, 'time_limit_seconds' => 120]);
        foreach ([[20, 20], [50, 20], [80, 20], [20, 70]] as $i => [$x, $y]) {
            PuzzleDifference::factory()->create([
                'puzzle_id' => $this->puzzle->id, 'label' => "Diff {$i}",
                'x_pct' => $x, 'y_pct' => $y, 'radius_pct' => 5, 'sort_order' => $i,
            ]);
        }
    }

    /** @return array{0: User, 1: User, 2: int} */
    protected function challenge(): array
    {
        [$a, $b] = $this->pair();
        $this->actingAsMember($a);
        $id = $this->postJson('/api/v1/games/spot/matches', [
            'puzzle_id' => $this->puzzle->id, 'opponent_profile_code' => $b->profile_code,
        ])->assertCreated()->json('data.id');

        return [$a, $b, $id];
    }

    /** Both ready and past the 3-second lead-in. */
    protected function startRound(User $a, User $b, int $id): void
    {
        $this->actAs($b);
        $this->postJson("/api/v1/games/spot/matches/{$id}/ready")->assertOk();
        GameMatch::whereKey($id)->update(['started_at' => now()->subSecond()]);
        $this->actAs($a);
    }

    protected function tap(User $user, int $id, float $x, float $y)
    {
        $this->actAs($user);

        return $this->postJson("/api/v1/games/spot/matches/{$id}/claim", ['x_pct' => $x, 'y_pct' => $y]);
    }

    public function test_puzzles_are_listed_without_revealing_any_coordinates(): void
    {
        $this->actingAsMember();

        $response = $this->getJson('/api/v1/games/spot/puzzles')->assertOk();

        $this->assertSame(4, $response->json('data.0.total_differences'));
        $this->assertStringNotContainsString('x_pct', $response->getContent());
    }

    public function test_a_challenge_seats_both_players_and_notifies_the_opponent(): void
    {
        [$a, $b, $id] = $this->challenge();

        $this->assertDatabaseHas('match_players', ['match_id' => $id, 'user_id' => $a->id, 'status' => 'ready']);
        $this->assertDatabaseHas('match_players', ['match_id' => $id, 'user_id' => $b->id, 'status' => 'invited']);
        $this->assertDatabaseHas('notifications', ['user_id' => $b->id, 'type' => 'game_invite']);

        $this->actAs($b);
        $mine = $this->getJson('/api/v1/games/spot/matches')->assertOk();
        $this->assertSame($id, $mine->json('data.0.id'));
        $this->assertSame($a->profile_code, $mine->json('data.0.opponent.profile_code'));
    }

    public function test_you_cannot_challenge_yourself_a_blocked_member_or_use_an_inactive_puzzle(): void
    {
        [$a, $b] = $this->pair();
        $this->actingAsMember($a);
        $body = ['puzzle_id' => $this->puzzle->id, 'opponent_profile_code' => $a->profile_code];

        $this->postJson('/api/v1/games/spot/matches', $body)->assertNotFound();

        $b->blocks()->create(['blocked_user_id' => $a->id]);
        $this->postJson('/api/v1/games/spot/matches', ['opponent_profile_code' => $b->profile_code] + $body)->assertNotFound();

        $this->puzzle->update(['is_active' => false]);
        [, $c] = $this->pair();
        $this->postJson('/api/v1/games/spot/matches', ['opponent_profile_code' => $c->profile_code] + $body)->assertNotFound();
    }

    public function test_only_participants_can_see_or_act_on_a_match(): void
    {
        [, , $id] = $this->challenge();
        $this->actAs($this->member());

        $this->getJson("/api/v1/games/spot/matches/{$id}")->assertForbidden();
        $this->postJson("/api/v1/games/spot/matches/{$id}/ready")->assertForbidden();
        $this->postJson("/api/v1/games/spot/matches/{$id}/claim", ['x_pct' => 20, 'y_pct' => 20])->assertForbidden();
        $this->postJson("/api/v1/games/spot/matches/{$id}/cancel")->assertForbidden();
    }

    public function test_the_match_state_never_contains_unfound_hotspots(): void
    {
        [, , $id] = $this->challenge();

        $state = $this->getJson("/api/v1/games/spot/matches/{$id}")->assertOk()->json('data');

        $this->assertSame(4, $state['puzzle']['total_differences']);
        $this->assertSame(0, $state['puzzle']['found_differences_count']);
        $this->assertSame([], $state['claims']);
        $this->assertCount(2, $state['players']);
        $this->assertTrue(collect($state['players'])->firstWhere('is_me', true)['status'] === 'ready');
    }

    public function test_the_round_starts_with_a_lead_in_and_taps_are_refused_until_it_ends(): void
    {
        [$a, $b, $id] = $this->challenge();

        $this->actAs($b);
        $this->postJson("/api/v1/games/spot/matches/{$id}/ready")->assertOk()->assertJsonPath('data.status', 'playing');

        $lead = $this->getJson("/api/v1/games/spot/matches/{$id}")->json('data');
        $this->assertGreaterThan(0, $lead['starts_in']);

        $this->tap($b, $id, 20, 20)->assertOk()->assertJsonPath('data.result', 'not_started');
    }

    public function test_tapping_a_hotspot_scores_it_and_the_opponent_cannot_take_it_again(): void
    {
        [$a, $b, $id] = $this->challenge();
        $this->startRound($a, $b, $id);

        $hit = $this->tap($a, $id, 21, 19)->assertOk();
        $this->assertSame('correct', $hit->json('data.result'));
        $this->assertGreaterThanOrEqual(100, $hit->json('data.points'));
        $this->assertSame('Diff 0', $hit->json('data.difference.label'));
        $this->assertSame(1, $hit->json('data.match.puzzle.found_differences_count'));
        $this->assertTrue($hit->json('data.match.claims.0.is_me'));

        $this->tap($b, $id, 20, 20)->assertOk()->assertJsonPath('data.result', 'wrong');
    }

    public function test_a_wrong_tap_costs_points_and_starts_a_cooldown(): void
    {
        [$a, $b, $id] = $this->challenge();
        $this->startRound($a, $b, $id);

        $miss = $this->tap($a, $id, 95, 95)->assertOk();
        $this->assertSame('wrong', $miss->json('data.result'));
        $this->assertSame(-20, $miss->json('data.penalty'));
        $this->assertSame(2, $miss->json('data.cooldown_seconds'));

        $this->tap($a, $id, 20, 20)->assertOk()->assertJsonPath('data.result', 'cooldown');
        $this->assertSame(0, MatchPlayer::where('match_id', $id)->where('user_id', $a->id)->value('score'));
    }

    public function test_the_cooldown_ends_and_the_player_can_tap_again(): void
    {
        [$a, $b, $id] = $this->challenge();
        $this->startRound($a, $b, $id);

        $this->tap($a, $id, 95, 95)->assertJsonPath('data.result', 'wrong');

        $this->travel(1)->seconds();
        $this->tap($a, $id, 20, 20)->assertJsonPath('data.result', 'cooldown')
            ->assertJsonPath('data.cooldown_remaining', 1);

        $this->travel(2)->seconds();
        $this->tap($a, $id, 20, 20)->assertJsonPath('data.result', 'correct');
    }

    public function test_a_blocked_opponent_cannot_get_ready_or_tap(): void
    {
        [$a, $b, $id] = $this->challenge();
        $this->startRound($a, $b, $id);
        $b->blocks()->create(['blocked_user_id' => $a->id]);

        $this->tap($a, $id, 20, 20)->assertForbidden();
        $this->actAs($a);
        $this->postJson("/api/v1/games/spot/matches/{$id}/ready")->assertForbidden();
        $this->getJson("/api/v1/games/spot/matches/{$id}")->assertOk();
    }

    public function test_the_hit_test_corrects_for_the_pictures_aspect_ratio(): void
    {
        [$a, $b, $id] = $this->challenge();
        $this->startRound($a, $b, $id);

        // 5% radius. 6% away vertically is only 4.5% in picture space (600/800).
        $this->tap($a, $id, 20, 26)->assertOk()->assertJsonPath('data.result', 'correct');
        // 8% away horizontally is outside.
        $this->actAs($b);
        $this->tap($b, $id, 58, 20)->assertOk()->assertJsonPath('data.result', 'wrong');
    }

    public function test_finding_every_difference_ends_the_match_and_names_a_winner(): void
    {
        [$a, $b, $id] = $this->challenge();
        $this->startRound($a, $b, $id);

        $this->tap($a, $id, 20, 20)->assertOk();
        $this->tap($a, $id, 50, 20)->assertOk();
        $this->tap($b, $id, 80, 20)->assertOk();
        $last = $this->tap($a, $id, 20, 70)->assertOk();

        $this->assertSame('finished', $last->json('data.match.status'));
        $this->assertSame($a->id, $last->json('data.match.winner_id'));

        $this->actAs($a);
        $list = $this->getJson('/api/v1/games/spot/matches')->json('data.0');
        $this->assertSame('won', $list['result']);

        $this->tap($b, $id, 20, 20)->assertOk()->assertJsonPath('data.result', 'not_playing');
    }

    public function test_an_expired_round_is_finished_when_next_read(): void
    {
        [$a, $b, $id] = $this->challenge();
        $this->startRound($a, $b, $id);
        $this->tap($a, $id, 20, 20)->assertOk();

        GameMatch::whereKey($id)->update(['ends_at' => now()->subSecond()]);

        $state = $this->getJson("/api/v1/games/spot/matches/{$id}")->assertOk()->json('data');
        $this->assertSame('finished', $state['status']);
        $this->assertSame($a->id, $state['winner_id']);
        $this->assertSame(0, $state['seconds_left']);
    }

    public function test_ready_cannot_restart_a_round_in_progress(): void
    {
        [$a, $b, $id] = $this->challenge();
        $this->startRound($a, $b, $id);
        $endsAt = GameMatch::find($id)->ends_at;

        $this->postJson("/api/v1/games/spot/matches/{$id}/ready")->assertOk()->assertJsonPath('data.status', 'playing');

        $this->assertEquals($endsAt, GameMatch::find($id)->ends_at);
    }

    public function test_cancel_works_in_the_lobby_but_not_mid_round(): void
    {
        [$a, $b, $id] = $this->challenge();

        $this->actAs($b);
        $this->postJson("/api/v1/games/spot/matches/{$id}/cancel")->assertOk()->assertJsonPath('data.status', 'cancelled');

        [, , $second] = $this->challenge();
        $match = GameMatch::findOrFail($second);
        $player = $match->players()->where('user_id', '!=', $match->host_id)->firstOrFail();
        $this->startRound(User::find($match->host_id), User::find($player->user_id), $second);
        $this->postJson("/api/v1/games/spot/matches/{$second}/cancel")->assertForbidden();
    }

    public function test_a_rematch_challenges_the_same_opponent_on_the_same_puzzle(): void
    {
        [$a, $b, $id] = $this->challenge();
        $this->startRound($a, $b, $id);
        $this->postJson("/api/v1/games/spot/matches/{$id}/rematch")->assertStatus(409);

        foreach ([[20, 20], [50, 20], [80, 20], [20, 70]] as [$x, $y]) {
            $this->tap($a, $id, $x, $y)->assertOk();
        }

        $this->actAs($a);
        $new = $this->postJson("/api/v1/games/spot/matches/{$id}/rematch")->assertCreated();

        $this->assertNotSame($id, $new->json('data.id'));
        $this->assertSame($this->puzzle->id, $new->json('data.puzzle.id'));
        $this->assertDatabaseHas('match_players', ['match_id' => $new->json('data.id'), 'user_id' => $b->id]);
    }

    public function test_the_leaderboard_ranks_by_wins(): void
    {
        [$a, $b, $id] = $this->challenge();
        $this->startRound($a, $b, $id);
        foreach ([[20, 20], [50, 20], [80, 20], [20, 70]] as [$x, $y]) {
            $this->tap($a, $id, $x, $y)->assertOk();
        }

        $this->actAs($b);
        $board = $this->getJson('/api/v1/games/spot/leaderboard')->assertOk()->json('data');

        $this->assertSame($a->profile_code, $board[0]['player']['profile_code']);
        $this->assertSame(1, $board[0]['wins']);
        $this->assertSame(0, $board[1]['wins']);
        $this->assertTrue($board[1]['is_me']);
    }
}
