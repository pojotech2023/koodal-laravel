<?php

namespace App\Services\Games;

use App\Models\KoodalSyncAnswer;
use App\Models\KoodalSyncQuestion;
use App\Models\User;
use App\Services\Games\Rooms\SyncRoom;
use App\Services\KoodalGameService;
use Illuminate\Validation\ValidationException;

/**
 * Koodal Sync without a shared room: answer all ten when it suits you, the
 * partner answers later, results unlock when both have.
 *
 * Question choice is KoodalGameService's — it re-serves questions the partner
 * already answered so the two sets can be compared — and submission goes
 * through the same method the website uses, so scores, pair history and the
 * "waiting for you" invite are identical.
 */
class SyncOffline
{
    public function __construct(
        protected KoodalGameService $games,
        protected SyncRoom $presenter,
    ) {}

    /** @return array{questions: array<int, array<string, mixed>>, partner_already_played: bool} */
    public function questions(User $user, User $partner): array
    {
        $ids = array_map(
            fn ($q) => (int) $q['id'],
            $this->games->getSyncQuestionsForPair($user->id, $partner->id, SyncRoom::QUESTION_COUNT),
        );

        $models = KoodalSyncQuestion::whereIn('id', $ids)->get()->keyBy('id');

        $u1 = min($user->id, $partner->id);
        $u2 = max($user->id, $partner->id);

        $partnerPlayed = KoodalSyncAnswer::where('user_id', $partner->id)
            ->whereHas('session', function ($q) use ($u1, $u2) {
                $q->where('user_one_id', $u1)->where('user_two_id', $u2);
            })
            ->exists();

        return [
            'questions' => collect($ids)
                ->map(fn ($id) => $models->get($id))
                ->filter()
                ->map(fn ($q) => $this->presenter->presentQuestion($q))
                ->values()
                ->all(),
            'partner_already_played' => $partnerPlayed,
        ];
    }

    /**
     * @param  array<int|string, string>  $answers  question id => option key
     * @return array<string, mixed>
     */
    public function submit(User $user, User $partner, array $answers): array
    {
        $questions = KoodalSyncQuestion::where('is_active', true)
            ->whereIn('id', array_map('intval', array_keys($answers)))
            ->get()
            ->keyBy('id');

        $clean = [];
        foreach ($answers as $id => $option) {
            $question = $questions->get((int) $id);
            $valid = $question && collect($question->options ?? [])->pluck('key')->contains($option);

            if (! $valid) {
                throw ValidationException::withMessages(['answers' => ["Question {$id} or its option is not valid."]]);
            }

            $clean[(int) $id] = $option;
        }

        return $this->games->submitSyncSession($user->id, $partner->id, $clean);
    }
}
