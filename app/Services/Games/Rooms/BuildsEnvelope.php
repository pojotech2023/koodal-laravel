<?php

namespace App\Services\Games\Rooms;

use App\Models\User;
use App\Services\Games\GameAccess;

/**
 * The part of a room payload that is identical for every game, so the mobile
 * client has one parser for the frame and one per-game `game` object.
 */
trait BuildsEnvelope
{
    /**
     * @param  array<int, array<string, mixed>>  $messages
     * @param  array<string, mixed>  $game
     * @param  ?int  $startedAt  unix seconds the round went live, while the
     *                           app's 3-2-1 lead-in may still be showing;
     *                           null when the game has its own countdown or
     *                           has moved on
     * @return array<string, mixed>
     */
    protected function envelope(
        string $type,
        string $code,
        string $status,
        User $viewer,
        ?User $partner,
        int $youSlot,
        bool $youReady,
        bool $partnerReady,
        array $messages,
        array $game,
        ?int $startedAt = null,
    ): array {
        /** @var GameAccess $access */
        $access = app(GameAccess::class);

        return [
            'type' => $type,
            'code' => $code,
            'status' => $status,
            'started_at' => $status === 'playing' ? $startedAt : null,
            'server_time' => microtime(true),
            'you' => $access->card($viewer, $viewer) + ['slot' => $youSlot, 'ready' => $youReady],
            'partner' => $partner
                ? $access->card($viewer, $partner) + ['slot' => $youSlot === 1 ? 2 : 1, 'ready' => $partnerReady]
                : null,
            'messages' => $messages,
            'game' => $game,
        ];
    }
}
