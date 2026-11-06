<?php

namespace App\Services\Games\Rooms;

use App\Models\MemorySnapGame;
use App\Models\User;
use App\Services\Games\GameAssets;
use App\Services\Games\MemorySnapQuestionBank;
use App\Services\KoodalGameService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Memory Snap: a photo is shown for ten seconds, then both players are asked
 * about it; five rounds, score = share of correct answers.
 *
 * What the API changes on purpose: the player's slot comes from the
 * authenticated user (the website trusts a `role` field), `correct_option` is
 * withheld until the reveal (the website serialises the whole row, answers
 * included), and a question can be answered once (the website re-scored a
 * repeated submission).
 */
class MemorySnapRoom implements RoomDriver
{
    use BuildsEnvelope;

    public function __construct(
        protected MemorySnapQuestionBank $bank,
        protected KoodalGameService $games,
    ) {}

    public function type(): string
    {
        return 'memory_snap';
    }

    public function find(string $code): Model
    {
        return MemorySnapGame::where('code', strtoupper($code))->firstOrFail();
    }

    public function code(Model $room): string
    {
        return $room->code;
    }

    public function findActiveBetween(User $a, User $b): ?Model
    {
        return MemorySnapGame::query()
            ->whereIn('status', ['waiting', 'playing'])
            ->where(fn ($q) => $q
                ->where(fn ($x) => $x->where('user_one_id', $a->id)->where('user_two_id', $b->id))
                ->orWhere(fn ($x) => $x->where('user_one_id', $b->id)->where('user_two_id', $a->id)))
            ->latest('id')
            ->first();
    }

    public function create(User $host, User $guest): Model
    {
        return $this->make($host, $guest);
    }

    /** A room with seat 2 open, for the website's share-a-link flow. */
    public function open(User $host): MemorySnapGame
    {
        return $this->make($host, null);
    }

    /**
     * Put $viewer in seat 2 if it is still free (re-checked under the lock, so
     * two people opening the same link cannot both take it). A player is
     * returned as they are; anyone else gets a 403.
     */
    public function seatGuest(MemorySnapGame $game, User $viewer): MemorySnapGame
    {
        return $this->locked($game, function (MemorySnapGame $g) use ($viewer) {
            if (in_array($viewer->id, [$g->user_one_id, $g->user_two_id], true)) {
                return;
            }

            abort_unless($g->user_two_id === null && $g->status === 'waiting', 403, 'This game already has two players.');

            $g->user_two_id = $viewer->id;
            $g->user_two_name = $viewer->name;
            $g->user_two_avatar = $viewer->primary_photo_url ?? null;
            $g->save();
        });
    }

    protected function make(User $host, ?User $guest): MemorySnapGame
    {
        do {
            $code = strtoupper(Str::random(6));
        } while (MemorySnapGame::where('code', $code)->exists());

        $questions = $this->bank->five();

        return MemorySnapGame::create([
            'code' => $code,
            'user_one_id' => $host->id,
            'user_two_id' => $guest?->id,
            'user_one_name' => $host->name,
            'user_two_name' => $guest?->name ?? 'Waiting for partner',
            'user_one_avatar' => $host->primary_photo_url ?? null,
            'user_two_avatar' => $guest?->primary_photo_url ?? null,
            'status' => 'waiting',
            'user_one_joined' => false,
            'user_two_joined' => false,
            'user_one_ready' => false,
            'user_two_ready' => false,
            'challenge_data' => $this->challengeData($questions),
            'questions_data' => $questions,
            'current_question_index' => 0,
            'user_one_answers' => [],
            'user_two_answers' => [],
            'user_one_score' => 0,
            'user_two_score' => 0,
            'chat_messages' => [],
        ]);
    }

    protected function challengeData(array $questions): array
    {
        return [
            'id' => 1,
            'title' => '1v1 Memory Challenge',
            'image_url' => $questions[0]['image_url'] ?? '/image/03_Koodal_Memory.png',
        ];
    }

    protected function slot(MemorySnapGame $game, User $viewer): int
    {
        return match ($viewer->id) {
            $game->user_one_id => 1,
            $game->user_two_id => 2,
            default => abort(403, 'You are not a player in this game.'),
        };
    }

    public function partnerOf(Model $room, User $viewer): ?User
    {
        $slot = $this->slot($room, $viewer);

        return User::with('profile.primaryPhoto')->find($slot === 1 ? $room->user_two_id : $room->user_one_id);
    }

