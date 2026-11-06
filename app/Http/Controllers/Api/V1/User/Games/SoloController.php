<?php

namespace App\Http\Controllers\Api\V1\User\Games;

use App\Http\Controllers\Controller;
use App\Services\Games\GameAccess;
use App\Services\Games\SoloGames;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * The single-player Memory and Spot rounds. The server deals and grades; see
 * SoloGames for why the client no longer reports its own score.
 */
class SoloController extends Controller
{
    public function __construct(protected SoloGames $solo, protected GameAccess $access) {}

    public function startMemory(Request $request, string $profileCode): JsonResponse
    {
        $partner = $this->access->partner($request->user(), $profileCode);

        return ApiResponse::success($this->solo->startMemory($request->user(), $partner), 'Memory round ready');
    }

    public function finishMemory(Request $request, string $profileCode): JsonResponse
    {
        $data = $request->validate([
            'session_id' => ['required', 'string', 'max:64'],
            'answers' => ['required', 'array', 'min:1', 'max:10'],
            'answers.*.position' => ['required', 'integer', 'min:1', 'max:7'],
            'answers.*.symbol_id' => ['required', 'string', 'max:20'],
            'time_taken' => ['nullable', 'integer', 'min:0', 'max:3600'],
        ]);

        $partner = $this->access->partner($request->user(), $profileCode);

        return ApiResponse::created($this->solo->finishMemory(
            $request->user(), $partner, $data['session_id'], $data['answers'], (int) ($data['time_taken'] ?? 0),
        ), 'Score recorded');
    }

    public function startSpot(Request $request, string $profileCode): JsonResponse
    {
        $partner = $this->access->partner($request->user(), $profileCode);

        return ApiResponse::success($this->solo->startSpot($request->user(), $partner), 'Spot round ready');
    }

    public function finishSpot(Request $request, string $profileCode): JsonResponse
    {
        $data = $request->validate([
            'session_id' => ['required', 'string', 'max:64'],
            'taps' => ['present', 'array', 'max:36'],
            'taps.*' => ['integer', 'min:0', 'max:35'],
            'time_taken' => ['nullable', 'integer', 'min:0', 'max:3600'],
        ]);

        $partner = $this->access->partner($request->user(), $profileCode);

        return ApiResponse::created($this->solo->finishSpot(
            $request->user(), $partner, $data['session_id'], $data['taps'], (int) ($data['time_taken'] ?? 0),
        ), 'Score recorded');
    }
}
