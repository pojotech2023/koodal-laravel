<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateFlipGameRequest;
use App\Http\Requests\FlipTileRequest;
use App\Http\Requests\JoinFlipGameRequest;
use App\Models\FlipGame;
use App\Models\FlipTileImage;
use App\Models\User;
use App\Services\FlipGameService;
use App\Services\Games\GameAccess;
use App\Services\Games\GameChatPolicy;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class FlipGameController extends Controller
{
    protected FlipGameService $gameService;

    public function __construct(FlipGameService $gameService)
    {
        $this->gameService = $gameService;
    }

    protected function getPlayerToken(Request $request): string
    {
        $user = auth()->guard('web')->user();
        if ($user) {
            $token = 'user_' . $user->id;
        } else {
            $token = session('flip_player_token');
            if (!$token) {
                $token = $request->cookie('flip_player_token');
            }
            if (!$token) {
                $token = Str::random(32);
            }
        }
        session(['flip_player_token' => $token]);
        cookie()->queue('flip_player_token', $token, 60 * 24 * 30);

        return $token;
    }

    /**
     * Create Game & Redirect directly to game room page.
     */
    public function index(Request $request)
    {
        $token = $this->getPlayerToken($request);
        $activeCount = FlipTileImage::where('is_active', true)->count();
        if ($activeCount < 8) {
            return view('flip.landing', [
                'activeCount' => $activeCount,
                'isAvailable' => false,
            ]);
        }

        $user = auth()->guard('web')->user();
        $nickname = $user ? $user->name : 'Player 1';

        try {
            $game = $this->gameService->createGame($nickname, $token);
            return redirect()->route('flip.show', ['code' => $game->code]);
        } catch (\RuntimeException $e) {
            return view('flip.landing', [
                'activeCount' => $activeCount,
                'isAvailable' => false,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Create Game POST.
     */
    public function create(CreateFlipGameRequest $request)
    {
        $token = $this->getPlayerToken($request);

        try {
            $game = $this->gameService->createGame($request->validated('nickname'), $token);
            return redirect()->route('flip.show', ['code' => $game->code]);
        } catch (\RuntimeException $e) {
            return redirect()->route('flip.index')->with('error', $e->getMessage());
        }
    }

    /**
     * Join page: Nickname input.
     */
    public function showJoin(Request $request, string $code)
    {
        $game = FlipGame::where('code', $code)->firstOrFail();
        $token = $this->getPlayerToken($request);

        // If player is already P1 or P2, redirect directly to game
        $pNum = $game->playerNumber($token);
        if ($pNum !== null) {
            return redirect()->route('flip.show', ['code' => $code]);
        }

        if ($game->status === 'finished' || $game->status === 'abandoned') {
            return redirect()->route('flip.index')->with('error', 'This game has already ended.');
        }

        return view('flip.join', [
            'game' => $game,
        ]);
    }

    /**
     * Join Game POST.
     */
    public function join(JoinFlipGameRequest $request, string $code)
    {
        $game = FlipGame::where('code', $code)->firstOrFail();
        $token = $this->getPlayerToken($request);

        $game = $this->gameService->join($game, $request->validated('nickname'), $token);

        return redirect()->route('flip.show', ['code' => $code]);
    }

    /**
     * Game room page.
     */
    public function show(Request $request, string $code)
    {
        $game = FlipGame::where('code', $code)->firstOrFail();
        $token = $this->getPlayerToken($request);

        $user = auth()->guard('web')->user();

        // Auto-join as Player 2 only into a free seat meant for this visitor
        // (FlipGameService::join enforces the same under the row lock).
        if ($game->isWaiting() && $game->playerNumber($token) === null) {
            $host = $game->user_one_id ? User::find($game->user_one_id) : null;
            if ($user && $host && app(GameAccess::class)->blocked($user, $host)) {
                return redirect()->route('dashboard')->with('error', 'This game is not open to you.');
            }

            $game = $this->gameService->join($game, $user?->name, $token, $user?->id);
        }

        $you = $game->playerNumber($token);

        if ($you === null && $game->isWaiting()) {
            return redirect()->route($user ? 'dashboard' : 'flip.index')
                ->with('error', 'This game already has two players.');
        }

        // The other seat's member, from the game itself. (This used to be
        // guessed from the latest invite or the session and written back into
        // the game — seating a member who never joined — and fell back to
        // user #1 for the page's links.)
        $partnerId = null;
        if ($user && $you !== null) {
            $partnerId = $you === 1 ? $game->user_two_id : $game->user_one_id;
            $otherToken = $you === 1 ? $game->p2_token : $game->p1_token;
            if (! $partnerId && str_starts_with((string) $otherToken, 'user_')) {
                $partnerId = (int) substr($otherToken, 5);
            }
        }

        return view('flip.game', [
            'game' => $game,
            'you' => $you,
            'playerToken' => $token,
            'partnerId' => $partnerId && $partnerId !== $user?->id ? $partnerId : null,
        ]);
    }

    /**
     * Flip tile POST endpoint.
     */
    public function flip(FlipTileRequest $request, string $code): JsonResponse
    {
        $game = FlipGame::where('code', $code)->firstOrFail();
        $token = $this->getPlayerToken($request);
        $tileIndex = (int) $request->validated('tile');

        $result = $this->gameService->flip($game, $tileIndex, $token);

        return response()->json($result);
    }

    /**
     * Preview POST endpoint.
     */
    public function preview(Request $request, string $code): JsonResponse
    {
        $game = FlipGame::where('code', $code)->firstOrFail();
        $token = $this->getPlayerToken($request);

        $this->gameService->usePreview($game, $token);

        return response()->json(['success' => true]);
    }

    /**
     * Ready POST endpoint.
     */
    public function ready(Request $request, string $code): JsonResponse
    {
        $game = FlipGame::where('code', $code)->firstOrFail();
        $token = $this->getPlayerToken($request);

        $this->gameService->toggleReady($game, $token);

        return response()->json(['success' => true]);
    }

    /**
     * Send chat message POST endpoint.
     */
    public function chat(Request $request, string $code): JsonResponse
    {
        $request->validate([
            'message' => 'required|string|max:250',
        ]);

        $text = trim($request->input('message'));
        $user = auth()->guard('web')->user();
        $policy = app(GameChatPolicy::class);

        // The same rule as the other game chats: a whole-message chip match
        // for free members and guests (the old `str_contains` check let
        // "wow, call me on 98…" through), blocked words for paid members.
        if ($user) {
            $text = $policy->authorize($user, $text);
        } elseif (! $policy->isChip($text)) {
            return response()->json([
                'success' => false,
                'message' => 'Free plan members can only send predefined messages (Super, Good, Wow, Excellent). Please upgrade your plan for custom chat!'
            ], 403);
        }

        $game = FlipGame::where('code', $code)->firstOrFail();
        $token = $this->getPlayerToken($request);

        $msg = $this->gameService->sendMessage($game, $token, $text);

        return response()->json(['success' => true, 'message' => $msg]);
    }

    /**
     * State polling GET endpoint.
     */
    public function state(Request $request, string $code): JsonResponse
    {
        $game = FlipGame::where('code', $code)->firstOrFail();
        $token = $this->getPlayerToken($request);

        $state = $this->gameService->state($game, $token);

        return response()->json($state);
    }
}
