<?php

namespace App\Services\Games\Rooms;

use App\Models\GameRoom;
use App\Models\KoodalPairHistory;
use App\Models\KoodalSyncAnswer;
use App\Models\KoodalSyncQuestion;
use App\Models\KoodalSyncSession;
use App\Models\User;
use App\Services\KoodalGameService;
use Illuminate\Support\Facades\DB;

/**
 * Koodal Sync, live: ten shared questions, both answer, then either advances.
 *
 * Rules carried over from LiveGameRoomController: a match is the same option
 * key on the same question and is worth 10 points; completion writes a
 * KoodalSyncSession + answers (so the Vibe story can quote them) and records
 * one `sync` game score. Tightened: an answer must be for the *current*
 * question with a real option key, and the round only advances once both have
 * answered.
 */
class SyncRoom extends GameRoomDriver
{
    public const QUESTION_COUNT = 10;
    public const POINTS = 10;

    public function __construct(protected KoodalGameService $games) {}

    public function type(): string
    {
        return 'sync';
    }

    protected function freshContent(User $a, User $b, ?GameRoom $previous = null): array
    {
        $questions = $this->games->getSyncQuestionsForPair($a->id, $b->id, self::QUESTION_COUNT);

        return ['question_ids' => array_map(fn ($q) => (int) $q['id'], $questions)];
    }

    /**
     * Besides dealing a bare room, re-deal one still in its lobby, unanswered,
     * whose set is short of ten or lacks a food or a location question (rooms
     * dealt before that rule). The new set replaces it only when it is better,
     * so a bank without such questions doesn't re-deal on every page load and
     * leave the two players looking at different questions.
     */
    public function ensureContent(GameRoom $room): GameRoom
    {
        $room = parent::ensureContent($room);

        if (! $this->needsRedeal($room)) {
            return $room;
        }

        return $this->locked($room, function (GameRoom $r) {
            if (! $this->needsRedeal($r)) {
                return;
            }

            $old = $r->question_ids ?? [];
            $fresh = $this->freshContent(User::findOrFail($r->user_a_id), User::findOrFail($r->user_b_id), $r);

            if ($this->dealRank($fresh['question_ids']) > $this->dealRank($old)) {
                $r->fill($fresh);
                $r->save();
            } else {
                // The dealer writes its pick into the pair's lobby room itself;
                // put the old set back, unchanged as far as the players know.
                GameRoom::whereKey($r->id)->update(['question_ids' => json_encode(array_values($old))]);
            }
        });
    }

    protected function needsRedeal(GameRoom $room): bool
    {
        return $room->status === 'lobby'
            && empty($room->user_a_answers) && empty($room->user_b_answers)
            && $this->dealRank($room->question_ids ?? []) < $this->dealRank(null);
    }

    /** Has food, has location, then size — compared as a tuple. `null` is the ideal deal. */
    protected function dealRank(?array $ids): array
    {
        if ($ids === null) {
            return [1, 1, self::QUESTION_COUNT];
        }

        $questions = KoodalSyncQuestion::where('is_active', true)->whereIn('id', $ids)->get();

        return [
            (int) $questions->contains(fn ($q) => $this->games->isFoodQuestion($q)),
            (int) $questions->contains(fn ($q) => $this->games->isLocationQuestion($q)),
            min(count($ids), self::QUESTION_COUNT),
        ];
    }

    public function answer(GameRoom $room, User $viewer, int $questionId, string $option): GameRoom
    {
        return $this->locked($room, function (GameRoom $r) use ($viewer, $questionId, $option) {
            $slot = $this->slot($r, $viewer);

            abort_unless($r->status === 'playing', 409, 'The game is not in progress.');

            $index = (int) $r->current_question_index;
            $expected = $r->question_ids[$index] ?? null;
            abort_unless($expected === $questionId, 409, 'That is not the current question.');

            $question = KoodalSyncQuestion::find($questionId);
            $keys = collect($question?->options ?? [])->pluck('key')->all();
            abort_unless(in_array($option, $keys, true), 422, 'Choose one of the listed options.');

            $mine = $slot === 1 ? ($r->user_a_answers ?? []) : ($r->user_b_answers ?? []);
            $theirs = $slot === 1 ? ($r->user_b_answers ?? []) : ($r->user_a_answers ?? []);

            // Once both have answered the question is closed; letting a late
            // change through would let someone edit after seeing the match.
            abort_if(isset($mine[$index]) && isset($theirs[$index]), 409, 'This question is already closed.');

            $mine[$index] = ['question_id' => $questionId, 'option' => $option];
            if ($slot === 1) {
                $r->user_a_answers = $mine;
            } else {
                $r->user_b_answers = $mine;
            }
            $r->save();

            if (isset($theirs[$index])) {
                $this->system($r, $viewer, 'Both answered Question #'.($index + 1).' ✅');
            }
        });
    }

    /**
     * Advance (or finish). `$fromIndex` makes a double tap from both phones
     * idempotent: only the first call for an index moves the room.
     */
    public function next(GameRoom $room, User $viewer, ?int $fromIndex): GameRoom
    {
        return $this->locked($room, function (GameRoom $r) use ($viewer, $fromIndex) {
            $this->slot($r, $viewer);

            if ($r->status !== 'playing') {
                return;
            }

            $index = (int) $r->current_question_index;

            if ($fromIndex !== null && $fromIndex !== $index) {
                return;
            }

            $a = $r->user_a_answers ?? [];
            $b = $r->user_b_answers ?? [];
            abort_unless(isset($a[$index], $b[$index]), 409, 'Both players must answer first.');

            $total = count($r->question_ids ?? []);

            if ($index < $total - 1) {
                $r->current_question_index = $index + 1;
                $r->save();

                return;
            }

            $this->complete($r, $viewer);
        });
    }

