<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Services\Analytics\DashboardMetrics;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Thin by design: every number is aggregated in `DashboardMetrics` and cached
 * there. The panel polls this on an interval, so a controller that assembled
 * the payload itself would run a dozen COUNT(*) queries per poll per open tab.
 */
class DashboardController extends Controller
{
    public function __construct(protected DashboardMetrics $metrics) {}

    public function stats(Request $request): JsonResponse
    {
        $this->authorize('dashboard.view');

        return ApiResponse::success($this->metrics->stats(), 'Dashboard statistics retrieved successfully');
    }

    /**
     * The route constraint already restricts `{chart}` to the six known names,
     * so an unknown one is a 404 before it reaches here rather than an empty
     * array that renders as a blank card.
     *
     * `days` is clamped rather than validated: a request for 3,650 days is not
     * an error worth a 422, it is someone dragging a date picker too far, and
     * the honest response is the longest window the cache key space allows.
     */
    public function chart(Request $request, string $chart): JsonResponse
    {
        $this->authorize('dashboard.view');

        $days = max(7, min(365, $request->integer('days', 30)));

        return ApiResponse::success([
            'chart' => $chart,
            'days' => $days,
            'series' => $this->metrics->chart($chart, $days),
        ], 'Chart data retrieved successfully');
    }
}
