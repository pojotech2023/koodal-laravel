<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\GameInvite;
use App\Models\User;
use App\Services\GameInviteService;
use App\Services\Games\GameAccess;
use App\Services\Games\GameInvites;
use App\Services\Games\Rooms\FlipRoom;
use App\Services\Games\Rooms\MemorySnapRoom;
use App\Services\UserPresenceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class GameInviteController extends Controller
{
    private const GAME_TYPES = ['sync', 'find_it', 'flip', 'memory_snap'];

    public function __construct(
        protected GameInviteService $inviteService,
        protected UserPresenceService $presenceService,
        protected GameAccess $access,
    ) {}

    /**
     * Send or retrieve an invite for a partner.
     */
    public function send(Request $request, int $partnerId): JsonResponse
    {
        // The route is behind `auth:web`; there is always a signed-in member.
        $user = Auth::guard('web')->user();
        if ($user->id === $partnerId) {
            return response()->json(['success' => false, 'message' => 'Cannot invite yourself.'], 422);
        }

        $gameType = $request->input('game_type', 'sync');
        if (! in_array($gameType, self::GAME_TYPES, true)) {
            return response()->json(['success' => false, 'message' => 'Unknown game.'], 422);
        }

        // Unknown, suspended or blocked (either way) is a 404, as in the app.
        $partner = $this->access->partnerById($user, $partnerId);

        try {
            $result = $this->inviteService->sendInvite($user, $partner, $gameType);

            $partnerStatus = $this->presenceService->formatStatus($partner);

            return response()->json([
                'success'        => true,
                'invite'         => $result['invite'],
                'room_code'      => $result['room_code'] ?? null,
                'redirect_url'   => $result['redirect_url'] ?? null,
                'is_duplicate'   => $result['is_duplicate'] ?? false,
                'can_remind'     => $result['can_remind'] ?? true,
                'partner_online' => $partnerStatus['is_online'] ?? false,
                'message'        => !empty($result['is_duplicate'])
                    ? 'Invitation already sent to ' . $partner->name
                    : 'Invitation sent to ' . $partner->name . ' 💕',
            ]);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error("Game invite send error: " . $e->getMessage(), ['trace' => $e->getTraceAsString()]);

            $fallbackUrl = match ($gameType) {
                'flip' => '/flip',
                'memory_snap' => route('games.memory_snap.start', $partnerId, false),
                'find_it' => route('games.find_it', $partnerId, false),
                default => route('games.sync', $partnerId, false),
            };

            return response()->json([
                'success'        => true,
                'invite'         => null,
                'room_code'      => null,
                'redirect_url'   => $fallbackUrl,
                'is_duplicate'   => false,
                'can_remind'     => true,
                'partner_online' => true,
                'message'        => 'Starting game with ' . $partner->name . ' 💕',
            ]);
        }
    }

    /**
     * Accept an invitation and return the live room redirect URL.
     *
     * Only the receiver, signed in as themselves, can accept. The route sits
     * behind `auth:web`, so a shared link opened while signed out goes through
     * the login page and comes back here. This method used to sign the visitor
     * in as the receiver instead — anyone holding an invite id got that
     * member's session.
     */
    public function accept(GameInvite $invite): JsonResponse|\Illuminate\Http\RedirectResponse
    {
        $user = Auth::guard('web')->user();

        if ($user->id === $invite->sender_id) {
            // The sender opening their own share link: nothing to accept.
            return $this->refuse('This is your invite — waiting for your partner to accept it.', route('games.hub', $invite->receiver_id), 403);
        }

        if ($user->id !== $invite->receiver_id) {
            return $this->refuse('This invite was sent to another member.', route('matches.recommended'), 403);
        }

        if ($invite->status !== 'pending' || ($invite->expires_at && $invite->expires_at->isPast())) {
            return $this->refuse('This invite has expired or was already answered.', route('matches.recommended'), 410);
        }

        $sender = User::find($invite->sender_id);
        if (! $sender || $sender->status !== 'active' || $this->access->blocked($user, $sender)) {
            return $this->refuse('You can no longer play with this member.', route('matches.recommended'), 403);
        }

        $invite->update(['status' => 'accepted']);

        if ($invite->game_type === 'flip') {
            // The board between these two, never the sender's game with someone else.
            // Only a board still waiting with nothing matched is reused; one
            // under way or played out gets a fresh board.
            $flip = app(FlipRoom::class);
            $flipGame = $flip->findActiveBetween($sender, $user);
            if (! $flipGame || $flipGame->status !== 'waiting' || ! empty($flipGame->matched)) {
                $flipGame = $flip->create($sender, $user);
            }
            $flip->join($flipGame, $user);

            return $this->accepted('/flip/' . $flipGame->code);
        }

        if ($invite->game_type === 'memory_snap') {
            $snap = app(MemorySnapRoom::class);
            $msGame = $snap->findActiveBetween($sender, $user) ?? $snap->create($sender, $user);
            $snap->join($msGame, $user);

            return $this->accepted('/memory-snap/room/' . $msGame->code);
        }

        if ($invite->game_type === 'find_it') {
            return $this->accepted('/games/' . $invite->sender_id . '/find-it');
        }

        // The sender already answered on their own (with this partner): answer
        // the same questions rather than wait in a room. New offline invites
        // carry their own type; older ones are recognised by the answers.
        if ($invite->game_type === 'sync_offline'
            || ($invite->game_type === 'sync' && app(GameInvites::class)->senderAlreadyPlayed($invite))) {
            return $this->accepted('/games/' . $invite->sender_id . '/sync');
        }

        $room = $this->inviteService->acceptInvite($invite);

        return $this->accepted('/games/room/' . $room->room_code);
    }

    protected function accepted(string $redirect): JsonResponse|\Illuminate\Http\RedirectResponse
    {
        if (! $this->asJson()) {
            return redirect($redirect);
        }

        return response()->json(['success' => true, 'redirect' => $redirect]);
    }

    protected function refuse(string $message, string $redirect, int $status): JsonResponse|\Illuminate\Http\RedirectResponse
    {
        if (! $this->asJson()) {
            return redirect($redirect)->with('error', $message);
        }

        // The pages follow `redirect`; the flash tells them why on arrival.
        session()->flash('error', $message);

        return response()->json(['success' => false, 'message' => $message, 'redirect' => $redirect], $status);
    }

    /** A page's fetch() (some send only a JSON body, no Accept header) vs. a followed link. */
    protected function asJson(): bool
    {
        return request()->wantsJson() || request()->ajax() || request()->isJson();
    }

    /**
     * Decline an invitation.
     */
    public function decline(GameInvite $invite): JsonResponse
    {
        $user = Auth::guard('web')->user();
        if (!$user || $user->id !== $invite->receiver_id) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
        }

        $invite->update(['status' => 'declined']);

        return response()->json(['success' => true, 'message' => 'Invitation declined.']);
    }

    /**
     * Send a reminder for a pending invite.
     */
    public function remind(GameInvite $invite): JsonResponse
    {
        $user = Auth::guard('web')->user();
        if (!$user || $user->id !== $invite->sender_id) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
        }

        $result = $this->inviteService->sendReminder($invite);

        return response()->json($result);
    }

    /**
     * Poll for incoming pending invites and accepted invites for automatic sender redirect.
     */
    public function pending(Request $request): JsonResponse
    {
        $user = Auth::guard('web')->user();
        if (!$user) {
            return response()->json([
                'count'             => 0,
                'invites'           => [],
                'accepted_redirect' => null,
            ]);
        }

        // 1. Incoming pending invites where current user is receiver
        $pendingInvites = GameInvite::with(['sender.profile'])
            ->where('receiver_id', $user->id)
            ->where('status', 'pending')
            ->where('expires_at', '>', now())
            ->latest()
            ->get();

        $invites = app(GameInvites::class);

        $formatted = $pendingInvites->map(function ($inv) use ($invites) {
            $isOfflineWaiting = false;
            $customMessage = null;

            // Answers the sender gave with *this* member, as in the app.
            if ($inv->game_type === 'sync_offline'
                || ($inv->game_type === 'sync' && $invites->senderAlreadyPlayed($inv))) {
                $isOfflineWaiting = true;
                $customMessage = ($inv->sender->name ?? 'Partner') . ': Already answered, waiting for your answers! Please play 🎯';
            }

            return [
                'id'                 => $inv->id,
                'sender_name'        => $inv->sender->name,
                'sender_id'          => $inv->sender_id,
                'game_type'          => $inv->game_type,
                'is_offline_waiting' => $isOfflineWaiting,
                'custom_message'     => $customMessage,
                'created_at'         => $inv->created_at->diffForHumans(),
            ];
        });

        // 2. Check if an invite sent by current user was accepted recently
        $acceptedInvite = GameInvite::where('sender_id', $user->id)
            ->where('status', 'accepted')
            ->where('updated_at', '>=', now()->subMinutes(15))
            ->latest()
            ->first();

        $acceptedRedirect = null;
        if ($acceptedInvite) {
            $pairSender = User::find($acceptedInvite->sender_id);
            $pairReceiver = User::find($acceptedInvite->receiver_id);

            if ($acceptedInvite->game_type === 'flip') {
                $flipGame = ($pairSender && $pairReceiver)
                    ? app(FlipRoom::class)->findActiveBetween($pairSender, $pairReceiver)
                    : null;
                if ($flipGame) {
                    $acceptedRedirect = '/flip/' . $flipGame->code;
                }
            } elseif ($acceptedInvite->game_type === 'memory_snap') {
                $msGame = ($pairSender && $pairReceiver)
                    ? app(MemorySnapRoom::class)->findActiveBetween($pairSender, $pairReceiver)
                    : null;

                if ($msGame) {
                    $acceptedRedirect = '/memory-snap/room/' . $msGame->code;
                }
            } elseif ($acceptedInvite->game_type === 'find_it') {
                $acceptedRedirect = '/games/' . $acceptedInvite->receiver_id . '/find-it?fresh=' . time();
            } else {
                $room = \App\Models\GameRoom::whereIn('user_a_id', [$acceptedInvite->sender_id, $acceptedInvite->receiver_id])
                    ->whereIn('user_b_id', [$acceptedInvite->sender_id, $acceptedInvite->receiver_id])
                    ->where('game_type', $acceptedInvite->game_type)
                    ->whereIn('status', ['lobby', 'playing'])
                    ->first();

                if ($room && empty($room->user_a_answers) && empty($room->user_b_answers)) {
                    $acceptedRedirect = '/games/room/' . $room->room_code;
                } else {
                    $acceptedInvite->update(['status' => 'expired']);
                }
            }

            if ($acceptedRedirect) {
                // Mark invite as expired so this redirect is sent ONLY ONCE to redirect the sender into the room
                $acceptedInvite->update(['status' => 'expired']);
            }
        }

        return response()->json([
            'count'             => $formatted->count(),
            'invites'           => $formatted,
            'accepted_redirect' => $acceptedRedirect,
        ]);
    }
}
