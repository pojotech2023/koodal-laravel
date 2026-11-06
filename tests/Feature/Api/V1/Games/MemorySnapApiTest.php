<?php

namespace Tests\Feature\Api\V1\Games;

use App\Models\KoodalGameScore;
use App\Models\MemorySnapGame;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\PlaysGames;
use Tests\TestCase;

class MemorySnapApiTest extends TestCase
{
    use PlaysGames, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->bootGames();
    }

    /** @return array{0: User, 1: User, 2: string} */
    protected function playingGame(): array
    {
        [$a, $b] = $this->pair();
        $this->actingAsMember($a);
        $code = $this->postJson("/api/v1/games/partners/{$b->profile_code}/invites", ['game_type' => 'memory_snap'])
            ->assertCreated()->json('data.room.code');

        $this->postJson("/api/v1/games/rooms/memory_snap/{$code}/ready")->assertOk();
        $this->actAs($b);
        $this->postJson("/api/v1/games/rooms/memory_snap/{$code}/ready")->assertOk();

        return [$a, $b, $code];
    }

    /** Jump the clock so the photo-recall phase is over and the question is showing. */
    protected function skipRecall(string $code): void
    {
        MemorySnapGame::where('code', $code)->update(['image_started_at' => microtime(true) - 30]);
    }

    protected function answer(User $user, string $code, int $index, string $option)
    {
        $this->actAs($user);

        return $this->postJson("/api/v1/games/rooms/memory_snap/{$code}/answer", [
            'question_index' => $index, 'answer' => $option,
        ]);
    }

    public function test_both_ready_starts_a_shared_countdown(): void
    {
        [$a, , $code] = $this->playingGame();
        $this->actAs($a);

        $game = $this->getJson("/api/v1/games/rooms/memory_snap/{$code}")->assertOk()->json('data');

        $this->assertSame('playing', $game['status']);
        $this->assertSame('countdown', $game['game']['phase']);
        $this->assertGreaterThan(0, $game['game']['phase_seconds_left']);
        $this->assertSame(5, $game['game']['total']);
        $this->assertGreaterThan(microtime(true), $game['game']['image_started_at']);
    }

    public function test_the_question_text_and_options_are_withheld_until_the_photo_phase_is_over(): void
    {
        [$a, , $code] = $this->playingGame();
        $this->actAs($a);

        $during = $this->getJson("/api/v1/games/rooms/memory_snap/{$code}")->json('data.game');
        $this->assertArrayNotHasKey('text', $during['question']);
        $this->assertArrayNotHasKey('options', $during['question']);

        MemorySnapGame::where('code', $code)->update(['image_started_at' => microtime(true) - 5]);
        $this->assertSame('image', $this->getJson("/api/v1/games/rooms/memory_snap/{$code}")->json('data.game.phase'));

        $this->skipRecall($code);
        $question = $this->getJson("/api/v1/games/rooms/memory_snap/{$code}")->json('data.game');
        $this->assertSame('question', $question['phase']);
        $this->assertArrayHasKey('text', $question['question']);
        $this->assertSame(['A', 'B', 'C', 'D'], array_keys($question['question']['options']));
    }

    public function test_the_correct_answer_is_never_sent_before_the_reveal(): void
    {
        [$a, $b, $code] = $this->playingGame();
        $this->skipRecall($code);

        $this->actAs($a);
        $question = $this->getJson("/api/v1/games/rooms/memory_snap/{$code}")->json('data.game.question');
        $this->assertArrayNotHasKey('correct_option', $question);

        // Even the raw response must not contain the key anywhere.
        $raw = $this->getJson("/api/v1/games/rooms/memory_snap/{$code}")->getContent();
        $this->assertStringNotContainsString('correct_option', str_replace('"correct_option":null', '', $raw));

        $this->answer($a, $code, 0, 'A')->assertOk();
        $this->actAs($a);
        $this->assertArrayNotHasKey('correct_option', $this->getJson("/api/v1/games/rooms/memory_snap/{$code}")->json('data.game.question'));

        $this->answer($b, $code, 0, 'B')->assertOk();
        $reveal = $this->getJson("/api/v1/games/rooms/memory_snap/{$code}")->json('data.game');
        $this->assertSame('reveal', $reveal['phase']);
        $this->assertSame('A', $reveal['question']['correct_option']);
        // Viewed as player two, the partner is player one, who answered A.
        $this->assertSame('A', $reveal['question']['partner_answer']);
    }

    public function test_the_players_role_comes_from_the_token_not_the_request(): void
    {
        [$a, $b, $code] = $this->playingGame();
        $this->skipRecall($code);

        // A forged `role` is ignored: the answer lands on the caller's side.
        $this->actAs($a);
        $this->postJson("/api/v1/games/rooms/memory_snap/{$code}/answer", [
            'question_index' => 0, 'answer' => 'A', 'role' => 2,
        ])->assertOk();

        $game = MemorySnapGame::where('code', $code)->firstOrFail();
        $this->assertSame(['0' => 'A'], $game->user_one_answers);
        $this->assertEmpty($game->user_two_answers);
    }

    public function test_a_stranger_cannot_read_or_answer(): void
    {
        [, , $code] = $this->playingGame();
        $this->actAs($this->member());

        $this->getJson("/api/v1/games/rooms/memory_snap/{$code}")->assertForbidden();
        $this->postJson("/api/v1/games/rooms/memory_snap/{$code}/answer", ['question_index' => 0, 'answer' => 'A'])->assertForbidden();
    }

    public function test_a_question_scores_once_even_if_answered_again(): void
    {
        [$a, , $code] = $this->playingGame();
        $this->skipRecall($code);

        $this->answer($a, $code, 0, 'A')->assertOk();
        $this->answer($a, $code, 0, 'A')->assertOk();
        $this->answer($a, $code, 0, 'B')->assertOk();

        $game = MemorySnapGame::where('code', $code)->firstOrFail();
        $this->assertSame(1, $game->user_one_score);
        $this->assertSame('A', $game->user_one_answers['0']);
    }

    public function test_answers_must_target_the_current_question_with_a_valid_letter(): void
    {
        [$a, , $code] = $this->playingGame();
        $this->skipRecall($code);

        $this->answer($a, $code, 3, 'A')->assertStatus(409);
        $this->answer($a, $code, 0, 'E')->assertStatus(422);
    }

    public function test_the_round_advances_after_the_reveal_and_the_game_completes(): void
    {
        [$a, $b, $code] = $this->playingGame();

        for ($i = 0; $i < 5; $i++) {
            $this->skipRecall($code);
            $this->answer($a, $code, $i, 'A')->assertOk();
            $this->answer($b, $code, $i, $i < 2 ? 'A' : 'B')->assertOk();

            // Reveal window is still open, so the question has not moved on.
            $this->actAs($a);
            $this->assertSame($i, $this->getJson("/api/v1/games/rooms/memory_snap/{$code}")->json('data.game.current_index'));

            MemorySnapGame::where('code', $code)->update(['image_started_at' => microtime(true) - 1]);
            $this->getJson("/api/v1/games/rooms/memory_snap/{$code}")->assertOk();
        }

        $this->actAs($a);
        $final = $this->getJson("/api/v1/games/rooms/memory_snap/{$code}")->json('data');

        $this->assertSame('completed', $final['status']);
        $this->assertSame(['me' => 5, 'partner' => 2], $final['game']['scores']);
        // Combined: (100% + 40%) / 2.
        $this->assertSame(70, $final['game']['combined_pct']);
        $this->assertCount(5, $final['game']['review']);
        $this->assertSame(1, KoodalGameScore::where('game_type', 'memory_snap')->count());
    }

    public function test_reset_returns_to_the_lobby_with_fresh_questions(): void
    {
        [$a, , $code] = $this->playingGame();
        $this->skipRecall($code);
        $this->answer($a, $code, 0, 'A')->assertOk();

        $this->postJson("/api/v1/games/rooms/memory_snap/{$code}/reset")->assertOk()
            ->assertJsonPath('data.status', 'lobby')
            ->assertJsonPath('data.game.scores.me', 0)
            ->assertJsonPath('data.you.ready', false);
    }

    public function test_celebration_keywords_in_chat_raise_an_event(): void
    {
        [$a, , $code] = $this->playingGame();
        $this->actAs($a);

        $this->postJson("/api/v1/games/rooms/memory_snap/{$code}/messages", ['message' => 'super'])->assertOk();

        $this->assertSame(
            'SUPER!',
            $this->getJson("/api/v1/games/rooms/memory_snap/{$code}")->json('data.game.celebration.word'),
        );

        $this->postJson("/api/v1/games/rooms/memory_snap/{$code}/messages", ['message' => 'All the Best 💖'])->assertOk();
        $celebration = $this->getJson("/api/v1/games/rooms/memory_snap/{$code}")->json('data.game.celebration');
        $this->assertSame('ALL THE BEST!', $celebration['word']);
        $this->assertSame('hearts', $celebration['type']);
        $this->assertSame(1, $celebration['sender_role']);
        $this->assertSame($a->name, $celebration['sender_name']);
    }
}
