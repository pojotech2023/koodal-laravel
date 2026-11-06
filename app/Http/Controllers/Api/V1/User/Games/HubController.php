<?php

namespace App\Http\Controllers\Api\V1\User\Games;

use App\Http\Controllers\Controller;
use App\Models\GameInvite;
use App\Models\KoodalGameScore;
use App\Models\KoodalSyncAnswer;
use App\Services\Games\CompatibilityReports;
use App\Services\Games\GameAccess;
use App\Services\Games\GameCatalog;
use App\Services\Games\GameRoomRegistry;
use App\Services\Games\VibeReport;
use App\Services\KoodalGameService;
use App\Services\KoodalVibeStoryService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * The entry points: what games exist, the hub for one partner, the Vibe Meter.
 */
class HubController extends Controller
{
    public function __construct(
        protected GameAccess $access,
        protected GameCatalog $catalog,
        protected KoodalGameService $games,
    ) {}

    public function catalog(): JsonResponse
    {
        return ApiResponse::success($this->catalog->all(), 'Games retrieved successfully');
    }

    /** Replaces the website's `games.hub` page. */
    public function partner(Request $request, string $profileCode, GameRoomRegistry $rooms): JsonResponse
    {
        $user = $request->user();
        $partner = $this->access->partner($user, $profileCode);

        $vibe = $this->games->recalculateVibeResult($user->id, $partner->id);
        $summary = $vibe->games_summary ?? [];

        $invites = GameInvite::query()
            ->where('status', 'pending')
            ->where('expires_at', '>', now())
            ->where(fn ($q) => $q
                ->where(fn ($x) => $x->where('sender_id', $user->id)->where('receiver_id', $partner->id))
                ->orWhere(fn ($x) => $x->where('sender_id', $partner->id)->where('receiver_id', $user->id)))
            ->get();

        $games = collect($this->catalog->all())->map(function (array $game) use ($rooms, $user, $partner, $summary, $invites) {
            $room = $rooms->driver($game['type'])->findActiveBetween($user, $partner);
            $mine = $invites->first(fn ($i) => $i->baseGameType() === $game['type'] && $i->sender_id === $user->id);
            $theirs = $invites->first(fn ($i) => $i->baseGameType() === $game['type'] && $i->sender_id === $partner->id);

            return $game + [
                'result' => $summary[$game['vibe_key']] ?? ['played' => false, 'score' => null, 'percentage' => 0],
                // `status` tells a game in progress (resume it) from a lobby
                // still waiting for the pair (Flip / Memory Snap say "waiting").
                'active_room' => $room ? [
                    'type' => $game['type'],
                    'code' => $rooms->driver($game['type'])->code($room),
                    'status' => $room->status === 'playing' ? 'playing' : 'lobby',
                ] : null,
                'outgoing_invite' => $mine ? ['id' => $mine->id, 'can_remind' => $mine->reminders_sent < config('game.timing.invite_max_reminders', 3)] : null,
                'incoming_invite' => $theirs ? ['id' => $theirs->id] : null,
            ];
        })->values();

        return ApiResponse::success([
            'partner' => $this->access->card($user, $partner),
            'vibe' => $this->vibePayload($vibe),
            'sync_status' => [
                'user_played' => KoodalSyncAnswer::where('user_id', $user->id)
                    ->whereHas('session', fn($q) => $q->where('user_one_id', min($user->id, $partner->id))->where('user_two_id', max($user->id, $partner->id)))
                    ->exists(),
                'partner_played' => KoodalSyncAnswer::where('user_id', $partner->id)
                    ->whereHas('session', fn($q) => $q->where('user_one_id', min($user->id, $partner->id))->where('user_two_id', max($user->id, $partner->id)))
                    ->exists(),
            ],
            'games' => $games,
        ], 'Games hub retrieved successfully');
    }

    /** The Vibe Meter result page: percentage, the game bars and the generated story. */
    public function vibe(Request $request, string $profileCode, VibeReport $report, KoodalVibeStoryService $stories, CompatibilityReports $reports): JsonResponse
    {
        $user = $request->user();
        $partner = $this->access->partner($user, $profileCode);

        $with = ['profile.primaryPhoto', 'profile.city', 'profile.state', 'profile.education', 'profile.religion', 'profile.community', 'preferences'];
        $user->loadMissing($with);
        $partner->load($with);

        $vibe = $this->games->recalculateVibeResult($user->id, $partner->id);
        $story = $stories->generateStory($user, $partner, $vibe);

        $place = fn ($u) => collect([$u->profile?->city?->name, $u->profile?->state?->name])->filter()->implode(', ') ?: null;

        $prefScore = isset($story['preference_match']['score']) ? (int) $story['preference_match']['score'] : null;
        $rasiScore = null;
        if (! empty($story['rasi_porutham']) && ($story['rasi_porutham']['status'] ?? '') === 'ready' && isset($story['rasi_porutham']['score'])) {
            $rasiScore = (int) $story['rasi_porutham']['score'];
        }

        return ApiResponse::success([
            'preference_score' => $prefScore,
            'rasi_porutham_score' => $rasiScore,
            'vibe' => $this->vibePayload($vibe),
            'story' => [
                'category_name' => $story['category_name'],
                'title' => $story['title'],
                'tagline' => $story['tagline'],
                'score' => $story['score'],
                'paragraphs' => $story['paragraphs'],
                'call_to_action' => $story['call_to_action'],
                'cta_url' => $story['cta_url'],
                'groom_name' => $story['groom_name'],
                'bride_name' => $story['bride_name'],
                'sync_qa' => $story['sync_qa'],
            ],
            'compatibility_bars' => $report->compatibilityBars($user, $partner, $vibe),
            // The Rasi Porutham / Preference percentages, linking to
            // GET profiles/{code}/compatibility; null where that profile is
            // not open to this member.
            'reports' => $reports->canSee($user, $partner)
                ? $reports->summary($story['rasi_porutham'], $story['preference_match'])
                : null,
            'you' => $this->access->card($user, $user) + ['location' => $place($user)],
            'partner' => $this->access->card($user, $partner) + ['location' => $place($partner)],
        ], 'Vibe meter retrieved successfully');
    }

    public function scores(Request $request): JsonResponse
    {
        $user = $request->user();
        $query = KoodalGameScore::query()
            ->with(['user', 'partner'])
            ->where(fn ($q) => $q->where('user_id', $user->id)->orWhere('partner_id', $user->id))
            ->latest('id');

        if ($code = $request->query('partner')) {
            $partner = $this->access->partner($user, $code);
            $query->where(fn ($q) => $q
                ->where(fn ($x) => $x->where('user_id', $user->id)->where('partner_id', $partner->id))
                ->orWhere(fn ($x) => $x->where('user_id', $partner->id)->where('partner_id', $user->id)));
        }

        $rows = $query->limit(min(50, $request->integer('limit', 20)))->get()->map(fn ($s) => [
            'id' => $s->id,
            'game_type' => $s->game_type,
            'score' => $s->score,
            'max_score' => $s->max_score,
            'played_at' => $s->created_at?->toIso8601String(),
            'partner_code' => ($s->user_id === $user->id ? $s->partner : $s->user)?->profile_code,
        ]);

        return ApiResponse::success($rows, 'Scores retrieved successfully');
    }

    protected function vibePayload($vibe): array
    {
        return [
            'percentage' => (int) $vibe->vibe_percentage,
            'common_tags' => $vibe->common_tags ?? [],
            'games_summary' => $vibe->games_summary ?? [],
        ];
    }
}
