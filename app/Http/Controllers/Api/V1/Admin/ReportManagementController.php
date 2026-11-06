<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Actions\Report\AssignReport;
use App\Actions\Report\DismissReport;
use App\Actions\Report\ResolveReport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AssignReportRequest;
use App\Http\Requests\Admin\DismissReportRequest;
use App\Http\Requests\Admin\ResolveReportRequest;
use App\Http\Resources\Admin\AdminReportResource;
use App\Models\Report;
use App\Models\User;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

/**
 * The moderation queue. All three writes already exist as Actions, so this is
 * wiring — which is the shape a controller should have.
 */
class ReportManagementController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Report::class);

        $request->validate([
            'status' => ['nullable', 'in:open,investigating,action_taken,dismissed'],
            'category' => ['nullable', 'string', 'max:40'],
            'assigned_to' => ['nullable', 'integer'],
            'unassigned' => ['nullable', 'boolean'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $reports = Report::query()
            ->with([
                'reporter:id,profile_code,name',
                'reportedUser:id,profile_code,name,status',
                'assignee:id,name',
                'resolver:id,name',
            ])

            ->status($request->string('status')->toString() ?: null)
            ->when($request->filled('category'), fn ($q) => $q->where('category', $request->input('category')))
            ->when($request->integer('assigned_to'), fn ($q, $id) => $q->where('assigned_to', $id))
            ->when($request->boolean('unassigned'), fn ($q) => $q->whereNull('assigned_to'))

            // Oldest first, and unlike the other queues this one is a safety
            // SLA rather than a courtesy: the report that has waited longest is
            // the harassment nobody has looked at yet.
            ->orderBy('created_at')
            ->paginate($request->integer('per_page', 25));

        $this->hydrateHistory($reports->getCollection());

        return ApiResponse::paginated($reports, AdminReportResource::class, 'Reports retrieved successfully');
    }

    public function assign(AssignReportRequest $request, Report $report, AssignReport $assign): JsonResponse
    {
        $this->authorize('assign', $report);

        // Defaults to the caller: picking up the next item in your own queue
        // is the common case and should not require naming yourself.
        $assignee = $request->filled('assignee_id')
            ? User::findOrFail($request->integer('assignee_id'))
            : $request->user();

        $report = $assign($report, $assignee, $request->user());

        return ApiResponse::success(
            new AdminReportResource($report->load('reporter', 'reportedUser', 'assignee')),
            'Report assigned',
        );
    }

    public function resolve(ResolveReportRequest $request, Report $report, ResolveReport $resolve): JsonResponse
    {
        $this->authorize('resolve', $report);

        $report = $resolve(
            $report,
            $request->user(),
            $request->string('action_taken')->toString(),
            $request->input('note'),
        );

        return ApiResponse::success(
            new AdminReportResource($report->load('reporter', 'reportedUser', 'resolver')),
            'Report resolved',
        );
    }

    /**
     * A separate endpoint from `resolve`, not a flag on it.
     *
     * `dismissed` and `action_taken = 'none'` are different outcomes:
     * `none` means investigated and found not to warrant action, `dismissed`
     * means the report itself was not actionable — duplicate, retaliatory,
     * nothing to go on. The two counts are read separately when judging
     * whether a report category is working, and collapsing them makes both
     * numbers meaningless.
     */
    public function dismiss(DismissReportRequest $request, Report $report, DismissReport $dismiss): JsonResponse
    {
        $this->authorize('resolve', $report);

        $report = $dismiss($report, $request->user(), $request->input('note'));

        return ApiResponse::success(
            new AdminReportResource($report->load('reporter', 'reportedUser', 'resolver')),
            'Report dismissed',
        );
    }

    // --- Internals ---------------------------------------------------------

    /**
     * How many times each accused account has been reported, in total.
     *
     * This is the number a moderator cannot derive from the report in front of
     * them, and it changes the decision: one complaint is noise, five from
     * five different people is a pattern. It is filled per *page* with one
     * grouped query — the same shape `ViewerState` uses — rather than as a
     * correlated subquery per row, which would make the queue's plan depend on
     * the page size.
     *
     * @param  Collection<int, Report>  $reports
     */
    protected function hydrateHistory($reports): void
    {
        $userIds = $reports->pluck('reported_user_id')->filter()->unique();

        if ($userIds->isEmpty()) {
            return;
        }

        $counts = Report::query()
            ->whereIn('reported_user_id', $userIds)
            ->selectRaw('reported_user_id, COUNT(*) as total')
            ->groupBy('reported_user_id')
            ->pluck('total', 'reported_user_id');

        foreach ($reports as $report) {
            $report->reports_against_count = (int) ($counts[$report->reported_user_id] ?? 0);
        }
    }
}
