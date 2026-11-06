<?php

namespace Tests\Feature\Api\V1\Games;

use App\Models\FlipGame;
use App\Models\FlipSetting;
use App\Models\FlipTileImage;
use App\Models\KoodalGameScore;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\PlaysGames;
use Tests\TestCase;

class FlipGameApiTest extends TestCase
{
    use PlaysGames, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->bootGames();

        for ($i = 1; $i <= 16; $i++) {
            FlipTileImage::create(['name' => "Image {$i}", 'path' => "flip-tiles/t{$i}.png", 'is_active' => true]);
        }
        FlipSetting::instance()->update(['mismatch_show_seconds' => 3.5]);
    }

    /** An invited, accepted, both-ready game. @return array{0: User, 1: User, 2: string} */
    protected function playingGame(): array
    {
        [$a, $b] = $this->pair();
        $this->actingAsMember($a);
        $inviteId = $this->postJson("/api/v1/games/partners/{$b->profile_code}/invites", ['game_type' => 'flip'])
            ->assertCreated()->json('data.invite.id');
        $code = FlipGame::firstOrFail()->code;

        $this->actAs($b);
        $this->postJson("/api/v1/games/invites/{$inviteId}/accept")->assertOk();
        $this->postJson("/api/v1/games/rooms/flip/{$code}/ready")->assertOk();
        $this->actAs($a);
        $this->postJson("/api/v1/games/rooms/flip/{$code}/ready")->assertOk();

        return [$a, $b, $code];
    }

    /** @return array<int, int[]> image id => [tile, tile] */
    protected function pairs(string $code): array
    {
        $groups = [];
        foreach (FlipGame::where('code', $code)->firstOrFail()->board as $tile => $image) {
            $groups[$image][] = $tile;
        }

        return array_values($groups);
    }

    protected function flip(User $user, string $code, int $tile)
    {
        $this->actAs($user);

        return $this->postJson("/api/v1/games/rooms/flip/{$code}/flip", ['tile' => $tile]);
    }

    /** Match every pair, the players taking turns to open first (a 4–4 draw). */
    protected function finish(User $a, User $b, string $code): void
    {
        foreach ($this->pairs($code) as $n => [$first, $second]) {
            [$opener, $closer] = $n % 2 === 0 ? [$a, $b] : [$b, $a];
            $this->flip($opener, $code, $first)->assertOk();
            $this->flip($closer, $code, $second)->assertOk();
        }
    }

    public function test_an_invited_game_seats_both_players_and_starts_when_both_are_ready(): void
    {
        [$a, $b, $code] = $this->playingGame();

        $this->actAs($a);
        $state = $this->getJson("/api/v1/games/rooms/flip/{$code}")->assertOk()->json('data');

        $this->assertSame('playing', $state['status']);
        $this->assertSame(1, $state['you']['slot']);
        $this->assertSame($b->profile_code, $state['partner']['profile_code']);
        $this->assertTrue($state['game']['my_turn']);
        $this->assertSame(16, $state['game']['tiles']);
        $this->assertSame(['me' => 0, 'partner' => 0], $state['game']['scores']);
    }

    public function test_the_board_layout_is_never_sent_before_a_tile_is_flipped(): void
    {
        [$a, , $code] = $this->playingGame();
        $this->actAs($a);

        $json = $this->getJson("/api/v1/games/rooms/flip/{$code}")->json('data.game');

        $this->assertSame([], $json['flipped']);
        $this->assertSame([], $json['matched']);
        $this->assertNull($json['preview']['images']);
        $this->assertArrayNotHasKey('board', $json);
    }

    public function test_a_stranger_cannot_play_or_read_the_game(): void
    {
        [, , $code] = $this->playingGame();
        $stranger = $this->member();
        $this->actAs($stranger);

        $this->getJson("/api/v1/games/rooms/flip/{$code}")->assertForbidden();
        $this->postJson("/api/v1/games/rooms/flip/{$code}/flip", ['tile' => 0])->assertForbidden();
        $this->postJson("/api/v1/games/rooms/flip/{$code}/ready")->assertForbidden();
    }

    public function test_a_stranger_cannot_take_the_second_seat_of_an_invited_game(): void
    {
        [$a, $b] = $this->pair();
        $stranger = $this->member();
        $this->actingAsMember($a);
        $this->postJson("/api/v1/games/partners/{$b->profile_code}/invites", ['game_type' => 'flip'])->assertCreated();
        $code = FlipGame::firstOrFail()->code;

        $this->actAs($stranger);
        $this->postJson("/api/v1/games/rooms/flip/{$code}/join")->assertForbidden();
        $this->assertNull(FlipGame::firstOrFail()->p2_token);
    }

    public function test_an_open_game_can_be_joined_by_one_member_holding_the_code(): void
    {
        [$a, $b] = $this->pair();
        $c = $this->member();

        $this->actingAsMember($a);
        $created = $this->postJson('/api/v1/games/rooms/flip')->assertCreated();
        $code = $created->json('data.code');
        $this->assertSame('lobby', $created->json('data.status'));
        $this->assertNull($created->json('data.partner'));

        $this->actAs($b);
        $this->postJson("/api/v1/games/rooms/flip/{$code}/join")->assertOk()
            ->assertJsonPath('data.partner.profile_code', $a->profile_code);

        // Joining twice is harmless; a third member is turned away.
        $this->postJson("/api/v1/games/rooms/flip/{$code}/join")->assertOk();
        $this->actAs($c);
        $this->postJson("/api/v1/games/rooms/flip/{$code}/join")->assertForbidden();
    }

    public function test_a_blocked_member_cannot_join_an_open_game(): void
    {
        [$a, $b] = $this->pair();
        $a->blocks()->create(['blocked_user_id' => $b->id]);

        $this->actingAsMember($a);
        $code = $this->postJson('/api/v1/games/rooms/flip')->json('data.code');

        $this->actAs($b);
        $this->postJson("/api/v1/games/rooms/flip/{$code}/join")->assertForbidden();
    }

    public function test_the_partner_chemistry_rule_scores_for_whoever_opened_first(): void
    {
        [$a, $b, $code] = $this->playingGame();
        [$first, $second] = $this->pairs($code)[0];

        $opened = $this->flip($a, $code, $first)->assertOk();
        $this->assertFalse($opened->json('data.flip.matched'));
        $this->assertSame('partner', $opened->json('data.game.turn'));
        $this->assertCount(1, $opened->json('data.game.flipped'));

        // Opening again out of turn is refused.
        $this->flip($a, $code, $second)->assertForbidden();

        $matched = $this->flip($b, $code, $second)->assertOk();
        $this->assertTrue($matched->json('data.flip.matched'));
        $this->assertSame(1, $matched->json('data.flip.awarded_to'));
        // Player two opened the matching tile, so they keep the turn.
        $this->assertTrue($matched->json('data.game.my_turn'));
        $this->assertSame(['me' => 0, 'partner' => 1], $matched->json('data.game.scores'));
        $this->assertSame('partner', $matched->json('data.game.matched.0.by'));
    }

    public function test_a_mismatch_shows_both_tiles_then_clears(): void
    {
        [$a, $b, $code] = $this->playingGame();
        [$x] = $this->pairs($code)[0];
        [$y] = $this->pairs($code)[1];

        $this->flip($a, $code, $x)->assertOk();
        $response = $this->flip($b, $code, $y)->assertOk();

        $this->assertCount(2, $response->json('data.game.flipped'));
        $this->assertNotNull($response->json('data.game.mismatch_until'));

        // Within the window another flip is refused.
        $this->flip($b, $code, $this->pairs($code)[2][0])->assertStatus(409);

        FlipGame::where('code', $code)->update(['mismatch_until' => now()->subSecond()]);
        $this->actAs($a);
        $this->assertSame([], $this->getJson("/api/v1/games/rooms/flip/{$code}")->json('data.game.flipped'));
    }

    public function test_the_preview_can_be_used_once_and_only_while_playing(): void
    {
        [$a, , $code] = $this->playingGame();
        $this->actAs($a);

        $peek = $this->postJson("/api/v1/games/rooms/flip/{$code}/preview")->assertOk();
        $this->assertTrue($peek->json('data.game.preview.active'));
        $this->assertCount(16, $peek->json('data.game.preview.images'));
        $this->assertTrue($peek->json('data.game.preview_used'));

        $this->postJson("/api/v1/games/rooms/flip/{$code}/preview")->assertStatus(409);
    }

    public function test_the_peek_is_refused_while_a_tile_is_face_up(): void
    {
        [$a, , $code] = $this->playingGame();
        $this->flip($a, $code, $this->pairs($code)[0][0])->assertOk();

        $this->postJson("/api/v1/games/rooms/flip/{$code}/preview")
            ->assertStatus(409)->assertJsonPath('message', 'Cannot use preview while tiles are open.');
    }

    public function test_the_peek_works_once_a_mismatched_pair_has_turned_back(): void
    {
        [$a, $b, $code] = $this->playingGame();
        $this->flip($a, $code, $this->pairs($code)[0][0])->assertOk();
        $this->flip($b, $code, $this->pairs($code)[1][0])->assertOk();

        // The show time is over, but nobody has read the board since — the
        // pair is still stored as open. It must not block the peek.
        FlipGame::where('code', $code)->update(['mismatch_until' => now()->subSecond()]);

        $this->actAs($a);
        $this->postJson("/api/v1/games/rooms/flip/{$code}/preview")->assertOk()
            ->assertJsonPath('data.game.preview.active', true)
            ->assertJsonPath('data.game.flipped', []);
    }

    public function test_the_start_time_is_reported_until_the_first_move(): void
    {
        [$a, , $code] = $this->playingGame();

        $started = $this->getJson("/api/v1/games/rooms/flip/{$code}")->json('data.started_at');
        $this->assertIsInt($started);
        $this->assertEqualsWithDelta(now()->getTimestamp(), $started, 5);

        $this->flip($a, $code, 0)->assertOk()->assertJsonPath('data.started_at', null);
    }

    public function test_an_invalid_tile_is_rejected(): void
    {
        [$a, , $code] = $this->playingGame();

        $this->flip($a, $code, 16)->assertStatus(422);
        $this->flip($a, $code, -1)->assertStatus(422);
    }

    public function test_a_full_game_finishes_scores_both_players_and_offers_a_rematch(): void
    {
        [$a, $b, $code] = $this->playingGame();
        $this->finish($a, $b, $code);

        $this->actAs($a);
        $done = $this->getJson("/api/v1/games/rooms/flip/{$code}")->json('data');
        $this->assertSame('completed', $done['status']);
        $this->assertSame('draw', $done['game']['winner']);
        $this->assertSame(100, $done['game']['chemistry_score']);
        $this->assertSame(2, KoodalGameScore::where('game_type', 'flip')->count());
        $this->assertNull($done['game']['rematch_code']);

        $rematch = $this->postJson("/api/v1/games/rooms/flip/{$code}/reset")->assertOk();
        $newCode = $rematch->json('data.code');
        $this->assertNotSame($code, $newCode);
        $this->assertSame('lobby', $rematch->json('data.status'));

        // The partner is pointed at the new board from the old one.
        $this->actAs($b);
        $this->assertSame($newCode, $this->getJson("/api/v1/games/rooms/flip/{$code}")->json('data.game.rematch_code'));
        $this->postJson("/api/v1/games/rooms/flip/{$newCode}/join")->assertOk();
    }

    public function test_the_second_player_asking_for_a_rematch_joins_the_new_board(): void
    {
        [$a, $b, $code] = $this->playingGame();
        $this->finish($a, $b, $code);

        $this->actAs($a);
        $newCode = $this->postJson("/api/v1/games/rooms/flip/{$code}/reset")->assertOk()->json('data.code');

        $this->actAs($b);
        $second = $this->postJson("/api/v1/games/rooms/flip/{$code}/reset")->assertOk();

        $this->assertSame($newCode, $second->json('data.code'), 'no third board is dealt');
        $this->assertTrue($second->json('data.game.partner_joined'));
        $this->assertSame(2, $second->json('data.you.slot'));
        $this->assertSame(1, FlipGame::where('status', 'waiting')->count());
    }

    public function test_ready_does_not_reopen_an_abandoned_game(): void
    {
        [, $b, $code] = $this->playingGame();
        $this->postJson("/api/v1/games/rooms/flip/{$code}/leave")->assertOk();

        $this->actAs($b);
        $this->postJson("/api/v1/games/rooms/flip/{$code}/ready")->assertOk()
            ->assertJsonPath('data.status', 'abandoned');
    }

    public function test_ready_does_not_reopen_a_finished_game(): void
    {
        [$a, $b, $code] = $this->playingGame();
        $this->finish($a, $b, $code);

        $this->actAs($a);
        $this->postJson("/api/v1/games/rooms/flip/{$code}/ready")->assertOk()
            ->assertJsonPath('data.status', 'completed');
        $this->assertSame('finished', FlipGame::where('code', $code)->value('status'));
    }

    public function test_a_game_idle_for_an_hour_is_abandoned(): void
    {
        [$a, , $code] = $this->playingGame();
        $this->travel(61)->minutes();

        $this->actAs($a);
        $this->getJson("/api/v1/games/rooms/flip/{$code}")->assertOk()
            ->assertJsonPath('data.status', 'abandoned');
    }

    public function test_a_rematch_is_refused_while_the_game_is_still_running(): void
    {
        [$a, , $code] = $this->playingGame();
        $this->actAs($a);

        $this->postJson("/api/v1/games/rooms/flip/{$code}/reset")->assertStatus(409);
    }

    public function test_leaving_abandons_the_game(): void
    {
        [$a, , $code] = $this->playingGame();
        $this->actAs($a);

        $this->postJson("/api/v1/games/rooms/flip/{$code}/leave")->assertOk()
            ->assertJsonPath('data.status', 'abandoned');
    }
}
