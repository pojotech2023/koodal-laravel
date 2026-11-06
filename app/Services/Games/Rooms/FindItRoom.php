<?php

namespace App\Services\Games\Rooms;

use App\Models\GameRoom;
use App\Models\KoodalFindItChallenge;
use App\Models\User;
use App\Services\Games\GameAssets;
use App\Services\KoodalGameService;

/**
 * Find It: two near-identical pictures, seven differences, both players hunt
 * together and a difference belongs to whoever tapped it first.
 *
 * The round has no clock by default: it ends when every difference is found
 * (or someone leaves). `game.timing.find_it_limit` brings a limit back, and
 * then it is enforced here — the site only ran it in the browser, so a closed
 * tab never ended the round. The score is computed from the stored claims;
 * the site accepted `chemistry_score` straight from the client.
 */
class FindItRoom extends GameRoomDriver
{
    /** Contribution balance → score. A lopsided hunt is a weaker "teamwork" signal. */
    private const BALANCE = [0 => 100, 1 => 100, 2 => 85, 3 => 65, 4 => 45, 5 => 30, 6 => 15, 7 => 5];

    public function __construct(protected KoodalGameService $games) {}

    public function type(): string
    {
        return 'find_it';
    }

    protected function freshContent(User $a, User $b, ?GameRoom $previous = null): array
    {
        $query = KoodalFindItChallenge::where('is_active', true);

        $fresh = (clone $query)->whereNotIn('id', $previous?->question_ids ?? [])->inRandomOrder()->first()
            ?? $query->inRandomOrder()->first();

        return ['question_ids' => $fresh ? [(int) $fresh->id] : []];
    }

    /** Start without waiting for the partner's ready tap (the site's Start button). */
    public function start(GameRoom $room, User $viewer): GameRoom
    {
        return $this->locked($room, function (GameRoom $r) use ($viewer) {
            $this->slot($r, $viewer);

            if ($r->status !== 'lobby') {
                return;
            }

            $r->status = 'playing';
            $r->started_at = now();
            $r->save();
            $this->system($r, $viewer, $viewer->name.' started the game!'.($this->timeLimit() ? ' Timer running ⏰' : ' 🔍'));
        });
    }

    /**
     * @return array{room: GameRoom, result: string}
     *                                                result: found | already_found | claimed_by_partner | time_up
     */
    public function claim(GameRoom $room, User $viewer, int $index): array
    {
        $result = 'found';

        $updated = $this->locked($room, function (GameRoom $r) use ($viewer, $index, &$result) {
            $slot = $this->slot($r, $viewer);

            abort_unless($r->status === 'playing', 409, 'The game is not in progress.');

            if ($this->expired($r)) {
                $this->complete($r, $viewer);
                $result = 'time_up';

                return;
            }

            $challenge = $this->challenge($r);
            abort_unless($index >= 0 && $index < count($challenge['differences']), 422, 'Unknown difference.');

            // As ints: older website rooms stored the indexes as strings,
            // which the strict checks below would never match.
            $mine = array_values(array_map('intval', $slot === 1 ? ($r->user_a_answers ?? []) : ($r->user_b_answers ?? [])));
            $theirs = array_values(array_map('intval', $slot === 1 ? ($r->user_b_answers ?? []) : ($r->user_a_answers ?? [])));

            if (in_array($index, $theirs, true)) {
                $result = 'claimed_by_partner';

                return;
            }

            if (in_array($index, $mine, true)) {
                $result = 'already_found';

                return;
            }

            $mine[] = $index;
            $mine = array_values(array_unique($mine));

            if ($slot === 1) {
                $r->user_a_answers = $mine;
            } else {
                $r->user_b_answers = $mine;
            }
            $r->save();

            $name = $challenge['differences'][$index]['name'] ?? 'a difference';
            $this->system($r, $viewer, $viewer->name.' found "'.$name.'"! 🔍');

            if (count($mine) + count($theirs) >= count($challenge['differences'])) {
                $this->complete($r, $viewer);
            }
        });

        return ['room' => $updated, 'result' => $result];
    }