    protected function complete(GameRoom $r, User $actor): void
    {
        $a = $r->user_a_answers ?? [];
        $b = $r->user_b_answers ?? [];
        $total = count($r->question_ids ?? []);

        $matched = 0;
        foreach (array_keys($a) as $i) {
            if (isset($b[$i]) && $b[$i]['option'] === $a[$i]['option']) {
                $matched++;
            }
        }

        $score = $matched * self::POINTS;

        $session = KoodalSyncSession::create([
            'user_one_id' => min($r->user_a_id, $r->user_b_id),
            'user_two_id' => max($r->user_a_id, $r->user_b_id),
            'status' => 'completed',
            'user_one_score' => $score,
            'user_two_score' => $score,
            'matched_count' => $matched,
        ]);

        foreach ([[$r->user_a_id, $a], [$r->user_b_id, $b]] as [$userId, $answers]) {
            foreach ($answers as $entry) {
                KoodalSyncAnswer::create([
                    'session_id' => $session->id,
                    'question_id' => (int) $entry['question_id'],
                    'user_id' => $userId,
                    'selected_option' => $entry['option'],
                ]);
                KoodalPairHistory::firstOrCreate([
                    'user_one_id' => min($r->user_a_id, $r->user_b_id),
                    'user_two_id' => max($r->user_a_id, $r->user_b_id),
                    'question_id' => (int) $entry['question_id'],
                ]);
            }
        }

        $r->status = 'completed';
        $r->ended_at = now();
        $r->user_a_score = $score;
        $r->user_b_score = $score;
        $r->save();

        $partnerId = $actor->id === $r->user_a_id ? $r->user_b_id : $r->user_a_id;
        $this->games->recordGameScore($actor->id, $partnerId, 'sync', $score, $total * self::POINTS, [
            'matched_count' => $matched,
            'total_questions' => $total,
            'live_room_code' => $r->room_code,
        ]);
    }

    protected function game(GameRoom $room, User $viewer, int $slot): array
    {
        $ids = $room->question_ids ?? [];
        $models = KoodalSyncQuestion::whereIn('id', $ids)->get()->keyBy('id');

        $questions = collect($ids)->map(fn ($id) => $models->get($id))->filter()->values();

        $a = $room->user_a_answers ?? [];
        $b = $room->user_b_answers ?? [];
        $mine = $slot === 1 ? $a : $b;
        $theirs = $slot === 1 ? $b : $a;
        $index = (int) $room->current_question_index;
        $completed = $room->status === 'completed';

        $reveals = [];
        foreach ($questions as $i => $q) {
            if (isset($a[$i], $b[$i])) {
                $reveals[] = ['index' => $i, 'is_match' => $a[$i]['option'] === $b[$i]['option']];
            }
        }

        $payload = [
            'current_index' => $index,
            'total' => $questions->count(),
            'questions' => $questions->map(fn ($q) => $this->presentQuestion($q))->all(),
            'my_answers' => collect($mine)
                ->map(fn ($e, $i) => ['index' => (int) $i, 'option' => $e['option']])
                ->values()
                ->all(),
            'my_answered' => isset($mine[$index]),
            'partner_answered' => isset($theirs[$index]),
            'both_answered' => isset($mine[$index], $theirs[$index]),
            'answered' => ['me' => count($mine), 'partner' => count($theirs)],
            'reveals' => $reveals,
            'matched' => count(array_filter($reveals, fn ($r) => $r['is_match'])),
            'max_score' => $questions->count() * self::POINTS,
            'score' => $completed ? (int) $room->user_a_score : null,
            'review' => null,
        ];

        if ($completed) {
            $payload['review'] = $questions->map(function ($q, $i) use ($mine, $theirs) {
                $my = $mine[$i]['option'] ?? null;
                $their = $theirs[$i]['option'] ?? null;

                return [
                    'index' => $i + 1,
                    'question_id' => $q->id,
                    'question_en' => $q->question_text_en,
                    'question_ta' => $q->question_text_ta,
                    'my_option' => $my,
                    'partner_option' => $their,
                    'my_label' => $this->label($q, $my),
                    'partner_label' => $this->label($q, $their),
                    'is_match' => $my !== null && $my === $their,
                    'points' => $my !== null && $my === $their ? self::POINTS : 0,
                ];
            })->all();
        }

        return $payload;
    }

    /** Shared with the offline flow so both present a question identically. */
    public function presentQuestion(KoodalSyncQuestion $q): array
    {
        return [
            'id' => $q->id,
            'category' => $q->category,
            'question_en' => $q->question_text_en,
            'question_ta' => $q->question_text_ta,
            'options' => collect($q->options ?? [])->map(fn ($o) => [
                'key' => $o['key'] ?? null,
                'label_en' => $o['label_en'] ?? ($o['key'] ?? ''),
                'label_ta' => $o['label_ta'] ?? null,
            ])->values()->all(),
        ];
    }

    protected function label(KoodalSyncQuestion $q, ?string $key): ?string
    {
        if ($key === null) {
            return null;
        }

        foreach ($q->options ?? [] as $o) {
            if (($o['key'] ?? null) === $key) {
                return $o['label_en'] ?? $key;
            }
        }

        return $key;
    }
}
