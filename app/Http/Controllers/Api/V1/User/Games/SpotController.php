<?php

namespace App\Http\Controllers\Api\V1\User\Games;

use App\Http\Controllers\Controller;
use App\Http\Resources\MatchResource;
use App\Models\GameMatch;
use App\Models\MatchPlayer;
use App\Models\Puzzle;
use App\Models\User;
use App\Services\Games\GameAccess;
use App\Services\Games\GameAssets;
use App\Services\Games\SpotMatches;
use App\Services\MatchService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Spot the Difference 1v1 — two members race to tap the differences between
 * two pictures. MatchService owns the rules (server-side hit test, scoring,
 * cooldown, winner); this is the member-to-member front door.
 */
class SpotController extends Controller
{
    public function __construct(
        protected MatchService $matches,
        protected SpotMatches $spot,
        protected GameAccess $access,
    ) {}

    public function puzzles(): JsonResponse
    {
        $puzzles = Puzzle::where('is_active', true)->withCount('differences')->get()->map(fn (Puzzle $p) => [
            'id' => $p->id,
            'title' => $p->title,
            'difficulty' => $p->difficulty,
            'time_limit_seconds' => $p->time_limit_seconds,
            'total_differences' => $p->differences_count,
            'thumbnail_url' => GameAssets::url($p->original_image),
        ]);

        return ApiResponse::success($puzzles, 'Puzzles retrieved successfully');
    }

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $rows = GameMatch::query()
            ->whereHas('players', fn ($q) => $q->where('user_id', $user->id))
            ->with(['puzzle', 'players.user.profile.primaryPhoto'])
            ->latest('id')
            ->limit(30)
            ->get()
            ->map(function (GameMatch $m) use ($user) {
                $me = $m->players->firstWhere('user_id', $user->id);
                $them = $m->players->firstWhere('user_id', '!=', $user->id);

                return [
                    'id' => $m->id,
                    'status' => $m->status,
                    'puzzle' => ['id' => $m->puzzle->id, 'title' => $m->puzzle->title,
                        'thumbnail_url' => GameAssets::url($m->puzzle->original_image)],
                    'opponent' => $them?->user ? $this->access->card($user, $them->user) : null,
                    'my_status' => $me?->status,
                    'my_score' => $me?->score,
                    'opponent_score' => $them?->score,
                    'result' => $m->status === 'finished'
                        ? ($m->winner_id === null ? 'draw' : ($m->winner_id === $user->id ? 'won' : 'lost'))
                        : null,
                    'is_host' => $m->host_id === $user->id,
                    'created_at' => $m->created_at?->toIso8601String(),
                ];
            });

        return ApiResponse::success($rows, 'Matches retrieved successfully');
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'puzzle_id' => ['required', 'integer'],
            'opponent_profile_code' => ['required', 'string', 'max:32'],
        ]);

        $user = $request->user();
        $puzzle = Puzzle::where('is_active', true)->findOrFail($data['puzzle_id']);
        $opponent = $this->access->partner($user, $data['opponent_profile_code']);

        $match = $this->spot->create($user, $puzzle, $opponent);

        return ApiResponse::created($this->resource($match, $request), 'Challenge sent');
    }

    public function show(Request $request, GameMatch $match): JsonResponse
    {
        $this->authorize('view', $match);

        // Time is the other way a round ends, and nothing else is polling.
        if ($match->status === 'playing' && $match->ends_at && now()->gte($match->ends_at)) {
            $match = $this->matches->finishMatch($match);
        }

        return ApiResponse::success($this->resource($match, $request), 'Match retrieved successfully');
    }

    public function ready(Request $request, GameMatch $match): JsonResponse
    {
        $this->authorize('view', $match);
        $this->assertOpponentPlayable($match, $request->user());

        return ApiResponse::success(
            $this->resource($this->matches->toggleReady($match, $request->user()), $request),
            'Ready status updated',
        );
    }

    public function claim(Request $request, GameMatch $match): JsonResponse
    {
        $this->authorize('claim', $match);
        $this->assertOpponentPlayable($match, $request->user());

        $data = $request->validate([
            'x_pct' => ['required', 'numeric', 'between:0,100'],
            'y_pct' => ['required', 'numeric', 'between:0,100'],
        ]);

        $result = $this->matches->claimSpot($match, $request->user(), (float) $data['x_pct'], (float) $data['y_pct']);

        $out = match (true) {
            ($result['success'] ?? false) && ($result['is_correct'] ?? false) => [
                'result' => 'correct',
                'points' => $result['points'],
                'difference' => ['id' => $result['difference']->id, 'label' => $result['difference']->label],
            ],
            ($result['success'] ?? false) => [
                'result' => 'wrong',
                'penalty' => $result['penalty'],
                'cooldown_seconds' => $result['cooldown_seconds'],
            ],
            default => [
                'result' => match ($result['reason'] ?? '') {
                    'cooldown' => 'cooldown',
                    'already_claimed_by_opponent' => 'already_found',
                    'not_started' => 'not_started',
                    default => 'not_playing',
                },
                'cooldown_remaining' => $result['cooldown_remaining'] ?? null,
            ],
        };

        return ApiResponse::success(
            $out + ['match' => $this->resource($match->refresh(), $request)],
            'Tap recorded',
        );
    }

    public function cancel(Request $request, GameMatch $match): JsonResponse
    {
        $this->authorize('cancel', $match);

        $match->update(['status' => 'cancelled']);

        return ApiResponse::success($this->resource($match, $request), 'Match cancelled');
    }

    public function rematch(Request $request, GameMatch $match): JsonResponse
    {
        $this->authorize('view', $match);
        abort_unless($match->status === 'finished', 409, 'The match is not over yet.');

        return ApiResponse::created(
            $this->resource($this->spot->rematch($request->user(), $match), $request),
            'Rematch sent',
        );
    }

    public function leaderboard(Request $request): JsonResponse
    {
        $viewer = $request->user();

        $rows = MatchPlayer::select('match_players.user_id')
            ->selectRaw('SUM(CASE WHEN spot_matches.winner_id = match_players.user_id THEN 1 ELSE 0 END) as wins')
            ->selectRaw('ROUND(AVG(match_players.score)) as avg_score')
            ->selectRaw('COUNT(*) as matches_played')
            ->join('spot_matches', 'spot_matches.id', '=', 'match_players.match_id')
            ->where('spot_matches.status', 'finished')
            ->groupBy('match_players.user_id')
            ->orderByDesc('wins')
            ->orderByDesc('avg_score')
            ->with('user.profile.primaryPhoto')
            ->limit(20)
            ->get()
            ->values()
            ->map(fn ($row, $i) => [
                'rank' => $i + 1,
                'player' => $row->user ? $this->access->card($viewer, $row->user) : null,
                'is_me' => $row->user_id === $viewer->id,
                'wins' => (int) $row->wins,
                'avg_score' => (int) $row->avg_score,
                'matches_played' => (int) $row->matches_played,
            ]);

        return ApiResponse::success($rows, 'Leaderboard retrieved successfully');
    }

    /** The live rooms refuse a move once the other player is blocked or suspended; so does a match. */
    protected function assertOpponentPlayable(GameMatch $match, User $user): void
    {
        $opponent = $match->players()->where('user_id', '!=', $user->id)->first()?->user;

        if ($opponent) {
            $this->access->assertCanPlay($user, $opponent);
        }
    }

    /** @return array<string, mixed> */
    protected function resource(GameMatch $match, Request $request): array
    {
        return (new MatchResource($match->fresh()))->resolve($request);
    }
}
