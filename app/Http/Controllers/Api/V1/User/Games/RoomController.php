<?php

namespace App\Http\Controllers\Api\V1\User\Games;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\FirebaseNotificationService;
use App\Services\Games\GameChatPolicy;
use App\Services\Games\GameRoomRegistry;
use App\Services\Games\Rooms\RoomDriver;
use App\Support\ApiResponse;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * The actions every live game shares. One controller, four games: the driver
 * behind `{type}` decides what a "room" is.
 */
class RoomController extends Controller
{
    use ResolvesRoom;

    public function show(Request $request, string $type, string $code): JsonResponse
    {
        [$driver, $room, $user] = $this->resolve($request, $type, $code, mutating: false);

        return $this->stateResponse($driver, $room, $user, sinceVersion: $request->query('since_version'));
    }

    public function join(Request $request, string $type, string $code): JsonResponse
    {
        // Not "mutating": the caller has no seat yet, so there is no partner to
        // check. The driver's join() applies the seating and block rules.
        [$driver, $room, $user] = $this->resolve($request, $type, $code, mutating: false);

        return $this->stateResponse($driver, $driver->join($room, $user), $user, 'Joined the game');
    }

    public function ready(Request $request, string $type, string $code): JsonResponse
    {
        [$driver, $room, $user] = $this->resolve($request, $type, $code);

        return $this->stateResponse($driver, $driver->ready($room, $user), $user, 'Ready status updated');
    }

    public function leave(Request $request, string $type, string $code): JsonResponse
    {
        [$driver, $room, $user] = $this->resolve($request, $type, $code, mutating: false);

        return $this->stateResponse($driver, $driver->leave($room, $user), $user, 'You left the game');
    }

    /** Rematch. May hand back a different room (Flip deals a new board). */
    public function reset(Request $request, string $type, string $code): JsonResponse
    {
        [$driver, $room, $user] = $this->resolve($request, $type, $code);

        return $this->stateResponse($driver, $driver->reset($room, $user), $user, 'New round ready');
    }

    public function message(Request $request, string $type, string $code, GameChatPolicy $chat): JsonResponse
    {
        $data = $request->validate(['message' => ['required', 'string', 'max:500']]);

        [$driver, $room, $user] = $this->resolve($request, $type, $code);

        $text = $chat->authorize($user, $data['message']);
        $driver->sendMessage($room, $user, $text);

        $this->pushToPartner($driver, $room, $user, $type, $text);

        return $this->stateResponse($driver, $room, $user, 'Message sent');
    }

    /**
     * The partner may be on another screen or have the app in the background,
     * where the room poll is paused: a push is the only way they hear about
     * it. Push only, with no row in the notification list: a chat would fill
     * the list and the badge, and the messages are already in the room. Never
     * allowed to fail the send.
     */
    protected function pushToPartner(RoomDriver $driver, Model $room, User $user, string $type, string $text): void
    {
        try {
            $partner = $driver->partnerOf($room, $user);

            if (! $partner || $partner->notificationSettings?->push_enabled === false) {
                return;
            }

            $code = $driver->code($room);

            app(FirebaseNotificationService::class)->sendToUser(
                $partner->id,
                $user->name,
                Str::limit($text, 80),
                [
                    'reference_id' => $code,
                    'game_type' => $type,
                    'room_code' => $code,
                    'collapse_key' => 'game-chat-'.$code,
                ],
                'game_chat',
                true,
                false,
            );
        } catch (\Throwable $e) {
            Log::warning('Game chat push failed: '.$e->getMessage());
        }
    }

    public function markRead(Request $request, string $type, string $code): JsonResponse
    {
        [$driver, $room, $user] = $this->resolve($request, $type, $code, mutating: false);

        $driver->markRead($room, $user);

        return ApiResponse::success(null, 'Messages marked as read');
    }
}