    public function join(Model $room, User $viewer): Model
    {
        return $this->locked($room, function (MemorySnapGame $g) use ($viewer) {
            $slot = $this->slot($g, $viewer);
            if ($slot === 1) {
                $g->user_one_joined = true;
            } else {
                $g->user_two_joined = true;
            }
            $g->save();
        });
    }

    public function ready(Model $room, User $viewer): Model
    {
        return $this->locked($room, function (MemorySnapGame $g) use ($viewer) {
            $slot = $this->slot($g, $viewer);

            if (in_array($g->status, ['playing', 'completed', 'abandoned'], true)) {
                return;
            }

            if ($slot === 1) {
                $g->user_one_ready = $g->user_one_joined = true;
            } else {
                $g->user_two_ready = $g->user_two_joined = true;
            }

            if ($g->user_one_ready && $g->user_two_ready) {
                $g->status = 'playing';
                $g->current_question_index = 0;
                // A shared start instant: both phones count down to the same
                // server timestamp, so the photo appears for both at once.
                $g->image_started_at = microtime(true) + (float) config('game.timing.memory_snap_countdown', 3.5);
            }

            $g->save();
        });
    }

    public function answer(MemorySnapGame $room, User $viewer, int $questionIndex, string $option): MemorySnapGame
    {
        return $this->locked($room, function (MemorySnapGame $g) use ($viewer, $questionIndex, $option) {
            $slot = $this->slot($g, $viewer);

            abort_unless($g->status === 'playing', 409, 'The game is not in progress.');
            abort_unless($questionIndex === (int) $g->current_question_index, 409, 'That is not the current question.');

            $option = strtoupper($option);
            abort_unless(in_array($option, ['A', 'B', 'C', 'D'], true), 422, 'Choose A, B, C or D.');

            $key = (string) $questionIndex;
            $one = $g->user_one_answers ?? [];
            $two = $g->user_two_answers ?? [];
            $mine = $slot === 1 ? $one : $two;

            if (isset($mine[$key])) {
                return; // already answered: idempotent, and never scored twice
            }

            $mine[$key] = $option;

            $correct = strtoupper($g->questions_data[$questionIndex]['correct_option'] ?? 'A');
            if ($slot === 1) {
                $g->user_one_answers = $mine;
                $one = $mine;
                $g->user_one_score += $option === $correct ? 1 : 0;
            } else {
                $g->user_two_answers = $mine;
                $two = $mine;
                $g->user_two_score += $option === $correct ? 1 : 0;
            }

            if (isset($one[$key], $two[$key])) {
                $g->image_started_at = microtime(true) + (float) config('game.timing.memory_snap_reveal', 2.5);
            }

            $g->save();
        });
    }

    public function leave(Model $room, User $viewer): Model
    {
        return $this->locked($room, function (MemorySnapGame $g) use ($viewer) {
            $this->slot($g, $viewer);

            if (! in_array($g->status, ['completed', 'abandoned'], true)) {
                $g->status = 'abandoned';
                $g->save();
            }
        });
    }

    public function reset(Model $room, User $viewer): Model
    {
        return $this->locked($room, function (MemorySnapGame $g) use ($viewer) {
            $this->slot($g, $viewer);

            $questions = $this->bank->five();
            $g->fill([
                'status' => 'waiting',
                'user_one_joined' => false,
                'user_two_joined' => false,
                'user_one_ready' => false,
                'user_two_ready' => false,
                'challenge_data' => $this->challengeData($questions),
                'questions_data' => $questions,
                'current_question_index' => 0,
                'user_one_answers' => [],
                'user_two_answers' => [],
                'user_one_score' => 0,
                'user_two_score' => 0,
                'celebration_event' => null,
                'image_started_at' => null,
                'next_game_url' => null,
            ]);
            $g->save();
        });
    }

