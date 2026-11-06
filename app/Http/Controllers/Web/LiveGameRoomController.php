<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\GameChatMessage;
use App\Models\GameRoom;
use App\Models\KoodalSyncQuestion;
use App\Models\User;
use App\Services\Games\GameAccess;
use App\Services\Games\Rooms\FindItRoom;
use App\Services\Games\Rooms\GameRoomDriver;
use App\Services\Games\Rooms\SyncRoom;
use App\Services\UserPresenceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * The website's live Sync and Find It rooms. Every endpoint works only for
 * the two people in the room, and every move goes through the same driver the
 * app uses (row lock, current-question and clock rules, server-side scoring);
 * the JSON keeps the shapes the pages' scripts already read.
 */
class LiveGameRoomController extends Controller
{
    public function __construct(
        protected UserPresenceService $presenceService,
        protected SyncRoom $sync,
        protected FindItRoom $findIt,
        protected GameAccess $access,
    ) {}

    public function show(Request $request, string $roomCode)
    {
        $user = $request->user();

        $room = GameRoom::with(['userA.profile.primaryPhoto', 'userB.profile.primaryPhoto'])
            ->where('room_code', $roomCode)
            ->firstOrFail();

        if ($user->id !== $room->user_a_id && $user->id !== $room->user_b_id) {
            return redirect()->route('matches.recommended')->with('error', 'You are not a participant in this game room.');
        }

        $partner = ($user->id === $room->user_a_id) ? $room->userB : $room->userA;

        if (! $partner || $partner->status !== 'active' || $this->access->blocked($user, $partner)) {
            return redirect()->route('matches.recommended')->with('error', 'You can no longer play with this member.');
        }

        $partnerPresence = $this->presenceService->formatStatus($partner);
        $driver = $this->driver($room);
        $room = $driver->settled($driver->ensureContent($room));

        if ($room->game_type === 'find_it') {
            return view('games.find_it', [
                'user' => $user,
                'partner' => $partner,
                'partnerPresence' => $partnerPresence,
                'partnerPhotoUrl' => $this->access->card($user, $partner)['avatar'],
                'room' => $room,
                'challenge' => $this->findIt->challengeFor($room),
                // 0 = untimed: the page then shows no clock.
                'timeLimit' => $this->findIt->timeLimit() ?? 0,
            ]);
        }

        // In room order, exactly as the driver numbers them, so an answer's
        // question id always matches the question at the current index.
        $ids = $room->question_ids ?? [];
        $questions = KoodalSyncQuestion::whereIn('id', $ids)->get()->keyBy('id');
        $orderedQuestions = collect($ids)->map(fn ($id) => $questions->get($id))->filter()->values();

        return view('games.live_room', [
            'user' => $user,
            'partner' => $partner,
            'partnerPresence' => $partnerPresence,
            'room' => $room,
            'questions' => $orderedQuestions,
        ]);
    }

    /**
     * Poll real-time room state (status, ready states, answers, chat).
     */
    public function pollState(Request $request, string $roomCode): JsonResponse
    {
        $user = $request->user();
        $room = $this->room($roomCode);
        $driver = $this->driver($room);
        $slot = $driver->seat($room, $user);
        $room = $driver->settled($room);

        $isUserA = $slot === 1;
        $partner = User::find($isUserA ? $room->user_b_id : $room->user_a_id);
        $partnerPresence = $partner ? $this->presenceService->formatStatus($partner) : null;

        $index = (int) $room->current_question_index;
        $a = $room->user_a_answers ?? [];
        $b = $room->user_b_answers ?? [];
        $myAnswered = isset(($isUserA ? $a : $b)[$index]);
        $partnerAnswered = isset(($isUserA ? $b : $a)[$index]);

        // Sync: the partner's pick for the open question stays hidden until
        // both have answered — seeing it first would let you copy it.
        if ($room->game_type === 'sync' && ! ($myAnswered && $partnerAnswered)) {
            if ($isUserA) {
                unset($b[$index]);
            } else {
                unset($a[$index]);
            }
        }

        $payload = [
            'status' => $room->status,
            'started_at' => $room->started_at?->toIso8601String(),
            'game_type' => $room->game_type,
            'current_question_index' => $index,
            'my_ready' => (bool) ($isUserA ? $room->user_a_ready : $room->user_b_ready),
            'partner_ready' => (bool) ($isUserA ? $room->user_b_ready : $room->user_a_ready),
            'my_answered' => $myAnswered,
            'partner_answered' => $partnerAnswered,
            'user_a_answers' => $a,
            'user_b_answers' => $b,
            'user_a_score' => $room->user_a_score,
            'user_b_score' => $room->user_b_score,
            'partner_presence' => $partnerPresence,
            'messages' => $this->messages($room),
            'questions_review' => $room->status === 'completed' && $room->game_type === 'sync'
                ? $this->syncReview($room, $isUserA)
                : [],
        ];

        if ($room->game_type === 'find_it') {
            $payload['seconds_left'] = $this->findIt->secondsLeft($room);
            $payload['score'] = $room->status === 'completed' ? (int) $room->user_a_score : null;
        }

        return response()->json($payload);
    }

