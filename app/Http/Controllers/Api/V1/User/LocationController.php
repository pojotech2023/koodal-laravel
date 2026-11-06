<?php

namespace App\Http\Controllers\Api\V1\User;

use App\Http\Controllers\Controller;
use App\Services\MasterData\MasterDataCache;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LocationController extends Controller
{
    public function __construct(protected MasterDataCache $cache) {}

    public function countries(): JsonResponse
    {
        return ApiResponse::success($this->cache->countries(), 'Countries retrieved successfully');
    }

    public function states(Request $request): JsonResponse
    {
        $request->validate(['country_id' => ['nullable', 'integer', 'exists:countries,id']]);

        return ApiResponse::success(
            $this->cache->states($request->integer('country_id') ?: null),
            'States retrieved successfully',
        );
    }

    /**
     * `q` drives the typeahead. Two-character minimum: a one-letter prefix
     * matches thousands of rows and is never what the user meant.
     */
    public function cities(Request $request): JsonResponse
    {
        $request->validate([
            'state_id' => ['nullable', 'integer', 'exists:states,id'],
            'q' => ['nullable', 'string', 'min:2', 'max:60'],
        ]);

        return ApiResponse::success(
            $this->cache->cities(
                $request->integer('state_id') ?: null,
                $request->query('q'),
                $request->integer('limit', 50),
            ),
            'Cities retrieved successfully',
        );
    }
}
