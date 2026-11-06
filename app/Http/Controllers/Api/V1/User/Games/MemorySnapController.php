<?php

namespace App\Http\Controllers\Api\V1\User\Games;

use App\Http\Controllers\Controller;
use App\Services\Games\Rooms\MemorySnapRoom;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MemorySnapController extends Controller
{
    use ResolvesRoom;

    public function __construct(protected MemorySnapRoom $snap) {}

    public function answer(Request $request, string $code): JsonResponse
    {
        $data = $request->validate([
            'question_index' => ['required', 'integer', 'min:0'],
            'answer' => ['required', 'string', 'size:1'],
        ]);

        [, $room, $user] = $this->resolve($request, 'memory_snap', $code);

        $room = $this->snap->answer($room, $user, (int) $data['question_index'], $data['answer']);

        return $this->stateResponse($this->snap, $room, $user, 'Answer saved');
    }
}