    /** Toggle the caller's ready flag in the lobby; both ready starts the round. */
    public function toggleReady(Request $request, string $roomCode): JsonResponse
    {
        return $this->act($request, $roomCode, function (GameRoom $room, User $user) {
            $room = $this->driver($room)->ready($room, $user);

            return [
                'success' => true,
                'status' => $room->status,
                'user_a_ready' => (bool) $room->user_a_ready,
                'user_b_ready' => (bool) $room->user_b_ready,
            ];
        });
    }

    /** Sync: answer the current question. */
    public function submitAnswer(Request $request, string $roomCode): JsonResponse
    {
        $data = $request->validate([
            'question_id' => 'required|integer',
            'selected_option' => 'required|string|max:20',
        ]);

        return $this->act($request, $roomCode, function (GameRoom $room, User $user) use ($data) {
            abort_unless($room->game_type === 'sync', 409, 'This room does not take answers.');

            $room = $this->sync->answer($room, $user, (int) $data['question_id'], $data['selected_option']);
            $index = (int) $room->current_question_index;

            return [
                'success' => true,
                'both_answered' => isset(($room->user_a_answers ?? [])[$index], ($room->user_b_answers ?? [])[$index]),
            ];
        });
    }

    /** Sync: advance (or finish) once both have answered; `from_index` makes it idempotent. */
    public function nextQuestion(Request $request, string $roomCode): JsonResponse
    {
        $fromIndex = $request->has('from_index') ? (int) $request->input('from_index') : null;

        return $this->act($request, $roomCode, function (GameRoom $room, User $user) use ($fromIndex) {
            abort_unless($room->game_type === 'sync', 409, 'This room has no questions.');

            $room = $this->sync->next($room, $user, $fromIndex);

            return [
                'success' => true,
                'status' => $room->status,
                'current_question_index' => (int) $room->current_question_index,
            ];
        });
    }