    public function sendMessage(Model $room, User $viewer, string $text): void
    {
        $this->locked($room, function (MemorySnapGame $g) use ($viewer, $text) {
            $slot = $this->slot($g, $viewer);

            $chats = $g->chat_messages ?? [];
            $chats[] = [
                'id' => Str::random(8),
                'role' => $slot,
                'sender' => $slot === 1 ? $g->user_one_name : $g->user_two_name,
                'text' => $text,
                'time' => now()->toIso8601String(),
            ];
            $g->chat_messages = array_slice($chats, -50);

            $triggers = [
                'super' => ['SUPER!', 'confetti'], 'fantastic' => ['FANTASTIC!', 'stars_confetti'],
                'awesome' => ['AWESOME!', 'hearts'], 'good' => ['GOOD JOB!', 'stars_confetti'],
                'wow' => ['WOW!', 'stars_confetti'], 'excellent' => ['EXCELLENT!', 'stars_confetti'],
                'nice' => ['NICE PLAY!', 'stars_confetti'], 'all the best' => ['ALL THE BEST!', 'hearts'],
                'well played' => ['WELL PLAYED!', 'stars_confetti'], 'great' => ['GREAT MEMORY!', 'stars_confetti'],
            ];
            $lower = mb_strtolower($text);
            foreach ($triggers as $keyword => [$word, $kind]) {
                if (str_contains($lower, $keyword)) {
                    $g->celebration_event = [
                        'id' => Str::random(8),
                        'word' => $word,
                        'raw_text' => $text,
                        'sender_role' => $slot,
                        'sender_name' => $slot === 1 ? $g->user_one_name : $g->user_two_name,
                        'type' => $kind,
                        'timestamp' => microtime(true),
                    ];
                    break;
                }
            }

            $g->save();
        });
    }

    public function markRead(Model $room, User $viewer): void
    {
        $this->slot($room, $viewer);
    }

    public function state(Model $room, User $viewer): array
    {
        $this->slot($room, $viewer);

        /** @var MemorySnapGame $game */
        $game = $room->fresh();

        // Only a running round can need advancing, so only that pays for a lock.
        if ($game->status === 'playing') {
            $game = $this->locked($game, fn (MemorySnapGame $g) => $this->tick($g));
        }

        $slot = $this->slot($game, $viewer);

        $mine = $slot === 1 ? ($game->user_one_answers ?? []) : ($game->user_two_answers ?? []);
        $theirs = $slot === 1 ? ($game->user_two_answers ?? []) : ($game->user_one_answers ?? []);

        $chat = collect($game->chat_messages ?? [])->map(fn ($m) => [
            'id' => $m['id'] ?? null,
            'sender' => (int) ($m['role'] ?? 0) === $slot ? 'me' : 'partner',
            'text' => $m['text'],
            'type' => 'text',
            'at' => $m['time'] ?? null,
            'read' => false,
        ])->values()->all();

        return $this->envelope(
            'memory_snap',
            $game->code,
            $this->status($game->status),
            $viewer,
            $this->partnerOf($game, $viewer),
            $slot,
            (bool) ($slot === 1 ? $game->user_one_ready : $game->user_two_ready),
            (bool) ($slot === 1 ? $game->user_two_ready : $game->user_one_ready),
            $chat,
            $this->gamePayload($game, $slot, $mine, $theirs),
        );
    }

    protected function status(string $status): string
    {
        return $status === 'waiting' ? 'lobby' : $status;
    }

    /** 1 or 2 for a player; 403 for anyone else. */
    public function seat(MemorySnapGame $game, User $viewer): int
    {
        return $this->slot($game, $viewer);
    }

    /** Re-read the game and, if a round's reveal is over, move it on (under the lock). */
    public function advance(MemorySnapGame $game): MemorySnapGame
    {
        $game = $game->fresh();

        return $game->status === 'playing'
            ? $this->locked($game, fn (MemorySnapGame $g) => $this->tick($g))
            : $game;
    }

    /** Advance the round when its reveal window has passed. Runs under the row lock. */
    protected function tick(MemorySnapGame $g): void
    {
        if ($g->status !== 'playing' || ! $g->image_started_at) {
            return;
        }

        $now = microtime(true);
        $i = (string) $g->current_question_index;
        $both = isset(($g->user_one_answers ?? [])[$i], ($g->user_two_answers ?? [])[$i]);

        if (! $both || $now < $g->image_started_at) {
            return;
        }

        if ($g->current_question_index + 1 >= count($g->questions_data ?? [])) {
            $g->status = 'completed';
            $this->recordScore($g);
        } else {
            $g->current_question_index++;
            $g->image_started_at = $now;
        }

        $g->save();
    }

