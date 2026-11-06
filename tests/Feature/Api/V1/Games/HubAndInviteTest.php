<?php

namespace Tests\Feature\Api\V1\Games;

use App\Models\GameInvite;
use App\Models\GameRoom;
use App\Models\KoodalSyncAnswer;
use App\Models\KoodalSyncQuestion;
use App\Models\KoodalSyncSession;
use App\Models\Notification;
use App\Models\User;
use App\Services\Notification\NotificationDispatcher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\PlaysGames;
use Tests\TestCase;

class HubAndInviteTest extends TestCase
{
    use PlaysGames, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->bootGames();
        $this->seedSyncQuestions();
    }

    public function test_catalog_lists_the_four_live_games(): void
    {
        $this->actingAsMember();

        $response = $this->getJson('/api/v1/games')->assertOk();

        $this->assertEqualsCanonicalizing(
            ['sync', 'find_it', 'memory_snap', 'flip'],
            collect($response->json('data'))->pluck('type')->all(),
        );
    }

    public function test_the_games_api_requires_authentication(): void
    {
        $this->getJson('/api/v1/games')->assertUnauthorized();
    }

    public function test_hub_shows_the_partner_and_a_row_per_game(): void
    {
        [$me, $partner] = $this->pair();
        $this->actingAsMember($me);

        $response = $this->getJson("/api/v1/games/partners/{$partner->profile_code}")->assertOk();

        $this->assertSame($partner->profile_code, $response->json('data.partner.profile_code'));
        $this->assertCount(4, $response->json('data.games'));
        $this->assertSame(0, $response->json('data.vibe.percentage'));
    }

    public function test_you_cannot_open_a_hub_with_yourself_an_unknown_code_or_a_blocked_member(): void
    {
        [$me, $partner] = $this->pair();
        $this->actingAsMember($me);

        $this->getJson("/api/v1/games/partners/{$me->profile_code}")->assertNotFound();
        $this->getJson('/api/v1/games/partners/NOPE')->assertNotFound();

        $partner->blocks()->create(['blocked_user_id' => $me->id]);
        $this->getJson("/api/v1/games/partners/{$partner->profile_code}")->assertNotFound();
    }

    public function test_sending_an_invite_creates_a_room_an_invite_and_a_notification(): void
    {
        [$me, $partner] = $this->pair();
        $this->actingAsMember($me);

        $response = $this->postJson("/api/v1/games/partners/{$partner->profile_code}/invites", ['game_type' => 'sync'])
            ->assertCreated();

        $this->assertSame('sync', $response->json('data.room.type'));
        $this->assertNotEmpty($response->json('data.room.code'));
        $this->assertFalse($response->json('data.is_duplicate'));

        $invite = GameInvite::firstOrFail();
        $this->assertSame($me->id, $invite->sender_id);
        $this->assertSame($partner->id, $invite->receiver_id);
        $this->assertSame($response->json('data.room.code'), $invite->room_code);

        $this->assertDatabaseHas('notifications', ['user_id' => $partner->id, 'type' => 'game_invite']);
        $this->assertSame("/games/invites/{$invite->id}", Notification::first()->route());
    }

    public function test_an_invite_that_fails_to_save_leaves_no_room_behind(): void
    {
        // A live database missing a column the invite writes (as when a
        // migration has not been run) used to leave the new room behind, so
        // the hub offered "Resume" after the error.
        [$me, $partner] = $this->pair();
        $this->actingAsMember($me);
        GameInvite::creating(fn () => throw new \RuntimeException('no such column: room_code'));

        $this->postJson("/api/v1/games/partners/{$partner->profile_code}/invites", ['game_type' => 'find_it'])
            ->assertStatus(500);

        $this->assertSame(0, GameRoom::count());
        $this->assertNull($this->getJson("/api/v1/games/partners/{$partner->profile_code}")
            ->json('data.games.1.active_room'));
    }

    public function test_a_notification_failure_does_not_fail_a_saved_invite(): void
    {
        [$me, $partner] = $this->pair();
        $this->actingAsMember($me);
        $this->mock(NotificationDispatcher::class)->shouldReceive('send')->andThrow(new \RuntimeException('queue down'));

        $this->postJson("/api/v1/games/partners/{$partner->profile_code}/invites", ['game_type' => 'sync'])
            ->assertCreated();

        $this->assertSame(1, GameInvite::count());
    }

    public function test_the_hub_tells_a_waiting_room_from_a_game_in_play(): void
    {
        [$me, $partner] = $this->pair();
        $this->actingAsMember($me);
        $code = $this->postJson("/api/v1/games/partners/{$partner->profile_code}/invites", ['game_type' => 'sync'])
            ->json('data.room.code');
        $sync = fn () => collect($this->getJson("/api/v1/games/partners/{$partner->profile_code}")->json('data.games'))
            ->firstWhere('type', 'sync');

        $this->assertSame(['type' => 'sync', 'code' => $code, 'status' => 'lobby'], $sync()['active_room']);
        $this->assertNotNull($sync()['outgoing_invite']);

        $this->postJson("/api/v1/games/rooms/sync/{$code}/ready")->assertOk();
        $this->actAs($partner);
        $this->postJson("/api/v1/games/rooms/sync/{$code}/ready")->assertOk();
        $this->actAs($me);

        $this->assertSame('playing', $sync()['active_room']['status']);
    }

    public function test_repeating_an_open_invite_returns_it_instead_of_stacking(): void
    {
        [$me, $partner] = $this->pair();
        $this->actingAsMember($me);
        $url = "/api/v1/games/partners/{$partner->profile_code}/invites";

        $first = $this->postJson($url, ['game_type' => 'flip'])->assertCreated();
        $second = $this->postJson($url, ['game_type' => 'flip'])->assertCreated();

        $this->assertTrue($second->json('data.is_duplicate'));
        $this->assertSame($first->json('data.invite.id'), $second->json('data.invite.id'));
        $this->assertSame(1, GameInvite::count());
        $this->assertSame(1, Notification::where('type', 'game_invite')->count());
    }

    public function test_both_pressing_next_game_at_once_lands_the_pair_in_one_room(): void
    {
        [$me, $partner] = $this->pair();
        $this->actingAsMember($me);
        $mine = $this->postJson("/api/v1/games/partners/{$partner->profile_code}/invites", ['game_type' => 'find_it', 'force_fresh' => true])
            ->assertCreated();

        $this->actAs($partner);
        $theirs = $this->postJson("/api/v1/games/partners/{$me->profile_code}/invites", ['game_type' => 'find_it', 'force_fresh' => true])
            ->assertCreated();

        $this->assertTrue($theirs->json('data.joined'));
        $this->assertSame($mine->json('data.room'), $theirs->json('data.room'));
        $this->assertSame('accepted', GameInvite::find($mine->json('data.invite.id'))->status);
        $this->assertSame(1, GameRoom::where('game_type', 'find_it')->count());
        $this->assertSame('lobby', GameRoom::where('game_type', 'find_it')->value('status'));
    }

    public function test_an_unknown_game_type_is_rejected(): void
    {
        [$me, $partner] = $this->pair();
        $this->actingAsMember($me);

        $this->postJson("/api/v1/games/partners/{$partner->profile_code}/invites", ['game_type' => 'chess'])
            ->assertStatus(422);
    }

    public function test_the_receiver_sees_the_invite_and_accepting_returns_the_room(): void
    {
        [$me, $partner] = $this->pair();
        $this->actingAsMember($me);
        $sent = $this->postJson("/api/v1/games/partners/{$partner->profile_code}/invites", ['game_type' => 'sync'])
            ->assertCreated();

        $this->actAs($partner);

        $pending = $this->getJson('/api/v1/games/invites/pending')->assertOk();
        $this->assertSame(1, $pending->json('data.count'));
        $this->assertSame($me->profile_code, $pending->json('data.incoming.0.partner.profile_code'));

        $inviteId = $sent->json('data.invite.id');
        $accepted = $this->postJson("/api/v1/games/invites/{$inviteId}/accept")->assertOk();

        $this->assertSame($sent->json('data.room'), $accepted->json('data.room'));
        $this->assertSame('accepted', GameInvite::find($inviteId)->status);
        $this->assertDatabaseHas('notifications', ['user_id' => $me->id, 'type' => 'game_invite_accepted']);
    }

    public function test_the_sender_is_told_about_an_accepted_invite_exactly_once(): void
    {
        [$me, $partner] = $this->pair();
        $this->actingAsMember($me);
        $inviteId = $this->postJson("/api/v1/games/partners/{$partner->profile_code}/invites", ['game_type' => 'find_it'])
            ->json('data.invite.id');

        $this->actAs($partner);
        $this->postJson("/api/v1/games/invites/{$inviteId}/accept")->assertOk();

        $this->actAs($me);
        $first = $this->getJson('/api/v1/games/invites/pending')->json('data.accepted');
        $second = $this->getJson('/api/v1/games/invites/pending')->json('data.accepted');

        $this->assertCount(1, $first);
        $this->assertSame('find_it', $first[0]['room']['type']);
        $this->assertSame([], $second);
    }

    public function test_only_the_receiver_can_accept_or_decline_and_only_the_sender_can_remind(): void
    {
        [$me, $partner] = $this->pair();
        $stranger = $this->member();
        $this->actingAsMember($me);
        $id = $this->postJson("/api/v1/games/partners/{$partner->profile_code}/invites", ['game_type' => 'sync'])
            ->json('data.invite.id');

        // The sender cannot accept their own invite.
        $this->postJson("/api/v1/games/invites/{$id}/accept")->assertNotFound();

        $this->actAs($stranger);
        $this->postJson("/api/v1/games/invites/{$id}/accept")->assertNotFound();
        $this->postJson("/api/v1/games/invites/{$id}/decline")->assertNotFound();
        $this->postJson("/api/v1/games/invites/{$id}/remind")->assertNotFound();
        $this->getJson("/api/v1/games/invites/{$id}")->assertNotFound();

        $this->actAs($partner);
        $this->postJson("/api/v1/games/invites/{$id}/remind")->assertNotFound();
        $this->postJson("/api/v1/games/invites/{$id}/decline")->assertOk();
        $this->assertSame('declined', GameInvite::find($id)->status);
    }

    public function test_an_expired_invite_cannot_be_accepted(): void
    {
        [$me, $partner] = $this->pair();
        $this->actingAsMember($me);
        $id = $this->postJson("/api/v1/games/partners/{$partner->profile_code}/invites", ['game_type' => 'sync'])
            ->json('data.invite.id');
        GameInvite::whereKey($id)->update(['expires_at' => now()->subMinute()]);

        $this->actAs($partner);
        $this->postJson("/api/v1/games/invites/{$id}/accept")->assertStatus(410);
    }

    public function test_reminders_are_capped(): void
    {
        [$me, $partner] = $this->pair();
        $this->actingAsMember($me);
        $id = $this->postJson("/api/v1/games/partners/{$partner->profile_code}/invites", ['game_type' => 'sync'])
            ->json('data.invite.id');

        for ($i = 0; $i < 3; $i++) {
            $this->postJson("/api/v1/games/invites/{$id}/remind")->assertOk();
        }

        $this->postJson("/api/v1/games/invites/{$id}/remind")->assertStatus(429);
    }

    public function test_an_offline_sync_invite_has_no_room_and_accepting_points_to_the_quiz(): void
    {
        [$me, $partner] = $this->pair();
        $this->actingAsMember($me);
        $sent = $this->postJson("/api/v1/games/partners/{$partner->profile_code}/invites", [
            'game_type' => 'sync', 'mode' => 'offline',
        ])->assertCreated();

        $this->assertNull($sent->json('data.room'));

        $this->actAs($partner);
        $accepted = $this->postJson('/api/v1/games/invites/'.$sent->json('data.invite.id').'/accept')->assertOk();

        $this->assertNull($accepted->json('data.room'));
        $this->assertSame($me->profile_code, $accepted->json('data.offline_sync.partner_code'));

        // The sender's "accepted" notification must open a screen the app has.
        $note = Notification::where('user_id', $me->id)->where('type', 'game_invite_accepted')->firstOrFail();
        $this->assertSame("/games/partners/{$partner->profile_code}", $note->data['route']);
    }

    public function test_a_live_sync_invite_stays_live_when_the_sender_has_played_with_someone_else(): void
    {
        [$me, $partner] = $this->pair();
        $this->answeredSync($me, $this->member());

        $this->actingAsMember($me);
        $sent = $this->postJson("/api/v1/games/partners/{$partner->profile_code}/invites", ['game_type' => 'sync'])
            ->assertCreated();

        $this->actAs($partner);
        $accepted = $this->postJson('/api/v1/games/invites/'.$sent->json('data.invite.id').'/accept')->assertOk();

        $this->assertSame($sent->json('data.room'), $accepted->json('data.room'));
        $this->assertNull($accepted->json('data.offline_sync'));
    }

    public function test_a_live_sync_invite_turns_offline_once_the_sender_answers_on_their_own(): void
    {
        [$me, $partner] = $this->pair();
        $this->actingAsMember($me);
        $id = $this->postJson("/api/v1/games/partners/{$partner->profile_code}/invites", ['game_type' => 'sync'])
            ->json('data.invite.id');

        // Rather than wait in the room, the sender plays the quiz alone.
        $questions = $this->getJson("/api/v1/games/partners/{$partner->profile_code}/sync")->json('data.questions');
        $this->postJson("/api/v1/games/partners/{$partner->profile_code}/sync", [
            'answers' => collect($questions)->mapWithKeys(fn ($q) => [$q['id'] => 'A'])->all(),
        ])->assertCreated();

        $this->actAs($partner);
        $this->assertTrue($this->getJson("/api/v1/games/invites/{$id}")->json('data.offline_waiting'));
        $accepted = $this->postJson("/api/v1/games/invites/{$id}/accept")->assertOk();

        $this->assertNull($accepted->json('data.room'));
        $this->assertSame($me->profile_code, $accepted->json('data.offline_sync.partner_code'));
    }

    public function test_a_live_sync_invite_sent_from_the_website_opens_its_room(): void
    {
        [$me, $partner] = $this->pair();

        // What the website's sendInvite leaves behind: the pair's room in the
        // lobby and an invite that does not name it.
        $room = GameRoom::create([
            'room_code' => 'web-room', 'user_a_id' => $me->id, 'user_b_id' => $partner->id,
            'game_type' => 'sync', 'status' => 'lobby', 'question_ids' => [1, 2, 3, 4, 5],
            'user_a_answers' => [], 'user_b_answers' => [], 'current_question_index' => 0,
        ]);
        $invite = GameInvite::create([
            'sender_id' => $me->id, 'receiver_id' => $partner->id, 'game_type' => 'sync',
            'status' => 'pending', 'expires_at' => now()->addDay(),
        ]);

        $this->actingAsMember($partner);
        $this->postJson("/api/v1/games/invites/{$invite->id}/accept")->assertOk()
            ->assertJsonPath('data.room', ['type' => 'sync', 'code' => $room->room_code]);
    }

    /** $user has answered a Sync question in a game with $with. */
    protected function answeredSync(User $user, User $with): void
    {
        $session = KoodalSyncSession::create([
            'user_one_id' => min($user->id, $with->id), 'user_two_id' => max($user->id, $with->id),
            'status' => 'completed', 'user_one_score' => 0, 'user_two_score' => 0, 'matched_count' => 0,
        ]);

        KoodalSyncAnswer::create([
            'session_id' => $session->id, 'question_id' => KoodalSyncQuestion::firstOrFail()->id,
            'user_id' => $user->id, 'selected_option' => 'A',
        ]);
    }
}
