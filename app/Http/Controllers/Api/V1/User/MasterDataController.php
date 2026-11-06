<?php

namespace App\Http\Controllers\Api\V1\User;

use App\Http\Controllers\Controller;
use App\Services\MasterData\MasterDataCache;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Public and heavily cached. Nothing here may ever be hardcoded in a client —
 * adding a community or a language must be an admin action, not a release.
 */
class MasterDataController extends Controller
{
    public function __construct(protected MasterDataCache $cache) {}

    /**
     * The single payload the Flutter app fetches on first launch.
     *
     * A client that sends `?version=` it already holds gets a 200 with
     * `unchanged: true` and no body — one round trip instead of ~400 KB on
     * every cold start.
     */
    public function __invoke(Request $request): JsonResponse
    {
        $version = $this->cache->version();

        if ($request->query('version') === $version) {
            return ApiResponse::success(
                ['version' => $version, 'unchanged' => true],
                'Master data is up to date',
            );
        }

        return ApiResponse::success($this->cache->all(), 'Master data retrieved successfully');
    }

    public function byType(Request $request, string $type): JsonResponse
    {
        $filters = match ($type) {
            'communities' => ['religion_id' => $request->query('religion_id')],
            'sub-communities' => ['community_id' => $request->query('community_id')],
            'lifestyle-options' => ['type' => $request->query('type')],
            'educations', 'occupations' => ['category' => $request->query('category')],
            default => [],
        };

        return ApiResponse::success([
            'version' => $this->cache->version(),
            'items' => $this->cache->list($type, array_filter($filters, fn ($v) => $v !== null)),
        ], 'Master data retrieved successfully');
    }
}
