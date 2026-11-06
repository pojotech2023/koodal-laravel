<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\MemorySnapGame;
use App\Models\User;
use App\Services\Games\GameAccess;
use App\Services\Games\GameChatPolicy;
use App\Services\Games\Rooms\MemorySnapRoom;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * The website's Memory Snap room. Game rules, locking and scoring live in
 * MemorySnapRoom (shared with the app); this controller keeps the row-shaped
 * JSON the page's script reads, with two things taken out: the player's seat
 * comes from the logged-in account (a posted `role` is ignored), and a
 * question's `correct_option` and the partner's pick stay hidden until the
 * reveal.
 */
class MemorySnapGameController extends Controller
{
    public function __construct(
        protected MemorySnapRoom $rooms,
        protected GameAccess $access,
        protected GameChatPolicy $chat,
    ) {}

    /**
     * Start a game with $partnerId, or an open room whose link the host shares.
     */
    public function index(Request $request, $partnerId = null)
    {
        $user = $request->user();

        if ($partnerId !== null) {
            $partner = $this->access->partnerById($user, (int) $partnerId);
            $existing = $this->rooms->findActiveBetween($user, $partner);
            $game = ($existing && $existing->status === 'waiting')
                ? $existing
                : $this->rooms->create($user, $partner);
        } else {
            $game = $this->rooms->open($user);
        }

        return redirect()->route('games.memory_snap.room', ['code' => $game->code]);
    }

    /**
     * Compatibility helper to retrieve five questions.
     */
    public function getFiveGameQuestions(): array
    {
        return app(\App\Services\Games\MemorySnapQuestionBank::class)->five();
    }

    public function showRoom(Request $request, string $code)
    {
        $user = $request->user();
        $game = $this->rooms->find($code);

        if (! $this->isPlayer($game, $user)) {
            $host = User::find($game->user_one_id);

            if ($game->user_two_id !== null || $game->status !== 'waiting'
                || ! $host || $host->status !== 'active' || $this->access->blocked($user, $host)) {
                return redirect()->route('dashboard')->with('error', 'This Memory Snap game is not open to you.');
            }

            try {
                $game = $this->rooms->seatGuest($game, $user);
            } catch (HttpException $e) {
                return redirect()->route('dashboard')->with('error', $e->getMessage());
            }
        }

        $game = $this->rooms->join($game, $user);
        $slot = $this->rooms->seat($game, $user);
        $partnerId = $slot === 1 ? $game->user_two_id : $game->user_one_id;

        return view('games.memory_snap', [
            'game' => $this->webGame($game, $slot),
            'playerRole' => $slot,
            'code' => $game->code,
            'partnerId' => $partnerId,
            'serverTime' => microtime(true),
        ]);
    }

    /** Poll: move the round on if its reveal is over, then the redacted row. */
    public function state(Request $request, string $code): JsonResponse
    {
        $game = $this->rooms->find($code);
        $slot = $this->rooms->seat($game, $request->user());

        return response()->json($this->webGame($this->rooms->advance($game), $slot));
    }

    /** "Join Game" in the lobby. */
    public function playerJoin(Request $request, string $code): JsonResponse
    {
        return $this->act($request, $code, fn (MemorySnapGame $g, User $u) => $this->rooms->join($g, $u));
    }

    /** The old member picker: take the open seat (the posted name is ignored). */
    public function join(Request $request, string $code): JsonResponse
    {
        $user = $request->user();
        $game = $this->rooms->find($code);

        return $this->respond(function () use ($game, $user) {
            if (! $this->isPlayer($game, $user)) {
                $host = User::find($game->user_one_id);
                abort_if(! $host || $this->access->blocked($user, $host), 403, 'This Memory Snap game is not open to you.');
                $game = $this->rooms->seatGuest($game, $user);
            }

            return $this->rooms->join($game, $user);
        }, $user);
    }

    /** Both players must be ready before the game starts. */
    public function toggleReady(Request $request, string $code): JsonResponse
    {
        return $this->act($request, $code, fn (MemorySnapGame $g, User $u) => $this->rooms->ready($g, $u));
    }

    public function submitAnswer(Request $request, string $code): JsonResponse
    {
        $data = $request->validate([
            'question_index' => 'required|integer|min:0',
            'answer' => 'required|string|max:1',
        ]);

        return $this->act($request, $code, fn (MemorySnapGame $g, User $u) => $this->rooms->answer(
            $g, $u, (int) $data['question_index'], trim($data['answer']),
        ));
    }

    public function sendChat(Request $request, string $code): JsonResponse
    {
        $request->validate(['message' => 'required|string|max:200']);

        $user = $request->user();
        $game = $this->rooms->find($code);
        $slot = $this->rooms->seat($game, $user);
        $this->assertPartnerPlayable($game, $user);

        $text = $this->chat->authorize($user, (string) $request->input('message'));
        $this->rooms->sendMessage($game, $user, $text);

        $web = $this->webGame($game->fresh(), $slot);

        return response()->json([
            'success' => true,
            'message' => end($web['chat_messages']) ?: null,
            'game' => $web,
        ]);
    }

    public function resetGame(Request $request, string $code): JsonResponse
    {
        return $this->act($request, $code, fn (MemorySnapGame $g, User $u) => $this->rooms->reset($g, $u));
    }

