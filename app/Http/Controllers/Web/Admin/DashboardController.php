<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\MatrimonyLimit;
use App\Services\Analytics\DashboardMetrics;
use Illuminate\View\View;

/**
 * The `Web\Admin` sibling of {@see \App\Http\Controllers\Api\V1\Admin\
 * DashboardController} — same `DashboardMetrics` service, same cached
 * aggregates, no client-side aggregation. The API version returns each stat
 * block and chart series as its own JSON response, polled; this one calls
 * `DashboardMetrics` directly and hands the whole thing to one Blade view,
 * which embeds every chart's series as `@json($series)` for
 * resources/js/admin.js's Chart.js wiring to read — docs/06-admin-panel-guide
 * .md §8.
 *
 * The six chart names are fixed here, not user-suppliable — the API route
 * constrains `{chart}` the same way; a Blade page that always renders the
 * same six cards has no equivalent route parameter to validate.
 */
class DashboardController extends Controller
{
    /** @var array<int, string> */
    protected const CHARTS = ['registrations', 'revenue', 'subscriptions', 'demographics', 'active-users', 'locations'];

    public function __construct(protected DashboardMetrics $metrics) {}

    public function index(): View
    {
        $this->authorize('dashboard.view');

        $charts = collect(static::CHARTS)
            ->mapWithKeys(fn (string $chart) => [$chart => $this->metrics->chart($chart, 30)]);

        return view('admin.dashboard', [
            'stats' => $this->metrics->stats(),
            'charts' => $charts,
            'limit' => MatrimonyLimit::current(),
        ]);
    }
}
