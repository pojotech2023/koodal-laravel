<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Services\Analytics\AnalyticsReports;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * One invokable endpoint over four reports, because the route already
 * constrains `{report}` to the four names and a controller with four
 * near-identical methods would only be four places to forget the date clamp.
 */
class AnalyticsController extends Controller
{
    /** A year of daily aggregates is the widest window the reports are built for. */
    protected const MAX_DAYS = 366;

    public function __invoke(Request $request, AnalyticsReports $reports, string $report): JsonResponse
    {
        $this->authorize('analytics.view');

        $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ]);

        [$from, $to] = $this->window($request);

        return ApiResponse::success([
            'report' => $report,
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
            'data' => $reports->report($report, $from, $to),
        ], 'Analytics retrieved successfully');
    }

    /**
     * The window is clamped, not rejected.
     *
     * An analyst dragging a date picker to 2019 is asking a reasonable
     * question badly, and the useful answer is the widest window we can serve
     * — with the actual dates echoed in the payload, so the chart is never
     * labelled with a range it does not contain. The clamp also bounds the
     * analytics cache key space, which is otherwise one entry per pair of
     * dates anyone has ever typed.
     */
    protected function window(Request $request): array
    {
        $to = $request->date('to')?->endOfDay() ?? now()->endOfDay();
        $from = $request->date('from')?->startOfDay() ?? $to->copy()->subDays(29)->startOfDay();

        if ($from->diffInDays($to) > static::MAX_DAYS) {
            $from = $to->copy()->subDays(static::MAX_DAYS)->startOfDay();
        }

        return [$from, $to];
    }
}
