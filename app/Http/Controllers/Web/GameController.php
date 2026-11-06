<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\GameRoom;
use App\Models\User;
use App\Services\Games\GameAccess;
use App\Services\Games\Rooms\FindItRoom;
use App\Services\Games\SoloGames;
use App\Services\KoodalGameService;
use App\Services\Profile\ProfileVisibility;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Website game pages for one partner (`/games/{partner}/…`). Every page goes
 * through GameAccess first — no playing yourself, a suspended member or
 * someone either side has blocked — and every score is worked out on the
 * server: Find It from the room's stored claims, Memory and Spot from a round
 * the server dealt (SoloGames). The browser only reports what was tapped.
 */
class GameController extends Controller
{
    public function __construct(
        protected KoodalGameService $gameService,
        protected \App\Services\UserPresenceService $presenceService,
        protected GameAccess $access,
        protected FindItRoom $findItRoom,
        protected SoloGames $solo,
    ) {}

    protected function partner(int $partnerId): User
    {
        return $this->access->partnerById(Auth::guard('web')->user(), $partnerId);
    }

    /**
     * Display Games Hub for a partner profile.
     */
    public function hub(int $partnerId)
    {
        $user = Auth::guard('web')->user();

        if ($user->id === $partnerId) {
            return redirect()->route('matches.recommended')->with('error', 'You cannot play games with yourself.');
        }

        $partner    = $this->partner($partnerId);
        session(['active_partner_id' => $partner->id]);
        $vibeResult = $this->gameService->recalculateVibeResult($user->id, $partner->id);

        $syncStatus = [
            'userPlayed'    => \App\Models\KoodalSyncAnswer::where('user_id', $user->id)->exists(),
            'partnerPlayed' => \App\Models\KoodalSyncAnswer::where('user_id', $partner->id)->exists(),
        ];

        $partnerPresence = $this->presenceService->formatStatus($partner);

        return view('games.hub', [
            'user'            => $user,
            'partner'         => $partner,
            'vibe'            => $vibeResult,
            'syncStatus'      => $syncStatus,
            'partnerPresence' => $partnerPresence,
        ]);
    }

    /**
     * Koodal Sync (Compatibility Test) Game view.
     */
    public function sync(int $partnerId)
    {
        $user = Auth::guard('web')->user();
        $partner = $this->partner($partnerId);

        // Find existing GameRoom for sync between these two users
        $room = \App\Models\GameRoom::whereIn('user_a_id', [$user->id, $partner->id])
            ->whereIn('user_b_id', [$user->id, $partner->id])
            ->where('game_type', 'sync')
            ->latest('id')
            ->first();

        // A live round in progress belongs to the live room; opening the
        // offline page must not wipe it (it used to reset any room whose
        // question set was short of ten, e.g. a 5-question invite room).
        if ($room && $room->status === 'playing') {
            return redirect()->route('games.room', $room->room_code);
        }

        // Check if an ongoing room already exists with questions that both players must share
        $questions = null;
        if ($room && !empty($room->question_ids) && is_array($room->question_ids)) {
            $bothFinished = !empty($room->user_a_answers) && !empty($room->user_b_answers) && $room->status === 'completed';
            if (!$bothFinished) {
                $qMap = \App\Models\KoodalSyncQuestion::where('is_active', true)
                    ->whereIn('id', $room->question_ids)
                    ->get()
                    ->keyBy('id');
                $ordered = collect($room->question_ids)->map(fn($id) => $qMap->get($id))->filter()->values();
                if ($ordered->count() >= 10) {
                    $hasFood = $ordered->contains(fn($q) => $this->gameService->isFoodQuestion($q));
                    $hasLocation = $ordered->contains(fn($q) => $this->gameService->isLocationQuestion($q));
                    if ($hasFood && $hasLocation) {
                        $questions = $ordered->toArray();
                    }
                }
            }
        }

        if (!$questions) {
            $questions = $this->gameService->getSyncQuestionsForPair($user->id, $partner->id, 10);
            $qIds = array_map(fn($q) => (int)$q['id'], $questions);

            if (!$room) {
                $room = \App\Models\GameRoom::create([
                    'room_code'    => (string) \Illuminate\Support\Str::uuid(),
                    'user_a_id'    => $user->id,
                    'user_b_id'    => $partner->id,
                    'game_type'    => 'sync',
                    'status'       => 'lobby',
                    'question_ids' => $qIds,
                ]);
            } else {
                $room->update([
                    'question_ids'           => $qIds,
                    'status'                 => 'lobby',
                    'current_question_index' => 0,
                    'user_a_answers'         => [],
                    'user_b_answers'         => [],
                    'user_a_score'           => 0,
                    'user_b_score'           => 0,
                    'user_a_ready'           => false,
                    'user_b_ready'           => false,
                    'started_at'             => null,
                    'ended_at'               => null,
                ]);
            }
        }

        // Check if partner has already played (has any sync answers recorded)
        $partnerAlreadyPlayed = \App\Models\KoodalSyncAnswer::where('user_id', $partner->id)->exists();
        $partnerPresence = $this->presenceService->formatStatus($partner);

        return view('games.sync', [
            'user'                 => $user,
            'partner'              => $partner,
            'questions'            => $questions,
            'partnerAlreadyPlayed' => $partnerAlreadyPlayed,
            'partnerPresence'      => $partnerPresence,
            'room'                 => $room,
        ]);
    }

