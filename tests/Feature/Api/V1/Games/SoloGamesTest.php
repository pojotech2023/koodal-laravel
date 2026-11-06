<?php

namespace Tests\Feature\Api\V1\Games;

use App\Models\KoodalGameScore;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\Concerns\PlaysGames;
use Tests\TestCase;

class SoloGamesTest extends TestCase
{
    use PlaysGames, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->bootGames();
    }

    public function test_memory_is_graded_by_the_server_not_the_client(): void
    {
        [$a, $b] = $this->pair();
        $this->actingAsMember($a);

        $round = $this->postJson("/api/v1/games/partners/{$b->profile_code}/solo/memory/start")->assertOk()->json('data');
        $this->assertCount(7, $round['sequence']);
        $this->assertCount(4, $round['quiz']);

        // The answer key is not in the response.
        $this->assertStringNotContainsString('"key"', json_encode($round));

        $key = Cache::get("games:solo:memory:{$a->id}:{$round['session_id']}")['key'];
        $answers = collect($key)->map(fn ($id, $pos) => ['position' => $pos, 'symbol_id' => $id])->values()->all();

        // Three right, one wrong.
        $answers[3]['symbol_id'] = 'wrong';
        $result = $this->postJson("/api/v1/games/partners/{$b->profile_code}/solo/memory/finish", [
            'session_id' => $round['session_id'], 'answers' => $answers, 'time_taken' => 20,
        ])->assertCreated();

        $this->assertSame(75, $result->json('data.score'));
        $this->assertSame(3, $result->json('data.correct_count'));
        $this->assertSame(75, KoodalGameScore::where('game_type', 'memory')->value('score'));
    }

    public function test_a_round_can_only_be_submitted_once(): void
    {
        [$a, $b] = $this->pair();
        $this->actingAsMember($a);
        $round = $this->postJson("/api/v1/games/partners/{$b->profile_code}/solo/memory/start")->json('data');
        $payload = ['session_id' => $round['session_id'], 'answers' => [['position' => 1, 'symbol_id' => 'x']]];

        $this->postJson("/api/v1/games/partners/{$b->profile_code}/solo/memory/finish", $payload)->assertCreated();
        $this->postJson("/api/v1/games/partners/{$b->profile_code}/solo/memory/finish", $payload)->assertStatus(410);
    }

    public function test_someone_elses_round_or_a_made_up_session_is_rejected(): void
    {
        [$a, $b] = $this->pair();
        $this->actingAsMember($a);
        $round = $this->postJson("/api/v1/games/partners/{$b->profile_code}/solo/spot/start")->json('data');

        $this->actAs($b);
        $this->postJson("/api/v1/games/partners/{$a->profile_code}/solo/spot/finish", [
            'session_id' => $round['session_id'], 'taps' => [],
        ])->assertStatus(410);
        $this->postJson("/api/v1/games/partners/{$a->profile_code}/solo/spot/finish", [
            'session_id' => 'made-up', 'taps' => [],
        ])->assertStatus(410);
    }

    public function test_spot_deals_exactly_ten_targets_and_grades_taps(): void
    {
        [$a, $b] = $this->pair();
        $this->actingAsMember($a);

        $round = $this->postJson("/api/v1/games/partners/{$b->profile_code}/solo/spot/start")->assertOk()->json('data');

        $this->assertCount(36, $round['grid']);
        $this->assertSame(10, count(array_keys($round['grid'], $round['target_symbol'], true)));

        $targets = Cache::get("games:solo:spot:{$a->id}:{$round['session_id']}")['targets'];
        $wrong = collect(range(0, 35))->diff($targets)->first();

        // 8 right, 2 wrong (one repeated tap is ignored): (8 - 2*0.5) / 10 = 70.
        $taps = array_merge(array_slice($targets, 0, 8), [$wrong, $wrong, collect(range(0, 35))->diff($targets)->last()]);
        $result = $this->postJson("/api/v1/games/partners/{$b->profile_code}/solo/spot/finish", [
            'session_id' => $round['session_id'], 'taps' => $taps, 'time_taken' => 30,
        ])->assertCreated();

        $this->assertSame(70, $result->json('data.score'));
        $this->assertSame(8, $result->json('data.correct_taps'));
        $this->assertSame(2, $result->json('data.wrong_taps'));
    }

    public function test_tapping_everything_cannot_score_above_zero_for_accuracy(): void
    {
        [$a, $b] = $this->pair();
        $this->actingAsMember($a);
        $round = $this->postJson("/api/v1/games/partners/{$b->profile_code}/solo/spot/start")->json('data');

        // 10 right + 26 wrong → 10 - 13 < 0 → floored at 0.
        $this->postJson("/api/v1/games/partners/{$b->profile_code}/solo/spot/finish", [
            'session_id' => $round['session_id'], 'taps' => range(0, 35),
        ])->assertCreated()->assertJsonPath('data.score', 0);
    }
}
