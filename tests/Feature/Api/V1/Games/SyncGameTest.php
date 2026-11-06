<?php

namespace Tests\Feature\Api\V1\Games;

use App\Models\GameRoom;
use App\Models\KoodalGameScore;
use App\Models\KoodalSyncAnswer;
use App\Models\KoodalSyncQuestion;
use App\Models\User;
use App\Services\Games\Rooms\SyncRoom;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\PlaysGames;
use Tests\TestCase;

class SyncGameTest extends TestCase
{
    use PlaysGames, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->bootGames();
        $this->seedSyncQuestions();
    }

    /** @return array{0: User, 1: User, 2: string} */
    protected function liveRoom(): array
    {
        [$a, $b] = $this->pair();
        $this->actingAsMember($a);
        $code = $this->postJson("/api/v1/games/partners/{$b->profile_code}/invites", ['game_type' => 'sync'])
            ->json('data.room.code');

        return [$a, $b, $code];
    }

    protected function startPlaying(User $a, User $b, string $code): void
    {
        $this->actAs($a);
        $this->postJson("/api/v1/games/rooms/sync/{$code}/ready")->assertOk();
        $this->actAs($b);
        $this->postJson("/api/v1/games/rooms/sync/{$code}/ready")->assertOk();
    }

    protected function answerCurrent(User $user, string $code, string $option): void
    {
        $this->actAs($user);
        $state = $this->getJson("/api/v1/games/rooms/sync/{$code}")->assertOk();
        $index = $state->json('data.game.current_index');
        $qid = $state->json("data.game.questions.{$index}.id");

        $this->postJson("/api/v1/games/rooms/sync/{$code}/answer", [
            'question_id' => $qid, 'selected_option' => $option,
        ])->assertOk();
    }

    public function test_a_room_has_ten_questions_and_starts_when_both_are_ready(): void
    {
        [$a, $b, $code] = $this->liveRoom();

        $this->actAs($a);
        $lobby = $this->getJson("/api/v1/games/rooms/sync/{$code}")->assertOk();
        $this->assertSame('lobby', $lobby->json('data.status'));
        $this->assertSame(10, $lobby->json('data.game.total'));
        $this->assertSame($b->profile_code, $lobby->json('data.partner.profile_code'));

        $this->postJson("/api/v1/games/rooms/sync/{$code}/ready")->assertOk();
        $this->assertSame('lobby', $this->getJson("/api/v1/games/rooms/sync/{$code}")->json('data.status'));

        $this->actAs($b);
        $this->postJson("/api/v1/games/rooms/sync/{$code}/ready")->assertOk();

        $this->assertSame('playing', $this->getJson("/api/v1/games/rooms/sync/{$code}")->json('data.status'));
    }

    /** @param  int[]  $ids */
    protected function categories(array $ids): array
    {
        $games = app(\App\Services\KoodalGameService::class);
        $questions = KoodalSyncQuestion::whereIn('id', $ids)->get();

        return [
            'food' => $questions->contains(fn ($q) => $games->isFoodQuestion($q)),
            'location' => $questions->contains(fn ($q) => $games->isLocationQuestion($q)),
        ];
    }

    public function test_a_lobby_room_without_food_or_location_questions_is_re_dealt_once(): void
    {
        [, , $code] = $this->liveRoom();

        // A room dealt before the rule: ten lifestyle questions.
        $room = GameRoom::where('room_code', $code)->firstOrFail();
        $room->update(['question_ids' => KoodalSyncQuestion::where('category', 'Lifestyle')->limit(10)->pluck('id')->all()]);

        $sync = app(SyncRoom::class);
        $dealt = $sync->ensureContent($room->fresh())->question_ids;

        $this->assertCount(10, $dealt);
        $this->assertSame(['food' => true, 'location' => true], $this->categories($dealt));

        // Settled: the next page load shows the same questions.
        $this->assertSame($dealt, $sync->ensureContent($room->fresh())->question_ids);
        $this->assertSame($dealt, $room->fresh()->question_ids);
    }

    public function test_a_room_is_not_re_dealt_mid_game_or_when_the_bank_cannot_do_better(): void
    {
        [$a, $b, $code] = $this->liveRoom();
        $sync = app(SyncRoom::class);

        // Short of ten, but the bank holds only these nine: a fresh deal is
        // no better, so the room keeps them (in the database as well).
        $nine = KoodalSyncQuestion::orderBy('id')->limit(9)->pluck('id')->all();
        KoodalSyncQuestion::whereNotIn('id', $nine)->update(['is_active' => false]);
        $room = GameRoom::where('room_code', $code)->firstOrFail();
        $room->update(['question_ids' => array_reverse($nine)]);

        $this->assertSame(array_reverse($nine), $sync->ensureContent($room->fresh())->question_ids);
        $this->assertSame(array_reverse($nine), $room->fresh()->question_ids);

        // Once the round is under way nothing is re-dealt, whatever the bank holds.
        KoodalSyncQuestion::query()->update(['is_active' => true]);
        $room->update(['question_ids' => KoodalSyncQuestion::where('category', 'Lifestyle')->limit(10)->pluck('id')->all()]);
        $ids = $room->fresh()->question_ids;
        $this->startPlaying($a, $b, $code);
        $this->assertSame($ids, $sync->ensureContent($room->fresh())->question_ids);
    }

    public function test_a_stranger_cannot_read_or_change_the_room(): void
    {
        [, , $code] = $this->liveRoom();
        $this->actAs($this->member());

        $this->getJson("/api/v1/games/rooms/sync/{$code}")->assertForbidden();
        $this->postJson("/api/v1/games/rooms/sync/{$code}/ready")->assertForbidden();
        $this->postJson("/api/v1/games/rooms/sync/{$code}/leave")->assertForbidden();
        $this->postJson("/api/v1/games/rooms/sync/{$code}/reset")->assertForbidden();
        $this->postJson("/api/v1/games/rooms/sync/{$code}/messages", ['message' => 'wow'])->assertForbidden();
        $this->postJson("/api/v1/games/rooms/sync/{$code}/answer", ['question_id' => 1, 'selected_option' => 'A'])->assertForbidden();
    }

    public function test_an_unknown_room_or_game_type_is_a_404(): void
    {
        $this->actingAsMember();

        $this->getJson('/api/v1/games/rooms/sync/does-not-exist')->assertNotFound();
        $this->getJson('/api/v1/games/rooms/chess/abc')->assertNotFound();
    }

    public function test_the_partners_answer_stays_hidden_until_both_have_answered(): void
    {
        [$a, $b, $code] = $this->liveRoom();
        $this->startPlaying($a, $b, $code);

        $this->answerCurrent($a, $code, 'A');

        $this->actAs($b);
        $beforeMine = $this->getJson("/api/v1/games/rooms/sync/{$code}")->json('data.game');
        $this->assertTrue($beforeMine['partner_answered']);
        $this->assertFalse($beforeMine['my_answered']);
        $this->assertSame([], $beforeMine['reveals']);
        $this->assertSame([], $beforeMine['my_answers']);

        $this->answerCurrent($b, $code, 'A');
        $after = $this->getJson("/api/v1/games/rooms/sync/{$code}")->json('data.game');
        $this->assertTrue($after['both_answered']);
        $this->assertSame([['index' => 0, 'is_match' => true]], $after['reveals']);
    }

    public function test_an_answer_must_be_for_the_current_question_with_a_real_option(): void
    {
        [$a, $b, $code] = $this->liveRoom();
        $this->startPlaying($a, $b, $code);
        $this->actAs($a);

        $state = $this->getJson("/api/v1/games/rooms/sync/{$code}");
        $second = $state->json('data.game.questions.1.id');
        $first = $state->json('data.game.questions.0.id');

        $this->postJson("/api/v1/games/rooms/sync/{$code}/answer", ['question_id' => $second, 'selected_option' => 'A'])
            ->assertStatus(409);
        $this->postJson("/api/v1/games/rooms/sync/{$code}/answer", ['question_id' => $first, 'selected_option' => 'Z'])
            ->assertStatus(422);
    }

    public function test_answers_are_refused_before_the_game_starts(): void
    {
        [$a, , $code] = $this->liveRoom();
        $this->actAs($a);

        $qid = $this->getJson("/api/v1/games/rooms/sync/{$code}")->json('data.game.questions.0.id');

        $this->postJson("/api/v1/games/rooms/sync/{$code}/answer", ['question_id' => $qid, 'selected_option' => 'A'])
            ->assertStatus(409);
    }

    public function test_the_round_only_advances_once_both_have_answered(): void
    {
        [$a, $b, $code] = $this->liveRoom();
        $this->startPlaying($a, $b, $code);

        $this->answerCurrent($a, $code, 'A');
        $this->postJson("/api/v1/games/rooms/sync/{$code}/next", ['from_index' => 0])->assertStatus(409);

        $this->answerCurrent($b, $code, 'B');
        $this->postJson("/api/v1/games/rooms/sync/{$code}/next", ['from_index' => 0])->assertOk()
            ->assertJsonPath('data.game.current_index', 1);

        // Both phones tapping "next" for question 0 must not skip question 1.
        $this->actAs($a);
        $this->postJson("/api/v1/games/rooms/sync/{$code}/next", ['from_index' => 0])->assertOk()
            ->assertJsonPath('data.game.current_index', 1);
    }

    public function test_a_full_game_scores_ten_points_per_match_and_records_one_vibe_score(): void
    {
        [$a, $b, $code] = $this->liveRoom();
        $this->startPlaying($a, $b, $code);

        // Agree on the first four questions, differ on the other six.
        for ($i = 0; $i < 10; $i++) {
            $this->answerCurrent($a, $code, 'A');
            $this->answerCurrent($b, $code, $i < 4 ? 'A' : 'B');
            $this->postJson("/api/v1/games/rooms/sync/{$code}/next", ['from_index' => $i])->assertOk();
        }

        $final = $this->getJson("/api/v1/games/rooms/sync/{$code}")->json('data');

        $this->assertSame('completed', $final['status']);
        $this->assertSame(40, $final['game']['score']);
        $this->assertSame(100, $final['game']['max_score']);
        $this->assertCount(10, $final['game']['review']);
        $this->assertTrue($final['game']['review'][0]['is_match']);
        $this->assertFalse($final['game']['review'][9]['is_match']);

        $this->assertSame(1, KoodalGameScore::where('game_type', 'sync')->count());
        $this->assertSame(20, KoodalSyncAnswer::count());

        $this->actAs($a);
        $hub = $this->getJson("/api/v1/games/partners/{$b->profile_code}")->json('data');
        $this->assertSame(40, $hub['vibe']['percentage']);
    }

    public function test_leaving_abandons_the_room_and_reset_deals_a_fresh_round(): void
    {
        [$a, $b, $code] = $this->liveRoom();
        $this->startPlaying($a, $b, $code);
        $this->answerCurrent($a, $code, 'A');

        $this->actAs($b);
        $this->postJson("/api/v1/games/rooms/sync/{$code}/leave")->assertOk()
            ->assertJsonPath('data.status', 'abandoned');

        $this->postJson("/api/v1/games/rooms/sync/{$code}/reset")->assertOk()
            ->assertJsonPath('data.status', 'lobby')
            ->assertJsonPath('data.game.current_index', 0)
            ->assertJsonPath('data.game.my_answers', []);
    }

    public function test_polling_with_the_current_version_returns_no_change(): void
    {
        [$a, , $code] = $this->liveRoom();
        $this->actAs($a);

        $first = $this->getJson("/api/v1/games/rooms/sync/{$code}")->json('data');
        $this->assertTrue($first['changed']);

        $this->getJson("/api/v1/games/rooms/sync/{$code}?since_version={$first['version']}")
            ->assertOk()
            ->assertJsonPath('data.changed', false)
            ->assertJsonMissingPath('data.game');

        $this->postJson("/api/v1/games/rooms/sync/{$code}/ready");
        $this->getJson("/api/v1/games/rooms/sync/{$code}?since_version={$first['version']}")
            ->assertJsonPath('data.changed', true);
    }

    public function test_a_blocked_partner_cannot_keep_playing(): void
    {
        [$a, $b, $code] = $this->liveRoom();
        $b->blocks()->create(['blocked_user_id' => $a->id]);

        $this->actAs($a);
        $this->postJson("/api/v1/games/rooms/sync/{$code}/ready")->assertForbidden();
        // Reading and leaving stay possible so nobody is trapped in a room.
        $this->getJson("/api/v1/games/rooms/sync/{$code}")->assertOk();
        $this->postJson("/api/v1/games/rooms/sync/{$code}/leave")->assertOk();
    }

    // --- Offline mode ------------------------------------------------------

    public function test_offline_questions_and_submission_notify_a_partner_who_has_not_played(): void
    {
        [$a, $b] = $this->pair();
        $this->actingAsMember($a);

        $questions = $this->getJson("/api/v1/games/partners/{$b->profile_code}/sync")->assertOk();
        $this->assertCount(10, $questions->json('data.questions'));
        $this->assertFalse($questions->json('data.partner_already_played'));

        $answers = collect($questions->json('data.questions'))->mapWithKeys(fn ($q) => [$q['id'] => 'A'])->all();
        $result = $this->postJson("/api/v1/games/partners/{$b->profile_code}/sync", ['answers' => $answers])
            ->assertCreated();

        $this->assertTrue($result->json('data.waiting_for_partner'));
        $this->assertTrue($result->json('data.invite_sent'));
        $this->assertDatabaseHas('notifications', ['user_id' => $b->id, 'type' => 'game_invite']);

        // The partner is served the same questions so the sets can be compared.
        $this->actAs($b);
        $theirs = $this->getJson("/api/v1/games/partners/{$a->profile_code}/sync")->json('data');
        $this->assertTrue($theirs['partner_already_played']);
        $this->assertEqualsCanonicalizing(
            array_keys($answers),
            collect($theirs['questions'])->pluck('id')->all(),
        );

        $final = $this->postJson("/api/v1/games/partners/{$a->profile_code}/sync", [
            'answers' => collect($theirs['questions'])->mapWithKeys(fn ($q) => [$q['id'] => 'A'])->all(),
        ])->assertCreated();

        $this->assertFalse($final->json('data.waiting_for_partner'));
        $this->assertSame(100, $final->json('data.score'));
        $this->assertDatabaseHas('notifications', ['user_id' => $a->id, 'type' => 'game_result']);
    }

    public function test_offline_submission_rejects_unknown_questions_and_options(): void
    {
        [$a, $b] = $this->pair();
        $this->actingAsMember($a);
        $id = $this->getJson("/api/v1/games/partners/{$b->profile_code}/sync")->json('data.questions.0.id');

        $this->postJson("/api/v1/games/partners/{$b->profile_code}/sync", ['answers' => [$id => 'Z']])->assertStatus(422);
        $this->postJson("/api/v1/games/partners/{$b->profile_code}/sync", ['answers' => [999999 => 'A']])->assertStatus(422);
        $this->postJson("/api/v1/games/partners/{$b->profile_code}/sync", ['answers' => []])->assertStatus(422);
    }
}
