<?php

namespace App\Services\Games;

use App\Models\User;
use App\Services\KoodalGameService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * The two single-player games (sequence Memory, symbol Spot) whose results
 * count toward a pair's Vibe Meter.
 *
 * The website lets the browser grade itself — it POSTs `correct_count` and the
 * server stores whatever it is told. Here the server deals the round, keeps the
 * answer key in cache under a one-shot session id, and grades the submission
 * itself. Spot also deals exactly ten targets; the website filled the grid
 * from a pool that includes the target symbol, so a board could hold more than
 * the ten the player was asked to find.
 */
class SoloGames
{
    private const POOL = [
        ['id' => 'star', 'symbol' => '⭐', 'name' => 'Star / நட்சத்திரம்'],
        ['id' => 'apple', 'symbol' => '🍎', 'name' => 'Apple / ஆப்பிள்'],
        ['id' => 'blue', 'symbol' => '🔵', 'name' => 'Blue Circle / நீல வட்டம்'],
        ['id' => 'cat', 'symbol' => '🐱', 'name' => 'Cat / பூனை'],
        ['id' => 'moon', 'symbol' => '🌙', 'name' => 'Crescent Moon / பிறை நிலவு'],
        ['id' => 'heart', 'symbol' => '❤️', 'name' => 'Red Heart / சிவப்பு இதயம்'],
        ['id' => 'clover', 'symbol' => '🍀', 'name' => 'Four-Leaf Clover / அதிர்ஷ்ட புல்'],
        ['id' => 'flower', 'symbol' => '🌺', 'name' => 'Hibiscus / செம்பருத்தி'],
        ['id' => 'sun', 'symbol' => '☀️', 'name' => 'Bright Sun / சூரியன்'],
    ];

    private const SPOT_SYMBOLS = ['○', '△', '□', '☆', '🐱', '🌙', '❤️'];
    private const SPOT_TARGET = '⭐';
    private const SPOT_CELLS = 36;
    private const SPOT_TARGETS = 10;

    private const MEMORY_POSITIONS = [1, 3, 5, 6];
    private const MEMORY_DISPLAY_SECONDS = 5;

    public function __construct(protected KoodalGameService $games) {}

    /** @return array<string, mixed> */
    public function startMemory(User $user, User $partner): array
    {
        $sequence = collect(self::POOL)->shuffle()->take(7)->values();

        $quiz = [];
        $key = [];
        foreach (self::MEMORY_POSITIONS as $pos) {
            $target = $sequence[$pos - 1];
            $options = $sequence->shuffle()->take(4)->values();

            if (! $options->contains('id', $target['id'])) {
                $options[0] = $target;
            }

            $quiz[] = [
                'position' => $pos,
                'prompt' => "What was the #{$pos} symbol in sequence?",
                'options' => $options->shuffle()->values()->all(),
            ];
            $key[$pos] = $target['id'];
        }

        $session = $this->remember($user, $partner, 'memory', ['key' => $key]);

        return [
            'session_id' => $session,
            'display_seconds' => self::MEMORY_DISPLAY_SECONDS,
            'sequence' => $sequence->all(),
            'quiz' => $quiz,
        ];
    }

    /**
     * @param  array<int, array{position: int, symbol_id: string}>  $answers
     * @return array<string, mixed>
     */
    public function finishMemory(User $user, User $partner, string $sessionId, array $answers, int $timeTaken): array
    {
        $deal = $this->take($user, $partner, 'memory', $sessionId);

        $given = collect($answers)->mapWithKeys(fn ($a) => [(int) $a['position'] => $a['symbol_id']]);
        $total = count($deal['key']);
        $correct = collect($deal['key'])->filter(fn ($id, $pos) => $given->get($pos) === $id)->count();
        $score = (int) round($correct / max(1, $total) * 100);

        $this->games->recordGameScore($user->id, $partner->id, 'memory', $score, 100, [
            'correct_count' => $correct,
            'total_questions' => $total,
            'time_taken' => $timeTaken,
        ]);

        return ['score' => $score, 'correct_count' => $correct, 'total_questions' => $total];
    }

    /** @return array<string, mixed> */
    public function startSpot(User $user, User $partner): array
    {
        $targets = collect(range(0, self::SPOT_CELLS - 1))->shuffle()->take(self::SPOT_TARGETS)->sort()->values()->all();

        $grid = [];
        for ($i = 0; $i < self::SPOT_CELLS; $i++) {
            $grid[] = in_array($i, $targets, true)
                ? self::SPOT_TARGET
                : self::SPOT_SYMBOLS[array_rand(self::SPOT_SYMBOLS)];
        }

        $session = $this->remember($user, $partner, 'spot', ['targets' => $targets]);

        return [
            'session_id' => $session,
            'grid' => $grid,
            'columns' => 6,
            'target_symbol' => self::SPOT_TARGET,
            'target_count' => self::SPOT_TARGETS,
        ];
    }

    /**
     * @param  array<int, int>  $taps  cell indices, in the order tapped
     * @return array<string, mixed>
     */
    public function finishSpot(User $user, User $partner, string $sessionId, array $taps, int $timeTaken): array
    {
        $deal = $this->take($user, $partner, 'spot', $sessionId);

        $taps = array_values(array_unique(array_map('intval', $taps)));
        $correct = count(array_intersect($taps, $deal['targets']));
        $wrong = count($taps) - $correct;
        $total = count($deal['targets']);

        $accuracy = max(0, $correct - $wrong * 0.5);
        $score = (int) min(100, round($accuracy / $total * 100));

        $this->games->recordGameScore($user->id, $partner->id, 'spot', $score, 100, [
            'correct_taps' => $correct,
            'wrong_taps' => $wrong,
            'total_targets' => $total,
            'time_taken' => $timeTaken,
        ]);

        return ['score' => $score, 'correct_taps' => $correct, 'wrong_taps' => $wrong, 'total_targets' => $total];
    }

    protected function remember(User $user, User $partner, string $game, array $deal): string
    {
        $id = (string) Str::uuid();
        Cache::put(
            $this->cacheKey($user, $game, $id),
            $deal + ['partner_id' => $partner->id],
            now()->addMinutes((int) config('game.timing.solo_session_ttl_minutes', 30)),
        );

        return $id;
    }

    /** Single use: the session is gone after the first submission, right or not. */
    protected function take(User $user, User $partner, string $game, string $id): array
    {
        $deal = Cache::pull($this->cacheKey($user, $game, $id));

        abort_unless($deal && $deal['partner_id'] === $partner->id, 410, 'This round has expired. Start a new one.');

        return $deal;
    }

    protected function cacheKey(User $user, string $game, string $id): string
    {
        return "games:solo:{$game}:{$user->id}:{$id}";
    }
}
