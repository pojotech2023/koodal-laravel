<?php

namespace App\Http\Controllers\Api\V1\User\Games;

use App\Http\Controllers\Controller;
use App\Services\Games\Rooms\FindItRoom;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FindItController extends Controller
{
    use ResolvesRoom;

    public function __construct(protected FindItRoom $findIt) {}

    public function start(Request $request, string $code): JsonResponse
    {
        [, $room, $user] = $this->resolve($request, 'find_it', $code);

        return $this->stateResponse($this->findIt, $this->findIt->start($room, $user), $user, 'Game started');
    }

    public function claim(Request $request, string $code): JsonResponse
    {
        $data = $request->validate(['difference_index' => ['required', 'integer', 'min:0']]);

        [, $room, $user] = $this->resolve($request, 'find_it', $code);

        ['room' => $room, 'result' => $result] = $this->findIt->claim($room, $user, (int) $data['difference_index']);

        $response = $this->stateResponse($this->findIt, $room, $user, match ($result) {
            'found' => 'Nice find!',
            'claimed_by_partner' => 'Your partner already found this one.',
            'already_found' => 'You already found this one.',
            default => 'Time is up.',
        });

        // Tell the client what happened to *this* tap, separately from the
        // state it should redraw.
        $payload = $response->getData(true);
        $payload['data']['result'] = $result;

        return response()->json($payload);
    }
}