    protected function recordScore(MemorySnapGame $g): void
    {
        if (! $g->user_one_id || ! $g->user_two_id) {
            return;
        }

        $total = max(1, count($g->questions_data ?? []));
        $combined = (int) round((round($g->user_one_score / $total * 100) + round($g->user_two_score / $total * 100)) / 2);

        $this->games->recordGameScore($g->user_one_id, $g->user_two_id, 'memory_snap', $combined, 100, [
            'score' => $g->user_one_score,
            'user_one_id' => $g->user_one_id,
            'user_two_id' => $g->user_two_id,
            'user_one_score' => $g->user_one_score,
            'user_two_score' => $g->user_two_score,
            'total' => $total,
            'combined_pct' => $combined,
        ]);
    }

    /** @return array<string, mixed> */
    protected function gamePayload(MemorySnapGame $g, int $slot, array $mine, array $theirs): array
    {
        $questions = $g->questions_data ?? [];
        $index = (int) $g->current_question_index;
        $key = (string) $index;
        $now = microtime(true);
        $completed = $g->status === 'completed';
        $both = isset($mine[$key], $theirs[$key]);

        $recall = (float) config('game.timing.memory_snap_recall', 10.0);
        $diff = $g->image_started_at ? $now - $g->image_started_at : null;

        [$phase, $secondsLeft] = match (true) {
            $completed => ['completed', 0.0],
            $g->status !== 'playing' || $diff === null => [$g->status === 'abandoned' ? 'abandoned' : 'lobby', 0.0],
            $both => ['reveal', max(0.0, -$diff)],
            $diff < 0 => ['countdown', -$diff],
            $diff < $recall => ['image', $recall - $diff],
            default => ['question', 0.0],
        };

        $q = $questions[$index] ?? null;
        $showText = in_array($phase, ['question', 'reveal', 'completed'], true);
        $showAnswer = in_array($phase, ['reveal', 'completed'], true);

        $question = $q ? array_filter([
            'index' => $index,
            'image_url' => GameAssets::url($q['image_url'] ?? null),
            'text' => $showText ? ($q['question_text'] ?? null) : null,
            'options' => $showText ? [
                'A' => $q['option_a'] ?? '', 'B' => $q['option_b'] ?? '',
                'C' => $q['option_c'] ?? '', 'D' => $q['option_d'] ?? '',
            ] : null,
            'correct_option' => $showAnswer ? strtoupper($q['correct_option'] ?? 'A') : null,
            'partner_answer' => $showAnswer ? ($theirs[$key] ?? null) : null,
        ], fn ($v) => $v !== null) : null;

        $payload = [
            'phase' => $phase,
            'phase_seconds_left' => round($secondsLeft, 2),
            'image_started_at' => $g->image_started_at,
            'timing' => [
                'countdown' => (float) config('game.timing.memory_snap_countdown', 3.5),
                'recall' => $recall,
                'reveal' => (float) config('game.timing.memory_snap_reveal', 2.5),
            ],
            'current_index' => $index,
            'total' => count($questions),
            'question' => $question,
            'my_answer' => $mine[$key] ?? null,
            'partner_answered' => isset($theirs[$key]),
            'scores' => [
                'me' => (int) ($slot === 1 ? $g->user_one_score : $g->user_two_score),
                'partner' => (int) ($slot === 1 ? $g->user_two_score : $g->user_one_score),
            ],
            'prefetch_images' => collect($questions)->pluck('image_url')->map(fn ($u) => GameAssets::url($u))->filter()->values()->all(),
            'celebration' => $g->celebration_event,
            'combined_pct' => null,
            'review' => null,
        ];

        if ($completed) {
            $total = max(1, count($questions));
            $payload['combined_pct'] = (int) round((round($g->user_one_score / $total * 100) + round($g->user_two_score / $total * 100)) / 2);
            $payload['review'] = collect($questions)->map(fn ($qq, $i) => [
                'index' => $i + 1,
                'image_url' => GameAssets::url($qq['image_url'] ?? null),
                'text' => $qq['question_text'] ?? '',
                'correct_option' => strtoupper($qq['correct_option'] ?? 'A'),
                'my_answer' => $mine[(string) $i] ?? null,
                'partner_answer' => $theirs[(string) $i] ?? null,
            ])->all();
        }

        return $payload;
    }

    /**
     * @param  callable(MemorySnapGame): void  $mutate
     */
    protected function locked(Model $room, callable $mutate): MemorySnapGame
    {
        return DB::transaction(function () use ($room, $mutate) {
            $locked = MemorySnapGame::whereKey($room->id)->lockForUpdate()->firstOrFail();
            $mutate($locked);

            return $locked->refresh();
        });
    }
}
