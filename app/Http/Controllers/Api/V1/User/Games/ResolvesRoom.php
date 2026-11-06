<?php

namespace App\Http\Controllers\Api\V1\User\Games;

use App\Models\User;
use App\Services\Games\GameAccess;
use App\Services\Games\GameRoomRegistry;
use App\Services\Games\Rooms\RoomDriver;
use App\Support\ApiResponse;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

/**
 * What every room endpoint does first: turn `{type}/{code}` into a driver and a
 * room, and — for anything that changes the room — refuse if the other player
 * has since been blocked or suspended. Participation itself is enforced inside
 * each driver.
 */
trait ResolvesRoom
{
    /**
     * @return array{0: RoomDriver, 1: Model, 2: User}
     */
    protected function resolve(Request $request, string $type, string $code, bool $mutating = true): array
    {
        $driver = app(GameRoomRegistry::class)->driver($type);
        $room = $driver->find($code);
        $user = $request->user();

        if ($mutating && ($partner = $driver->partnerOf($room, $user))) {
            app(GameAccess::class)->assertCanPlay($user, $partner);
        }

        return [$driver, $room, $user];
    }

    protected function stateResponse(RoomDriver $driver, Model $room, User $user, string $message = 'Game state retrieved successfully', ?string $sinceVersion = null): JsonResponse
    {
        $state = $driver->state($room, $user);
        $state['version'] = $this->version($state);

        if ($sinceVersion !== null && hash_equals($state['version'], $sinceVersion)) {
            return ApiResponse::success([
                'changed' => false,
                'version' => $state['version'],
                'server_time' => $state['server_time'],
            ], $message);
        }

        return ApiResponse::success(['changed' => true] + $state, $message);
    }

    /**
     * A fingerprint of everything a client would redraw. Countdown values that
     * tick every poll are left out, otherwise "nothing changed" never matches.
     *
     * @param  array<string, mixed>  $state
     */
    protected function version(array $state): string
    {
        $stable = Arr::except($state, ['server_time', 'version', 'game.phase_seconds_left', 'game.seconds_left']);

        return substr(sha1(json_encode($stable)), 0, 16);
    }
}
