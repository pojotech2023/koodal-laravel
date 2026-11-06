<?php

namespace App\Http\Controllers\Api\V1\User\Games;

use App\Http\Controllers\Controller;
use App\Services\Games\GameAccess;
use App\Services\Games\Rooms\SyncRoom;
use App\Services\Games\SyncOffline;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SyncController extends Controller
{
    use ResolvesRoom;

    public function __construct(protected SyncRoom $sync) {}

    public function answer(Request $request, string $code): JsonResponse
    {
        $data = $request->validate([
            'question_id' => ['required', 'integer'],
            'selected_option' => ['required', 'string', 'max:10'],
        ]);

        [, $room, $user] = $this->resolve($request, 'sync', $code);

        $room = $this->sync->answer($room, $user, (int) $data['question_id'], $data['selected_option']);

        return $this->stateResponse($this->sync, $room, $user, 'Answer saved');
    }

    public function next(Request $request, string $code): JsonResponse
    {
        $data = $request->validate(['from_index' => ['nullable', 'integer', 'min:0']]);

        [, $room, $user] = $this->resolve($request, 'sync', $code);

        $room = $this->sync->next($room, $user, isset($data['from_index']) ? (int) $data['from_index'] : null);

        return $this->stateResponse($this->sync, $room, $user, 'Moved on');
    }

    /** Offline mode: the ten questions to answer on your own. */
    public function questions(Request $request, string $profileCode, GameAccess $access, SyncOffline $offline): JsonResponse
    {
        $partner = $access->partner($request->user(), $profileCode);

        return ApiResponse::success($offline->questions($request->user(), $partner), 'Questions retrieved successfully');
    }

    public function submit(Request $request, string $profileCode, GameAccess $access, SyncOffline $offline): JsonResponse
    {
        $data = $request->validate([
            'answers' => ['required', 'array', 'min:1', 'max:'.SyncRoom::QUESTION_COUNT],
            'answers.*' => ['required', 'string', 'max:10'],
        ]);

        $partner = $access->partner($request->user(), $profileCode);

        return ApiResponse::created(
            $offline->submit($request->user(), $partner, $data['answers']),
            'Answers submitted',
        );
    }
}
