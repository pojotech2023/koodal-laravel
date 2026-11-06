<?php

namespace App\Http\Controllers\Api\V1\User\Games;

use App\Http\Controllers\Controller;
use App\Models\GameInvite;
use App\Services\Games\GameAccess;
use App\Services\Games\GameCatalog;
use App\Services\Games\GameInvites;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class InviteController extends Controller
{
    public function __construct(
        protected GameInvites $invites,
        protected GameAccess $access,
        protected GameCatalog $catalog,
    ) {}

    public function send(Request $request, string $profileCode): JsonResponse
    {
        $data = $request->validate([
            'game_type' => ['required', Rule::in(collect($this->catalog->all())->pluck('type'))],
            'mode' => ['nullable', Rule::in(['live', 'offline'])],
            'force_fresh' => ['nullable', 'boolean'],
        ]);

        $user = $request->user();
        $partner = $this->access->partner($user, $profileCode);

        $result = $this->invites->send(
            $user,
            $partner,
            $data['game_type'],
            $data['mode'] ?? 'live',
            (bool) ($data['force_fresh'] ?? false),
        );

        $name = $partner->profile?->displayNameFor($user) ?? $partner->name;
        $joined = $result['joined'] ?? false;

        return ApiResponse::created([
            'invite' => $this->present($result['invite'], $user),
            'room' => $result['room'],
            'is_duplicate' => $result['is_duplicate'],
            // The partner had already invited you to this game: you joined it.
            'joined' => $joined,
            'partner_online' => $this->access->card($user, $partner)['presence']['is_online'],
        ], match (true) {
            $joined => "Joined {$name}'s game 💕",
            $result['is_duplicate'] => "Invitation already sent to {$name}",
            default => "Invitation sent to {$name} 💕",
        });
    }

    /** What the app polls every few seconds while it is open. */
    public function pending(Request $request): JsonResponse
    {
        $user = $request->user();
        $result = $this->invites->pending($user);

        return ApiResponse::success([
            'count' => $result['incoming']->count(),
            'incoming' => $result['incoming']->map(fn ($i) => $this->present($i, $user))->values(),
            'outgoing' => $result['outgoing']->map(fn ($i) => $this->present($i, $user))->values(),
            'accepted' => $result['accepted'],
        ], 'Invites retrieved successfully');
    }

    public function show(Request $request, GameInvite $invite): JsonResponse
    {
        $user = $request->user();
        abort_unless(in_array($user->id, [$invite->sender_id, $invite->receiver_id], true), 404);

        return ApiResponse::success($this->present($invite, $user), 'Invite retrieved successfully');
    }

    public function accept(Request $request, GameInvite $invite): JsonResponse
    {
        $result = $this->invites->accept($request->user(), $invite);

        return ApiResponse::success($result, 'Invitation accepted');
    }

    public function decline(Request $request, GameInvite $invite): JsonResponse
    {
        $this->invites->decline($request->user(), $invite);

        return ApiResponse::success(null, 'Invitation declined');
    }

    public function remind(Request $request, GameInvite $invite): JsonResponse
    {
        $invite = $this->invites->remind($request->user(), $invite);

        return ApiResponse::success($this->present($invite, $request->user()), 'Reminder sent');
    }

    /** @return array<string, mixed> */
    protected function present(GameInvite $invite, $viewer): array
    {
        $invite->loadMissing(['sender.profile.primaryPhoto', 'receiver.profile.primaryPhoto']);

        $type = $invite->baseGameType();
        $game = collect($this->catalog->all())->firstWhere('type', $type);
        $mine = $invite->sender_id === $viewer->id;
        $other = $mine ? $invite->receiver : $invite->sender;

        $answered = ! $mine && $type === 'sync' && $invite->status === 'pending'
            && $this->invites->senderAlreadyPlayed($invite);
        // Accepting means answering on your own: an offline invite (sent so
        // from either side), or the sender has already answered.
        $offlineWaiting = $answered || (! $mine && $invite->isOfflineSync() && $invite->status === 'pending');

        return [
            'id' => $invite->id,
            'direction' => $mine ? 'outgoing' : 'incoming',
            'game_type' => $type,
            'mode' => $invite->isOfflineSync() ? 'offline' : 'live',
            'title' => $game['title'] ?? $type,
            'icon' => $game['icon'] ?? '🎮',
            'status' => $invite->status,
            'partner' => $other ? $this->access->card($viewer, $other) : null,
            'room' => $this->invites->roomRef($invite),
            'offline_waiting' => $offlineWaiting,
            'message' => match (true) {
                $answered => 'Already played — waiting for your answers! 🎯',
                $offlineWaiting => 'Answer on your own — results unlock once you have both played 🎯',
                default => null,
            },
            'reminders_sent' => (int) $invite->reminders_sent,
            'can_remind' => $mine && $invite->status === 'pending'
                && $invite->reminders_sent < config('game.timing.invite_max_reminders', 3),
            'created_at' => $invite->created_at?->toIso8601String(),
            'expires_at' => $invite->expires_at?->toIso8601String(),
        ];
    }
}
