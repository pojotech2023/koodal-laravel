<?php

namespace App\Http\Controllers\Api\V1\User\Games;

use App\Http\Controllers\Controller;
use App\Models\FlipGame;
use App\Services\Games\Rooms\FlipRoom;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FlipController extends Controller
{
    use ResolvesRoom;

    public function __construct(protected FlipRoom $flip) {}

    /** An open game anyone with the code can join — for sharing a link outside the app. */
    public function create(Request $request): JsonResponse
    {
        $game = $this->flip->createOpen($request->user());

        return ApiResponse::created(
            $this->flip->state($game, $request->user()) + ['invite_code' => $game->code],
            'Game created. Share the code to invite a partner.',
        );
    }

    public function tile(Request $request, string $code): JsonResponse
    {
        $data = $request->validate(['tile' => ['required', 'integer', 'min:0', 'max:15']]);

        [, $room, $user] = $this->resolve($request, 'flip', $code);
        /** @var FlipGame $room */
        $result = $this->flip->flipTile($room, $user, (int) $data['tile']);

        $response = $this->stateResponse($this->flip, $room, $user, $result['matched'] ? 'It is a match!' : 'Tile flipped');
        $payload = $response->getData(true);
        $payload['data']['flip'] = $result;

        return response()->json($payload);
    }

    public function preview(Request $request, string $code): JsonResponse
    {
        [, $room, $user] = $this->resolve($request, 'flip', $code);
        /** @var FlipGame $room */
        $this->flip->preview($room, $user);

        return $this->stateResponse($this->flip, $room, $user, 'Peek!');
    }
}