    /**
     * The website's end-of-round call. It can only close a round the server
     * would close anyway — every difference claimed, or the clock run out —
     * and scores it from the stored claims; a round still running is returned
     * unchanged. Idempotent, so a second call never records a second score.
     */
    public function finish(GameRoom $room, User $viewer): GameRoom
    {
        return $this->locked($room, function (GameRoom $r) use ($viewer) {
            $this->slot($r, $viewer);

            $found = count($r->user_a_answers ?? []) + count($r->user_b_answers ?? []);

            if ($r->status === 'playing'
                && ($this->expired($r) || $found >= count($this->challenge($r)['differences']))) {
                $this->complete($r, $viewer);
            }
        });
    }

    /** The round's clock in seconds, or null when Find It is untimed (the default). */
    public function timeLimit(): ?int
    {
        $limit = (int) config('game.timing.find_it_limit', 0);

        return $limit > 0 ? $limit : null;
    }

    /** Seconds left on a running round's clock (the limit, before it starts); null when untimed. */
    public function secondsLeft(GameRoom $room): ?int
    {
        $limit = $this->timeLimit();
        if ($limit === null) {
            return null;
        }

        return match ($room->status) {
            'playing' => $room->started_at ? max(0, $limit - (int) $room->started_at->diffInSeconds(now())) : $limit,
            'lobby' => $limit,
            default => 0,
        };
    }

    /** @return array{id: int, title: string, image_a: ?string, image_b: ?string, differences: array<int, array<string, mixed>>} */
    public function challengeFor(GameRoom $room): array
    {
        return $this->challenge($room);
    }

    protected function settle(GameRoom $room): GameRoom
    {
        if ($room->status !== 'playing' || ! $this->expired($room)) {
            return $room;
        }

        return $this->locked($room, function (GameRoom $r) {
            if ($r->status === 'playing' && $this->expired($r)) {
                $this->complete($r, User::findOrFail($r->user_a_id));
            }
        });
    }

    protected function expired(GameRoom $r): bool
    {
        if ($this->timeLimit() === null) {
            return false;
        }

        $limit = $this->timeLimit() + (int) config('game.timing.find_it_grace', 3);

        return $r->started_at !== null && $r->started_at->copy()->addSeconds($limit)->isPast();
    }

    protected function complete(GameRoom $r, User $actor): void
    {
        $mine = count($r->user_a_answers ?? []);
        $theirs = count($r->user_b_answers ?? []);
        $total = max(1, count($this->challenge($r)['differences']));

        $balance = self::BALANCE[min(7, abs($mine - $theirs))];
        $completion = min(1.0, ($mine + $theirs) / $total);
        $score = (int) round($balance * $completion);

        $r->status = 'completed';
        $r->ended_at = now();
        $r->user_a_score = $score;
        $r->user_b_score = $score;
        $r->save();

        $details = [
            'found_count' => $mine + $theirs,
            'total' => $total,
            'time_taken' => $r->started_at ? (int) $r->started_at->diffInSeconds($r->ended_at) : null,
            'live_room_code' => $r->room_code,
        ];

        $this->games->recordGameScore($r->user_a_id, $r->user_b_id, 'find_it', $score, 100,
            $details + ['my_count' => $mine, 'partner_count' => $theirs]);
        $this->games->recordGameScore($r->user_b_id, $r->user_a_id, 'find_it', $score, 100,
            $details + ['my_count' => $theirs, 'partner_count' => $mine]);
    }