    public function leave(Request $request, string $roomCode): JsonResponse
    {
        $room = GameRoom::where('room_code', $roomCode)->first();

        if (! $room) {
            return response()->json(['success' => true]);
        }

        try {
            $this->driver($room)->leave($room, $request->user());
        } catch (HttpException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], $e->getStatusCode());
        }

        return response()->json(['success' => true]);
    }

    /** Find It: claim a difference by index; the room checks the clock and who got it first. */
    public function claimFindItDifference(Request $request, string $roomCode): JsonResponse
    {
        $data = $request->validate([
            'difference_index' => 'required|integer|min:0',
            'difference_name' => 'nullable|string|max:100',
        ]);

        return $this->act($request, $roomCode, function (GameRoom $room, User $user) use ($data) {
            abort_unless($room->game_type === 'find_it', 409, 'This room has no differences to find.');

            ['room' => $room, 'result' => $result] = $this->findIt->claim($room, $user, (int) $data['difference_index']);

            $partnerName = User::whereKey($room->user_a_id === $user->id ? $room->user_b_id : $room->user_a_id)
                ->value('name') ?? 'Partner';

            return array_filter([
                'success' => in_array($result, ['found', 'already_found'], true),
                'result' => $result,
                'claimed_by_partner' => $result === 'claimed_by_partner' ? true : null,
                'message' => match ($result) {
                    'claimed_by_partner' => $partnerName.' already found this! You find the remaining differences.',
                    'time_up' => "Time's up!",
                    default => null,
                },
                'status' => $room->status,
                'score' => $room->status === 'completed' ? (int) $room->user_a_score : null,
            ], fn ($v) => $v !== null) + [
                'user_a_answers' => $room->user_a_answers ?? [],
                'user_b_answers' => $room->user_b_answers ?? [],
            ];
        });
    }

    /** Find It: start the clock without waiting for the partner's ready tap. */
    public function startGameRoom(Request $request, string $roomCode): JsonResponse
    {
        return $this->act($request, $roomCode, function (GameRoom $room, User $user) {
            abort_unless($room->game_type === 'find_it', 409, 'Both players must press Ready to start.');

            $room = $this->findIt->start($room, $user);

            return [
                'success' => true,
                'status' => $room->status,
                'seconds_left' => $this->findIt->secondsLeft($room),
            ];
        });
    }

    /** A fresh round in the same room (new questions / a different picture). */
    public function resetGameRoom(Request $request, string $roomCode): JsonResponse
    {
        return $this->act($request, $roomCode, function (GameRoom $room, User $user) {
            $room = $this->driver($room)->reset($room, $user);
            $partnerId = $user->id === $room->user_a_id ? $room->user_b_id : $room->user_a_id;

            return [
                'success' => true,
                'redirect' => $room->game_type === 'find_it'
                    ? route('games.find_it', $partnerId)
                    : route('games.room', $room->room_code),
            ];
        });
    }

    /**
     * Run a move as the caller: participants only, and only while the two may
     * still play together. The pages' fetch() calls do not ask for JSON, so a
     * refusal is answered in JSON here instead of as an HTML error page.
     *
     * @param  callable(GameRoom, User): array<string, mixed>  $move
     */
    protected function act(Request $request, string $roomCode, callable $move): JsonResponse
    {
        $user = $request->user();
        $room = $this->room($roomCode);

        try {
            $driver = $this->driver($room);
            $driver->seat($room, $user);

            if ($partner = $driver->partnerOf($room, $user)) {
                $this->access->assertCanPlay($user, $partner);
            } else {
                abort(403, 'You can no longer play with this member.');
            }

            return response()->json($move($room, $user));
        } catch (HttpException $e) {
            $room->refresh();

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'status' => $room->status,
                'current_question_index' => (int) $room->current_question_index,
            ], $e->getStatusCode());
        }
    }

    protected function room(string $roomCode): GameRoom
    {
        return GameRoom::where('room_code', $roomCode)->firstOrFail();
    }

    protected function driver(GameRoom $room): GameRoomDriver
    {
        return match ($room->game_type) {
            'find_it' => $this->findIt,
            'sync' => $this->sync,
            default => abort(404),
        };
    }

    /** The latest 50, oldest first (the old query kept the *first* 50, so chat froze at 50). */
    protected function messages(GameRoom $room): array
    {
        return GameChatMessage::with('sender:id,name')
            ->where('room_id', $room->id)
            ->orderByDesc('id')
            ->take(50)
            ->get()
            ->reverse()
            ->map(fn (GameChatMessage $msg) => [
                'id' => $msg->id,
                'sender_id' => $msg->sender_id,
                'sender_name' => $msg->sender->name ?? 'User',
                'message' => $msg->message,
                'type' => $msg->type,
                'created_at' => $msg->created_at->format('g:i A'),
            ])
            ->values()
            ->all();
    }

    /** @return array<int, array<string, mixed>> */
    protected function syncReview(GameRoom $room, bool $isUserA): array
    {
        if (empty($room->question_ids)) {
            return [];
        }

        $qModels = KoodalSyncQuestion::whereIn('id', $room->question_ids)->get()->keyBy('id');
        $uA = $room->user_a_answers ?? [];
        $uB = $room->user_b_answers ?? [];
        $review = [];

        foreach ($room->question_ids as $idx => $qId) {
            $qModel = $qModels->get($qId);
            $ansA = $uA[$idx]['option'] ?? null;
            $ansB = $uB[$idx]['option'] ?? null;

            $labelA = '';
            $labelB = '';
            foreach ($qModel?->options ?? [] as $opt) {
                if (($opt['key'] ?? '') === $ansA) {
                    $labelA = $opt['label_en'] ?? $opt['key'];
                }
                if (($opt['key'] ?? '') === $ansB) {
                    $labelB = $opt['label_en'] ?? $opt['key'];
                }
            }

            $isMatch = $ansA !== null && $ansA === $ansB;
            $review[] = [
                'index' => $idx + 1,
                'question_en' => $qModel ? $qModel->question_text_en : 'Question #'.($idx + 1),
                'question_ta' => $qModel?->question_text_ta,
                'user_a_label' => $labelA ?: ($ansA ?: 'Not answered'),
                'user_b_label' => $labelB ?: ($ansB ?: 'Not answered'),
                'my_label' => $isUserA ? ($labelA ?: $ansA) : ($labelB ?: $ansB),
                'partner_label' => $isUserA ? ($labelB ?: $ansB) : ($labelA ?: $ansA),
                'is_match' => $isMatch,
                'points' => $isMatch ? SyncRoom::POINTS : 0,
            ];
        }

        return $review;
    }
}
