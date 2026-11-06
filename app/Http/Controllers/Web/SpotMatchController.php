<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\ClaimSpotRequest;
use App\Http\Requests\CreateMatchRequest;
use App\Http\Resources\MatchResource;
use App\Models\GameMatch;
use App\Models\MatchPlayer;
use App\Models\Puzzle;
use App\Services\InvitationService;
use App\Services\MatchService;
use Illuminate\Http\Request;

class SpotMatchController extends Controller
{
    public function __construct(
        protected MatchService $matchService,
        protected InvitationService $invitationService
    ) {}

    public function index()
    {
        $puzzles = Puzzle::where('is_active', true)->withCount('differences')->get();
        $myMatches = GameMatch::whereHas('players', fn($q) => $q->where('user_id', auth()->id()))
            ->with(['puzzle', 'players.user', 'winner'])
            ->latest()
            ->take(10)
            ->get();

        return view('game.index', [
            'puzzles' => $puzzles,
            'myMatches' => $myMatches,
        ]);
    }

    public function create()
    {
        $puzzles = Puzzle::where('is_active', true)->get();

        return view('game.create', [
            'puzzles' => $puzzles,
        ]);
    }

    public function store(CreateMatchRequest $request)
    {
        $user = $request->user();
        $puzzle = Puzzle::where('is_active', true)->findOrFail($request->input('puzzle_id'));

        $match = $this->invitationService->createMatchAndInvite(
            $user,
            $puzzle,
            $request->input('invited_email')
        );

        return redirect()->route('spot.matches.show', $match->id)
            ->with('success', 'Match created! Invitation email sent to ' . $request->input('invited_email'));
    }

    public function show(int $id)
    {
        $match = GameMatch::with(['puzzle.differences', 'players.user', 'claims', 'winner'])->findOrFail($id);
        $this->authorize('view', $match);

        if ($match->status === 'finished') {
            return view('game.results', [
                'match' => $match,
                'players' => $match->players,
                'winner' => $match->winner,
            ]);
        }

        if ($match->status === 'playing') {
            return view('game.play', [
                'match' => $match,
                'driver' => config('game.driver', 'polling'),
            ]);
        }

        return view('game.lobby', [
            'match' => $match,
            'driver' => config('game.driver', 'polling'),
        ]);
    }

    public function state(int $id)
    {
        $match = GameMatch::with(['puzzle.differences', 'players.user', 'claims'])->findOrFail($id);
        $this->authorize('view', $match);

        if ($match->status === 'playing' && $match->ends_at && now()->gte($match->ends_at)) {
            $match = $this->matchService->finishMatch($match);
        }

        return new MatchResource($match);
    }

    public function ready(int $id)
    {
        $match = GameMatch::findOrFail($id);
        $this->authorize('view', $match);

        $updatedMatch = $this->matchService->toggleReady($match, auth()->user());

        if (request()->wantsJson()) {
            return new MatchResource($updatedMatch);
        }

        return redirect()->route('spot.matches.show', $match->id);
    }

    public function claim(ClaimSpotRequest $request, int $id)
    {
        $match = GameMatch::findOrFail($id);
        $this->authorize('claim', $match);

        $result = $this->matchService->claimSpot(
            $match,
            $request->user(),
            (float)$request->input('x_pct'),
            (float)$request->input('y_pct')
        );

        return response()->json($result);
    }

    public function cancel(int $id)
    {
        $match = GameMatch::findOrFail($id);
        $this->authorize('cancel', $match);

        $match->update(['status' => 'cancelled']);

        return redirect()->route('spot.matches.index')
            ->with('success', 'Match cancelled.');
    }

    public function rematch(int $id)
    {
        $oldMatch = GameMatch::with('players')->findOrFail($id);
        $this->authorize('view', $oldMatch);

        $me = auth()->user();
        $opponentPlayer = $oldMatch->players->firstWhere('user_id', '!=', $me->id);

        if (!$opponentPlayer || !$opponentPlayer->user) {
            return redirect()->route('spot.matches.index')->with('error', 'Opponent not available for rematch.');
        }

        $newMatch = $this->invitationService->createMatchAndInvite(
            $me,
            $oldMatch->puzzle,
            $opponentPlayer->user->email
        );

        return redirect()->route('spot.matches.show', $newMatch->id)
            ->with('success', 'Rematch created! Invitation sent to ' . $opponentPlayer->user->name);
    }

    public function leaderboard()
    {
        // GameMatch lives in `spot_matches`; `matches` is the profile-match table.
        $leaderboard = MatchPlayer::select('match_players.user_id')
            ->selectRaw('SUM(CASE WHEN spot_matches.winner_id = match_players.user_id THEN 1 ELSE 0 END) as wins')
            ->selectRaw('ROUND(AVG(match_players.score)) as avg_score')
            ->selectRaw('COUNT(*) as matches_played')
            ->join('spot_matches', 'spot_matches.id', '=', 'match_players.match_id')
            ->where('spot_matches.status', 'finished')
            ->groupBy('match_players.user_id')
            ->orderByDesc('wins')
            ->orderByDesc('avg_score')
            ->with('user')
            ->take(20)
            ->get();

        return view('game.leaderboard', [
            'leaderboard' => $leaderboard,
        ]);
    }
}