    /**
     * The picture pair and its seven hotspots; falls back to the bundled image
     * when the admin has not created a challenge yet, as the website does.
     *
     * @return array{id: int, title: string, image_a: ?string, image_b: ?string, differences: array<int, array<string, mixed>>}
     */
    protected function challenge(GameRoom $room): array
    {
        $model = ($id = ($room->question_ids[0] ?? null)) ? KoodalFindItChallenge::find($id) : null;

        if ($model) {
            $diffs = array_slice(array_values($model->differences ?? []), 0, (int) config('game.min_differences', 7));

            return [
                'id' => $model->id,
                'title' => $model->title,
                'image_a' => GameAssets::url($model->image_a),
                'image_b' => GameAssets::url($model->image_b),
                'differences' => $diffs,
            ];
        }

        return [
            'id' => 0,
            'title' => 'Temple Courtyard & Differences',
            'image_a' => GameAssets::url('/image/02_Koodal_Find_It.png'),
            'image_b' => GameAssets::url('/image/02_Koodal_Find_It.png'),
            'differences' => [
                ['id' => 1, 'name' => 'Gold Coin', 'type' => 'general', 'x' => 20, 'y' => 73],
                ['id' => 2, 'name' => 'Flying Bird', 'type' => 'bird', 'x' => 67, 'y' => 25],
                ['id' => 3, 'name' => 'Canopy Color', 'type' => 'general', 'x' => 67, 'y' => 60],
                ['id' => 4, 'name' => 'Gopuram Flag', 'type' => 'general', 'x' => 80, 'y' => 22],
                ['id' => 5, 'name' => 'Flower Mistake', 'type' => 'flower', 'x' => 37, 'y' => 25],
                ['id' => 6, 'name' => 'Yellow Umbrella', 'type' => 'general', 'x' => 35, 'y' => 76],
                ['id' => 7, 'name' => 'Moved Coconut', 'type' => 'general', 'x' => 22, 'y' => 80],
            ],
        ];
    }

    protected function game(GameRoom $room, User $viewer, int $slot): array
    {
        $challenge = $this->challenge($room);
        $mine = $slot === 1 ? ($room->user_a_answers ?? []) : ($room->user_b_answers ?? []);
        $theirs = $slot === 1 ? ($room->user_b_answers ?? []) : ($room->user_a_answers ?? []);

        $limit = $this->timeLimit();
        $elapsed = $room->started_at ? max(0, (int) $room->started_at->diffInSeconds($room->ended_at ?? now())) : 0;
        $completed = $room->status === 'completed';

        $found = array_merge(
            array_map(fn ($i) => ['index' => (int) $i, 'by' => 'me'], $mine),
            array_map(fn ($i) => ['index' => (int) $i, 'by' => 'partner'], $theirs),
        );

        return [
            'challenge' => [
                'id' => $challenge['id'],
                'title' => $challenge['title'],
                'image_a' => $challenge['image_a'],
                'image_b' => $challenge['image_b'],
                'total' => count($challenge['differences']),
            ],
            // The client needs the hotspots to draw the found markers; a claim
            // is still validated server-side by index (and clock, when timed).
            'differences' => collect($challenge['differences'])->map(fn ($d, $i) => [
                'index' => $i,
                'id' => $d['id'] ?? $i + 1,
                'name' => $d['name'] ?? 'Difference '.($i + 1),
                'type' => $d['type'] ?? 'general',
                'x' => $d['x'] ?? null,
                'y' => $d['y'] ?? null,
            ])->values()->all(),
            'found' => $found,
            'my_count' => count($mine),
            'partner_count' => count($theirs),
            'started_at' => $room->started_at?->toIso8601String(),
            // Both null when the round is untimed.
            'time_limit_seconds' => $limit,
            'seconds_left' => $limit === null ? null
                : ($room->status === 'playing' ? max(0, $limit - $elapsed) : ($completed ? 0 : $limit)),
            'time_taken' => $completed ? $elapsed : null,
            'score' => $completed ? (int) $room->user_a_score : null,
            'all_found' => $completed && count($found) >= count($challenge['differences']),
        ];
    }
}