    /** Where the partner's page should offer to follow (the Flip room just opened). */
    public function setNextGame(Request $request, string $code): JsonResponse
    {
        $data = $request->validate(['url' => 'required|string|max:255']);

        $user = $request->user();
        $game = $this->rooms->find($code);
        $slot = $this->rooms->seat($game, $user);

        $url = preg_replace('#^https?://[^/]+#i', '', trim($data['url']));
        if (! $this->isSafePath($url)) {
            return response()->json(['success' => false, 'message' => 'Only a link on this site can be shared.'], 422);
        }

        $game->forceFill(['next_game_url' => $url])->save();
        $web = $this->webGame($game->fresh(), $slot);

        return response()->json(['success' => true, 'next_game_url' => $web['next_game_url'], 'game' => $web]);
    }

    /**
     * Run a driver mutation as the caller and answer with the page's shape.
     *
     * @param  callable(MemorySnapGame, User): MemorySnapGame  $mutate
     */
    protected function act(Request $request, string $code, callable $mutate): JsonResponse
    {
        $user = $request->user();
        $game = $this->rooms->find($code);

        return $this->respond(function () use ($game, $user, $mutate) {
            $this->rooms->seat($game, $user);
            $this->assertPartnerPlayable($game, $user);

            return $mutate($game, $user);
        }, $user);
    }

    /**
     * The page's fetch() calls do not ask for JSON, so a rule the driver
     * enforces (409 not the current question, 403 not a player) would come
     * back as an HTML error page; answer in JSON instead.
     *
     * @param  callable(): MemorySnapGame  $run
     */
    protected function respond(callable $run, User $user): JsonResponse
    {
        try {
            $game = $run();
        } catch (HttpException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], $e->getStatusCode());
        }

        $web = $this->webGame($game, $this->rooms->seat($game, $user));

        return response()->json(['success' => true, 'game' => $web, 'server_time' => $web['server_time']]);
    }

    protected function assertPartnerPlayable(MemorySnapGame $game, User $user): void
    {
        $partner = $this->rooms->partnerOf($game, $user);

        if ($partner) {
            $this->access->assertCanPlay($user, $partner);
        }
    }

    protected function isPlayer(MemorySnapGame $game, User $user): bool
    {
        return in_array($user->id, [$game->user_one_id, $game->user_two_id], true);
    }

    /**
     * The row as the page expects it, minus what a player must not see yet:
     * a question's answer before its reveal, the partner's pick for the
     * question still being answered, and scores that would tell which way an
     * unrevealed answer went.
     *
     * @return array<string, mixed>
     */
    protected function webGame(MemorySnapGame $game, int $slot): array
    {
        $data = $game->toArray();
        $questions = $game->questions_data ?? [];
        $current = (int) $game->current_question_index;
        $one = $game->user_one_answers ?? [];
        $two = $game->user_two_answers ?? [];
        $completed = $game->status === 'completed';

        $revealed = fn (int $i) => $completed
            || $i < $current
            || ($i === $current && isset($one[(string) $i], $two[(string) $i]));

        $data['questions_data'] = collect($questions)->map(function ($q, $i) use ($revealed) {
            if (! $revealed((int) $i)) {
                unset($q['correct_option']);
            }

            return $q;
        })->values()->all();

        $score = fn (array $answers) => collect($answers)
            ->filter(fn ($option, $i) => $revealed((int) $i)
                && strtoupper((string) $option) === strtoupper($questions[(int) $i]['correct_option'] ?? 'A'))
            ->count();

        $data['user_one_score'] = $score($one);
        $data['user_two_score'] = $score($two);

        // The page only needs to know the partner has answered, not what.
        $theirs = $slot === 1 ? 'user_two_answers' : 'user_one_answers';
        $partnerAnswers = $slot === 1 ? $two : $one;
        if (! $revealed($current) && isset($partnerAnswers[(string) $current])) {
            $partnerAnswers[(string) $current] = '?';
        }
        $data[$theirs] = (object) $partnerAnswers;
        $data[$slot === 1 ? 'user_one_answers' : 'user_two_answers'] = (object) ($slot === 1 ? $one : $two);

        $data['chat_messages'] = collect($game->chat_messages ?? [])->map(fn ($m) => [
            'id' => $m['id'] ?? null,
            'role' => (int) ($m['role'] ?? 0),
            'sender' => $m['sender'] ?? '',
            'text' => $m['text'] ?? '',
            'time' => $this->clock($m['time'] ?? null),
        ])->values()->all();

        $data['next_game_url'] = $this->isSafePath($game->next_game_url) ? $game->next_game_url : null;
        $data['server_time'] = microtime(true);

        return $data;
    }

    /** A same-site path: one leading slash, not `//host` or `/\host`, no scheme. */
    protected function isSafePath(?string $url): bool
    {
        return is_string($url) && preg_match('#^/(?![/\\\\])[^\s\\\\]*$#', $url) === 1;
    }

    /** The driver stores ISO times (the app needs them); the page shows "07:45 PM". */
    protected function clock(?string $time): string
    {
        if ($time === null || $time === '' || ! str_contains($time, 'T')) {
            return (string) $time;
        }

        try {
            return Carbon::parse($time)->setTimezone(config('app.timezone'))->format('h:i A');
        } catch (\Throwable) {
            return $time;
        }
    }
}
