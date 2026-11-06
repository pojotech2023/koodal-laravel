<?php

namespace Tests\Feature\Web;

use App\Models\BlockedUser;
use App\Models\FlipGame;
use App\Models\GameChatMessage;
use App\Models\GameInvite;
use App\Models\GameRoom;
use App\Models\KoodalGameScore;
use App\Models\MemorySnapGame;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\PlaysGames;
use Tests\TestCase;

/**
 * The website's game endpoints, after the security pass: who may accept an
 * invite, who may act in a room, what a player may see, and where scores come
 * from. Each test names the hole it closes.
 */
class GameSecurityWebTest extends TestCase
{
    use PlaysGames, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->bootGames();
    }

    protected function as(User $user): void
    {
        $this->app['auth']->forgetGuards();
        $this->actingAs($user, 'web');
    }

    protected function block(User $by, User $whom): void
    {
        BlockedUser::create(['user_id' => $by->id, 'blocked_user_id' => $whom->id]);
    }

    protected function invite(User $from, User $to, string $type = 'sync'): GameInvite
    {
        return GameInvite::create([
            'sender_id' => $from->id,
            'receiver_id' => $to->id,
            'game_type' => $type,
            'status' => 'pending',
            'expires_at' => now()->addDay(),
        ]);
    }

    // ── Invites ──────────────────────────────────────────────────────────

    public function test_an_invite_link_never_signs_anyone_in(): void
    {
        [$a, $b] = $this->pair();
        $invite = $this->invite($a, $b);

        $this->withHeaders(['Accept' => 'text/html'])
            ->get("/games/invites/{$invite->id}/accept")
            ->assertRedirect();
        $this->assertGuest('web');

        $this->postJson("/games/invites/{$invite->id}/accept")->assertUnauthorized();
        $this->assertGuest('web');
        $this->assertSame('pending', $invite->fresh()->status);
    }

    public function test_only_the_receiver_can_accept_an_invite(): void
    {
        [$a, $b] = $this->pair();
        $stranger = $this->member();
        $invite = $this->invite($a, $b);

        $this->as($stranger);
        $this->postJson("/games/invites/{$invite->id}/accept")->assertForbidden();

        $this->as($a);
        $this->postJson("/games/invites/{$invite->id}/accept")->assertForbidden();
        $this->assertSame('pending', $invite->fresh()->status);

        $this->as($b);
        $this->postJson("/games/invites/{$invite->id}/accept")
            ->assertOk()
            ->assertJsonPath('success', true);
        $this->assertSame('accepted', $invite->fresh()->status);
    }

    public function test_an_expired_invite_or_one_from_a_blocked_member_cannot_be_accepted(): void
    {
        [$a, $b] = $this->pair();

        $old = $this->invite($a, $b);
        $old->update(['expires_at' => now()->subMinute()]);
        $this->as($b);
        $this->postJson("/games/invites/{$old->id}/accept")->assertStatus(410);

        $fresh = $this->invite($a, $b, 'find_it');
        $this->block($a, $b);
        $this->postJson("/games/invites/{$fresh->id}/accept")->assertForbidden();
        $this->assertSame('pending', $fresh->fresh()->status);
    }

    public function test_sending_an_invite_to_an_unknown_member_does_not_fall_back_to_user_one(): void
    {
        $a = $this->member();
        $this->as($a);

        $this->postJson('/games/999999/invite', ['game_type' => 'sync'])->assertNotFound();
        $this->postJson("/games/{$a->id}/invite", ['game_type' => 'sync'])->assertStatus(422);

        $this->assertSame(0, GameInvite::count());
    }

    public function test_invites_to_a_blocked_member_or_for_an_unknown_game_are_refused(): void
    {
        [$a, $b] = $this->pair();
        $this->as($a);

        $this->postJson("/games/{$b->id}/invite", ['game_type' => 'poker'])->assertStatus(422);

        $this->block($b, $a);
        $this->postJson("/games/{$b->id}/invite", ['game_type' => 'sync'])->assertNotFound();

        $this->assertSame(0, GameInvite::count());
    }

    // ── Memory Snap ──────────────────────────────────────────────────────

    /** @return array{0: User, 1: User, 2: string} */
    protected function snapGame(bool $start = true): array
    {
        [$a, $b] = $this->pair();
        $this->as($a);
        $this->get("/memory-snap/start/{$b->id}")->assertRedirect();
        $code = MemorySnapGame::latest('id')->value('code');

        if ($start) {
            $this->postJson("/memory-snap/room/{$code}/ready")->assertOk();
            $this->as($b);
            $this->postJson("/memory-snap/room/{$code}/ready")->assertOk();
            // Past the countdown and the ten-second photo: the question is up.
            MemorySnapGame::where('code', $code)->update(['image_started_at' => microtime(true) - 30]);
        }

        return [$a, $b, $code];
    }

    public function test_memory_snap_seat_comes_from_the_login_not_a_posted_role(): void
    {
        [$a, $b, $code] = $this->snapGame();

        $this->as($a);
        $this->postJson("/memory-snap/room/{$code}/answer", ['role' => 2, 'question_index' => 0, 'answer' => 'A'])
            ->assertOk();

        $game = MemorySnapGame::where('code', $code)->first();
        $this->assertSame('A', $game->user_one_answers['0'] ?? null);
        $this->assertEmpty($game->user_two_answers);
    }

    public function test_memory_snap_is_closed_to_anyone_outside_the_game(): void
    {
        [, , $code] = $this->snapGame();
        $stranger = $this->member();
        $this->as($stranger);

        $this->getJson("/memory-snap/room/{$code}/state")->assertForbidden();
        $this->postJson("/memory-snap/room/{$code}/ready", ['role' => 1])->assertForbidden();
        $this->postJson("/memory-snap/room/{$code}/answer", ['role' => 1, 'question_index' => 0, 'answer' => 'A'])
            ->assertForbidden();
        $this->postJson("/memory-snap/room/{$code}/reset", ['role' => 1])->assertForbidden();
        $this->postJson("/memory-snap/room/{$code}/chat", ['role' => 1, 'message' => 'Super'])->assertForbidden();
        $this->withHeaders(['Accept' => 'text/html'])->get("/memory-snap/room/{$code}")->assertRedirect(route('dashboard'));

        $this->assertNull(MemorySnapGame::where('code', $code)->value('user_one_answers')['0'] ?? null);
    }

    public function test_memory_snap_withholds_the_answer_and_the_partners_pick_until_the_reveal(): void
    {
        [$a, $b, $code] = $this->snapGame();

        // Nobody has answered: no correct option for the open question.
        $this->as($a);
        $state = $this->getJson("/memory-snap/room/{$code}/state")->assertOk()->json();
        $this->assertArrayNotHasKey('correct_option', $state['questions_data'][0]);
        $this->assertArrayHasKey('question_text', $state['questions_data'][0]);

        // B answers: A learns that B answered, not what — and no score moves.
        $this->as($b);
        $this->postJson("/memory-snap/room/{$code}/answer", ['question_index' => 0, 'answer' => 'B'])->assertOk();
        $this->as($a);
        $state = $this->getJson("/memory-snap/room/{$code}/state")->assertOk()->json();
        $this->assertSame('?', $state['user_two_answers']['0']);
        $this->assertSame(0, $state['user_two_score']);
        $this->assertArrayNotHasKey('correct_option', $state['questions_data'][0]);

        // Both answered: the reveal shows the answer and both picks.
        $this->postJson("/memory-snap/room/{$code}/answer", ['question_index' => 0, 'answer' => 'A'])->assertOk();
        $state = $this->getJson("/memory-snap/room/{$code}/state")->assertOk()->json();
        $this->assertArrayHasKey('correct_option', $state['questions_data'][0]);
        $this->assertSame('B', $state['user_two_answers']['0']);
        $this->assertArrayNotHasKey('correct_option', $state['questions_data'][1]);
    }

    public function test_the_memory_snap_page_carries_no_answers_and_no_phone_number(): void
    {
        [$a, $b, $code] = $this->snapGame(start: false);
        $b->update(['mobile' => '9876543210']);

        $this->as($a);
        $page = $this->withHeaders(['Accept' => 'text/html'])->get("/memory-snap/room/{$code}")->assertOk();

        // The script reads `.correct_option` during a reveal; the data it is
        // handed must not carry one.
        $page->assertDontSee('"correct_option":', false);
        $page->assertSee('"question_text":', false);
        $page->assertDontSee('9876543210', false);
        $page->assertDontSee('partnerMobile', false);
    }

    public function test_memory_snap_next_game_link_must_stay_on_the_site(): void
    {
        [$a, , $code] = $this->snapGame(start: false);
        $this->as($a);

        foreach (['javascript:alert(1)', '//evil.example/x', '/\\evil.example', 'https://evil.example/x y'] as $bad) {
            $this->postJson("/memory-snap/room/{$code}/next-game", ['url' => $bad])->assertStatus(422);
        }
        $this->assertNull(MemorySnapGame::where('code', $code)->value('next_game_url'));

        // The page strips its own origin before sending; both forms land as a path.
        $this->postJson("/memory-snap/room/{$code}/next-game", ['url' => '/flip/ABCD1234'])
            ->assertOk()->assertJsonPath('next_game_url', '/flip/ABCD1234');
        $this->postJson("/memory-snap/room/{$code}/next-game", ['url' => url('/flip/WXYZ')])
            ->assertOk()->assertJsonPath('next_game_url', '/flip/WXYZ');
    }

    public function test_an_open_memory_snap_room_seats_one_guest_only(): void
    {
        $host = $this->member();
        [$first, $second] = $this->pair();

        $this->as($host);
        $this->get('/memory-snap/start')->assertRedirect();
        $code = MemorySnapGame::latest('id')->value('code');

        $this->as($first);
        $this->withHeaders(['Accept' => 'text/html'])->get("/memory-snap/room/{$code}")->assertOk();
        $this->assertSame($first->id, MemorySnapGame::where('code', $code)->value('user_two_id'));

        $this->as($second);
        $this->withHeaders(['Accept' => 'text/html'])->get("/memory-snap/room/{$code}")->assertRedirect(route('dashboard'));
        $this->postJson("/memory-snap/room/{$code}/join", ['name' => 'Karthik'])->assertForbidden();
        $this->assertSame($first->id, MemorySnapGame::where('code', $code)->value('user_two_id'));
    }

    // ── Live rooms (Sync, Find It) ───────────────────────────────────────

    protected function syncRoom(User $a, User $b): GameRoom
    {
        $this->seedSyncQuestions();
        $this->as($a);
        $this->postJson("/games/{$b->id}/invite", ['game_type' => 'sync'])->assertOk();

        return GameRoom::where('game_type', 'sync')->latest('id')->firstOrFail();
    }

    public function test_live_room_endpoints_refuse_anyone_outside_the_room(): void
    {
        [$a, $b] = $this->pair();
        $room = $this->syncRoom($a, $b);
        $code = $room->room_code;

        $this->as($this->member());
        $this->getJson("/games/room/{$code}/poll")->assertForbidden();
        $this->postJson("/games/room/{$code}/ready")->assertForbidden();
        $this->postJson("/games/room/{$code}/answer", ['question_id' => $room->question_ids[0], 'selected_option' => 'A'])
            ->assertForbidden();
        $this->postJson("/games/room/{$code}/next-question")->assertForbidden();
        $this->postJson("/games/room/{$code}/start")->assertForbidden();
        $this->postJson("/games/room/{$code}/reset")->assertForbidden();
        $this->postJson("/games/room/{$code}/leave")->assertForbidden();
        $this->postJson("/games/room/{$code}/messages", ['message' => 'Super'])->assertForbidden();
        $this->postJson("/games/room/{$code}/messages/read")->assertForbidden();

        $room->refresh();
        $this->assertSame('lobby', $room->status);
        $this->assertFalse((bool) $room->user_a_ready);
        $this->assertSame(0, GameChatMessage::where('room_id', $room->id)->count());
    }

    public function test_sync_hides_the_partners_pick_until_both_have_answered(): void
    {
        [$a, $b] = $this->pair();
        $room = $this->syncRoom($a, $b);
        $code = $room->room_code;
        $q0 = $room->question_ids[0];

        $this->postJson("/games/room/{$code}/ready")->assertOk();
        $this->as($b);
        $this->postJson("/games/room/{$code}/ready")->assertOk()->assertJsonPath('status', 'playing');

        // A wrong question id is refused instead of being filed under the current one.
        $this->postJson("/games/room/{$code}/answer", ['question_id' => $room->question_ids[1], 'selected_option' => 'A'])
            ->assertStatus(409);

        $this->postJson("/games/room/{$code}/answer", ['question_id' => $q0, 'selected_option' => 'B'])
            ->assertOk()->assertJsonPath('both_answered', false);

        $this->as($a);
        $poll = $this->getJson("/games/room/{$code}/poll")->assertOk()->json();
        $this->assertTrue($poll['partner_answered']);
        $this->assertArrayNotHasKey(0, (array) $poll['user_b_answers']);

        // Advancing before both have answered is refused.
        $this->postJson("/games/room/{$code}/next-question", ['from_index' => 0])->assertStatus(409);

        $this->postJson("/games/room/{$code}/answer", ['question_id' => $q0, 'selected_option' => 'A'])
            ->assertOk()->assertJsonPath('both_answered', true);
        $poll = $this->getJson("/games/room/{$code}/poll")->assertOk()->json();
        $this->assertSame('B', $poll['user_b_answers'][0]['option']);
    }

    public function test_room_chat_uses_the_whole_message_chip_rule_and_has_no_system_voice(): void
    {
        [$a, $b] = $this->pair();
        $room = $this->syncRoom($a, $b);
        $code = $room->room_code;

        $this->postJson("/games/room/{$code}/messages", ['message' => 'wow, call me on 9876543210'])->assertForbidden();
        $this->postJson("/games/room/{$code}/messages", ['message' => 'Anything', 'type' => 'system'])->assertStatus(422);
        $this->postJson("/games/room/{$code}/messages", ['message' => 'Wow 👏', 'type' => 'quick_reply'])->assertOk();

        $this->assertSame(['Wow 👏'], GameChatMessage::where('room_id', $room->id)->pluck('message')->all());
    }

    public function test_room_moves_stop_once_either_member_blocks_the_other(): void
    {
        [$a, $b] = $this->pair();
        $room = $this->syncRoom($a, $b);
        $this->block($b, $a);

        $this->postJson("/games/room/{$room->room_code}/ready")->assertForbidden();
        $this->postJson("/games/room/{$room->room_code}/messages", ['message' => 'Super'])->assertForbidden();
        $this->withHeaders(['Accept' => 'text/html'])->get("/games/room/{$room->room_code}")->assertRedirect();
    }

    // ── Find It ──────────────────────────────────────────────────────────

    public function test_find_it_partners_share_one_room_and_opening_the_page_does_not_reset_it(): void
    {
        [$a, $b] = $this->pair();

        $this->as($a);
        $this->withHeaders(['Accept' => 'text/html'])->get("/games/{$b->id}/find-it")->assertOk();
        $room = GameRoom::where('game_type', 'find_it')->sole();
        $this->postJson("/games/room/{$room->room_code}/start")->assertOk()->assertJsonPath('status', 'playing');
        $this->postJson("/games/room/{$room->room_code}/claim-find-it", ['difference_index' => 2])
            ->assertOk()->assertJsonPath('success', true);

        $this->as($b);
        $page = $this->withHeaders(['Accept' => 'text/html'])->get("/games/{$a->id}/find-it")->assertOk();

        $this->assertSame($room->room_code, $page->viewData('room')->room_code);
        $room->refresh();
        $this->assertSame('playing', $room->status);
        $this->assertSame([2], $room->user_a_answers);

        $this->postJson("/games/room/{$room->room_code}/claim-find-it", ['difference_index' => 2])
            ->assertOk()->assertJsonPath('claimed_by_partner', true);
    }

    public function test_find_it_score_comes_from_the_room_not_the_browser(): void
    {
        [$a, $b] = $this->pair();
        $this->as($a);
        $this->withHeaders(['Accept' => 'text/html'])->get("/games/{$b->id}/find-it")->assertOk();
        $code = GameRoom::where('game_type', 'find_it')->value('room_code');
        $this->postJson("/games/room/{$code}/start")->assertOk();

        // Mid-round, a claimed perfect score is refused and nothing is recorded.
        $this->postJson("/games/{$b->id}/find-it", [
            'found_count' => 7, 'total_differences' => 7, 'time_taken' => 5,
            'my_count' => 4, 'partner_count' => 3, 'chemistry_score' => 100,
        ])->assertStatus(409);
        $this->assertSame(0, KoodalGameScore::count());

        // A finds all seven alone: a lopsided hunt, scored by the room.
        foreach (range(0, 6) as $i) {
            $last = $this->postJson("/games/room/{$code}/claim-find-it", ['difference_index' => $i])->assertOk();
        }
        $last->assertJsonPath('status', 'completed')->assertJsonPath('score', 5);

        $this->postJson("/games/{$b->id}/find-it", ['chemistry_score' => 100])
            ->assertOk()->assertJsonPath('score', 5);

        // One row per partner, recorded once, both at the room's score.
        $this->assertSame([5, 5], KoodalGameScore::where('game_type', 'find_it')->pluck('score')->map(fn ($s) => (int) $s)->all());
    }

    public function test_find_it_claims_after_a_configured_clock_are_refused(): void
    {
        config(['game.timing.find_it_limit' => 60]);
        [$a, $b] = $this->pair();
        $this->as($a);
        $this->withHeaders(['Accept' => 'text/html'])->get("/games/{$b->id}/find-it")->assertOk();
        $room = GameRoom::where('game_type', 'find_it')->sole();
        $this->postJson("/games/room/{$room->room_code}/start")->assertOk();
        $room->update(['started_at' => now()->subMinutes(5)]);

        $this->postJson("/games/room/{$room->room_code}/claim-find-it", ['difference_index' => 0])
            ->assertJsonPath('result', 'time_up');
        $this->getJson("/games/room/{$room->room_code}/poll")
            ->assertOk()->assertJsonPath('status', 'completed')->assertJsonPath('seconds_left', 0);
    }

    public function test_find_it_is_untimed_by_default(): void
    {
        [$a, $b] = $this->pair();
        $this->as($a);
        $this->withHeaders(['Accept' => 'text/html'])->get("/games/{$b->id}/find-it")
            ->assertOk()->assertSee('timeLimit: 0,', false);
        $room = GameRoom::where('game_type', 'find_it')->sole();
        $this->postJson("/games/room/{$room->room_code}/start")->assertOk()->assertJsonPath('seconds_left', null);
        $room->update(['started_at' => now()->subMinutes(5)]);

        $this->postJson("/games/room/{$room->room_code}/claim-find-it", ['difference_index' => 0])
            ->assertJsonPath('result', 'found');
        $this->getJson("/games/room/{$room->room_code}/poll")
            ->assertOk()->assertJsonPath('status', 'playing')->assertJsonPath('seconds_left', null);
    }

    // ── Solo games ───────────────────────────────────────────────────────

    public function test_memory_is_graded_by_the_server(): void
    {
        [$a, $b] = $this->pair();
        $this->as($a);

        // The old body — a self-reported count — is no longer accepted.
        $this->postJson("/games/{$b->id}/memory", ['correct_count' => 4, 'total_questions' => 4, 'time_taken' => 1])
            ->assertStatus(422);

        $round = $this->withHeaders(['Accept' => 'text/html'])->get("/games/{$b->id}/memory")->assertOk()->viewData('round');
        $answers = collect($round['quiz'])->map(fn ($q) => [
            'position' => $q['position'],
            'symbol_id' => $round['sequence'][$q['position'] - 1]['id'],
        ])->all();
        $answers[0]['symbol_id'] = 'wrong';

        $this->postJson("/games/{$b->id}/memory", ['session_id' => $round['session_id'], 'answers' => $answers, 'time_taken' => 9])
            ->assertOk()->assertJsonPath('score', 75)->assertJsonPath('correct_count', 3);

        // Single use.
        $this->postJson("/games/{$b->id}/memory", ['session_id' => $round['session_id'], 'answers' => $answers])
            ->assertStatus(410);
        $this->assertSame(1, KoodalGameScore::where('game_type', 'memory')->count());
    }

    public function test_spot_is_graded_from_the_taps_on_the_dealt_board(): void
    {
        [$a, $b] = $this->pair();
        $this->as($a);

        $this->postJson("/games/{$b->id}/spot", ['correct_taps' => 10, 'wrong_taps' => 0, 'total_targets' => 10, 'time_taken' => 3])
            ->assertStatus(422);

        $round = $this->withHeaders(['Accept' => 'text/html'])->get("/games/{$b->id}/spot")->assertOk()->viewData('round');
        $targets = array_keys(array_filter($round['grid'], fn ($s) => $s === $round['target_symbol']));
        $this->assertCount(10, $targets);

        $this->postJson("/games/{$b->id}/spot", ['session_id' => $round['session_id'], 'taps' => array_slice($targets, 0, 8)])
            ->assertOk()->assertJsonPath('score', 80)->assertJsonPath('correct_taps', 8);
    }

    public function test_game_pages_refuse_yourself_a_blocked_member_and_unknown_ids(): void
    {
        [$a, $b] = $this->pair();
        $this->as($a);

        $this->get('/games/999999/find-it')->assertNotFound();
        $this->get("/games/{$a->id}/memory")->assertNotFound();

        $this->block($a, $b);
        foreach (['', '/sync', '/find-it', '/memory', '/spot', '/vibe'] as $page) {
            $this->get("/games/{$b->id}{$page}")->assertNotFound();
        }
        $this->get("/memory-snap/start/{$b->id}")->assertNotFound();
    }

    // ── Presence ─────────────────────────────────────────────────────────

    public function test_presence_respects_the_online_status_setting_and_blocks(): void
    {
        [$a, $b] = $this->pair();
        $b->forceFill(['last_active_at' => now()])->save();
        $this->as($a);

        $this->getJson("/presence/{$b->id}/status")->assertOk()->assertJsonPath('is_online', true);

        $b->privacySettings()->update(['show_online_status' => false]);
        $this->getJson("/presence/{$b->id}/status")
            ->assertJsonPath('is_online', false)->assertJsonPath('label', 'Offline');

        $b->privacySettings()->update(['show_online_status' => true]);
        $this->block($b, $a);
        $this->getJson("/presence/{$b->id}/status")
            ->assertJsonPath('is_online', false)->assertJsonPath('label', 'Offline');
    }

    // ── Flip ─────────────────────────────────────────────────────────────

    public function test_a_flip_board_made_for_one_member_cannot_be_taken_by_another(): void
    {
        [$a, $b] = $this->pair();
        $stranger = $this->member();

        $this->as($a);
        $this->postJson("/games/{$b->id}/invite", ['game_type' => 'flip'])->assertOk();
        $game = FlipGame::latest('id')->firstOrFail();
        $this->assertSame($b->id, $game->user_two_id);

        $this->as($stranger);
        $this->withHeaders(['Accept' => 'text/html'])->get("/flip/{$game->code}")->assertRedirect(route('dashboard'));
        $this->assertNull($game->fresh()->p2_token);

        // The named member is player 2 by account (FlipGame::playerNumber),
        // with or without a seat token.
        $this->as($b);
        $this->withHeaders(['Accept' => 'text/html'])->get("/flip/{$game->code}")->assertOk()->assertViewHas('you', 2);
        $this->assertContains($game->fresh()->p2_token, [null, 'user_'.$b->id]);

        // A later visitor still does not get player 2's seat.
        $this->as($stranger);
        $this->withHeaders(['Accept' => 'text/html'])->get("/flip/{$game->code}")->assertRedirect(route('dashboard'));
        $this->assertSame($b->id, (int) $game->fresh()->user_two_id);
        $this->assertContains($game->fresh()->p2_token, [null, 'user_'.$b->id]);
    }
}