    /**
     * Submit Koodal Sync answers.
     */
    public function postSync(int $partnerId, Request $request)
    {
        $user = Auth::guard('web')->user();
        $partner = $this->partner($partnerId);
        $request->validate([
            'answers' => 'required|array|min:1|max:20',
            'answers.*' => 'required|string|max:20',
        ]);

        $result = $this->gameService->submitSyncSession($user->id, $partner->id, $request->input('answers'));

        // Also record in GameRoom if active
        $room = \App\Models\GameRoom::whereIn('user_a_id', [$user->id, $partnerId])
            ->whereIn('user_b_id', [$user->id, $partnerId])
            ->where('game_type', 'sync')
            ->latest('id')
            ->first();

        // Never into a live round: that room's answers belong to the live game.
        if ($room && $room->status !== 'playing') {
            $isUserA = ($user->id === $room->user_a_id);
            $uAnswers = [];
            foreach ($request->input('answers', []) as $qId => $optKey) {
                $uAnswers[] = ['question_id' => (int)$qId, 'option' => $optKey];
            }
            if ($isUserA) {
                $room->user_a_answers = $uAnswers;
            } else {
                $room->user_b_answers = $uAnswers;
            }

            if (!empty($room->user_a_answers) && !empty($room->user_b_answers)) {
                $room->status = 'completed';
                $room->ended_at = now();
            }

            $room->save();
        }

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'result' => $result,
            ]);
        }

        return redirect()->route('games.vibe', $partnerId)->with('success', 'Koodal Sync completed! ' . $result['matched_count'] . ' out of ' . $result['total_questions'] . ' answers matched.');
    }

    /**
     * Koodal Find It (Find the Difference) Game view.
     *
     * The page plays in the pair's live room, on the room's own picture, so
     * both partners hunt the same seven spots. A room still in its lobby or
     * mid-round is joined as it is; only a finished one is replaced.
     */
    public function findIt(int $partnerId)
    {
        $user = Auth::guard('web')->user();
        $partner = $this->partner($partnerId);
        $partnerPresence = $this->presenceService->formatStatus($partner);

        $room = $this->findItRoom->findActiveBetween($user, $partner);
        $room = $room ? $this->findItRoom->settled($room) : null;

        if (! $room || ! in_array($room->status, ['lobby', 'playing'], true)) {
            $room = $this->findItRoom->create($user, $partner);
        }

        $room = $this->findItRoom->ensureContent($room);

        return view('games.find_it', [
            'user'            => $user,
            'partner'         => $partner,
            'partnerPresence' => $partnerPresence,
            'partnerPhotoUrl' => $this->access->card($user, $partner)['avatar'],
            'challenge'       => $this->findItRoom->challengeFor($room),
            'room'            => $room,
            // 0 = untimed: the page then shows no clock.
            'timeLimit'       => $this->findItRoom->timeLimit() ?? 0,
        ]);
    }

    /**
     * End-of-round call from the Find It page. The score comes from the
     * room's stored claims (FindItRoom records it for both partners when the
     * round closes); whatever counts or score the browser sends are ignored.
     */
    public function postFindIt(int $partnerId, Request $request)
    {
        $user = Auth::guard('web')->user();
        $partner = $this->partner($partnerId);

        $room = GameRoom::query()
            ->where('game_type', 'find_it')
            ->where(fn ($q) => $q
                ->where(fn ($x) => $x->where('user_a_id', $user->id)->where('user_b_id', $partner->id))
                ->orWhere(fn ($x) => $x->where('user_a_id', $partner->id)->where('user_b_id', $user->id)))
            ->latest('id')
            ->first();

        if (! $room) {
            return response()->json(['success' => false, 'message' => 'No Find It game with this member.'], 404);
        }

        $room = $this->findItRoom->finish($room, $user);

        if ($room->status !== 'completed') {
            return response()->json([
                'success'      => false,
                'status'       => $room->status,
                'seconds_left' => $this->findItRoom->secondsLeft($room),
                'message'      => 'The round is still running.',
            ], 409);
        }

        return response()->json([
            'success'  => true,
            'score'    => (int) $room->user_a_score,
            'redirect' => route('games.vibe', $partner->id),
        ]);
    }

    /**
     * Koodal Memory (Sequence Memory) Game view. The server deals the round
     * and keeps the answer key; the page posts back only what was picked.
     */
    public function memory(int $partnerId)
    {
        $user = Auth::guard('web')->user();
        $partner = $this->partner($partnerId);

        return view('games.memory', [
            'user' => $user,
            'partner' => $partner,
            'round' => $this->solo->startMemory($user, $partner),
        ]);
    }

    /**
     * Grade a Memory round against the dealt key (single use).
     */
    public function postMemory(int $partnerId, Request $request)
    {
        $user = Auth::guard('web')->user();
        $partner = $this->partner($partnerId);
        $data = $request->validate([
            'session_id' => 'required|string|max:64',
            'answers' => 'required|array|min:1|max:10',
            'answers.*.position' => 'required|integer|min:1|max:7',
            'answers.*.symbol_id' => 'required|string|max:20',
            'time_taken' => 'nullable|integer|min:0|max:3600',
        ]);

        $result = $this->solo->finishMemory($user, $partner, $data['session_id'], $data['answers'], (int) ($data['time_taken'] ?? 0));

        return response()->json([
            'success' => true,
            'redirect' => route('games.vibe', $partner->id),
        ] + $result);
    }

    /**
     * Koodal Spot (Spot the Symbol Grid) Game view: exactly ten targets,
     * dealt and remembered by the server.
     */
    public function spot(int $partnerId)
    {
        $user = Auth::guard('web')->user();
        $partner = $this->partner($partnerId);
        $round = $this->solo->startSpot($user, $partner);

        return view('games.spot', [
            'user' => $user,
            'partner' => $partner,
            'round' => $round,
            'grid' => $round['grid'],
            'targetSymbol' => $round['target_symbol'],
            'targetCount' => $round['target_count'],
        ]);
    }

    /**
     * Grade a Spot round from the cells tapped (single use).
     */
    public function postSpot(int $partnerId, Request $request)
    {
        $user = Auth::guard('web')->user();
        $partner = $this->partner($partnerId);
        $data = $request->validate([
            'session_id' => 'required|string|max:64',
            'taps' => 'present|array|max:36',
            'taps.*' => 'integer|min:0|max:35',
            'time_taken' => 'nullable|integer|min:0|max:3600',
        ]);

        $result = $this->solo->finishSpot($user, $partner, $data['session_id'], $data['taps'], (int) ($data['time_taken'] ?? 0));

        return response()->json([
            'success' => true,
            'redirect' => route('games.vibe', $partner->id),
        ] + $result);
    }

    /**
     * Koodal Vibe Meter Result View.
     */
    public function vibe(int $partnerId, \App\Services\KoodalVibeStoryService $storyService)
    {
        $user = Auth::guard('web')->user();
        $user->loadMissing(['profile.primaryPhoto', 'profile.city', 'profile.state', 'profile.education', 'profile.religion', 'profile.community', 'preferences', 'photos']);
        $partner = $this->partner($partnerId);
        $partner->load(['profile.primaryPhoto', 'profile.city', 'profile.state', 'profile.education', 'profile.religion', 'profile.community', 'preferences', 'photos']);

        $vibeResult = $this->gameService->recalculateVibeResult($user->id, $partner->id);
        $storyData = $storyService->generateStory($user, $partner, $vibeResult);

        // Resolve uploaded member profile photos (fallback to null so view renders default profile avatar)
        $userPhotoObj = $user->profile?->primaryPhoto ?? $user->photos->first();
        $userPhotoUrl = $userPhotoObj?->urlFor('thumbnail') ?? $userPhotoObj?->urlFor('original');

        // The partner's photo only when their photo privacy lets this viewer see it.
        $partnerPhotoUrl = null;
        if (app(ProfileVisibility::class)->canViewPhotos($user, $partner)) {
            $partnerPhotoObj = $partner->profile?->primaryPhoto ?? $partner->photos->first();
            $partnerPhotoUrl = $partnerPhotoObj?->urlFor('thumbnail') ?? $partnerPhotoObj?->urlFor('original');
        }

        $reports = app(\App\Services\Games\CompatibilityReports::class);
        $userLocation = $reports->location($user);
        $partnerLocation = $reports->location($partner);

        $compatibilityBars = app(\App\Services\Games\VibeReport::class)
            ->compatibilityBars($user, $partner, $vibeResult);
        $rasiEval = $storyData['rasi_porutham'];
        $prefEval = $storyData['preference_match'];

        return view('games.vibe', [
            'user' => $user,
            'partner' => $partner,
            'vibe' => $vibeResult,
            'story' => $storyData,
            'preferenceMatch' => $prefEval,
            'rasiPorutham' => $rasiEval,
            'userPhotoUrl' => $userPhotoUrl,
            'partnerPhotoUrl' => $partnerPhotoUrl,
            'userLocation' => $userLocation,
            'partnerLocation' => $partnerLocation,
            'compatibilityBars' => $compatibilityBars,
        ]);
    }

    /**
     * Rasi Porutham (10-porutham horoscope report) with this partner — the
     * same page as the profile's, behind the same partner check as every game.
     */
    public function rasiPorutham(int $partnerId, \App\Services\Games\CompatibilityReports $reports)
    {
        return view('profile.rasi', $reports->pageData(Auth::guard('web')->user(), $this->partner($partnerId)));
    }

    /**
     * Partner Preference Match with this partner.
     */
    public function preference(int $partnerId, \App\Services\Games\CompatibilityReports $reports)
    {
        return view('profile.preference', $reports->pageData(Auth::guard('web')->user(), $this->partner($partnerId)));
    }
}
