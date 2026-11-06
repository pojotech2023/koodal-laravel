<?php

namespace App\Http\Controllers\Web\Admin;

use App\Actions\Report\AssignReport;
use App\Actions\Report\DismissReport;
use App\Actions\Report\ResolveReport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AssignReportRequest;
use App\Http\Requests\Admin\DismissReportRequest;
use App\Http\Requests\Admin\ResolveReportRequest;
use App\Models\Report;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

/**
 * The `Web\Admin` sibling of {@see \App\Http\Controllers\Api\V1\Admin\
 * ReportManagementController} — same query, same filters, same `ReportPolicy`
 * abilities, same three Actions, and the same
 * `reports_against_count` hydration, which is the one number a moderator
 * cannot derive from the report in front of them: one complaint is noise, five
 * from five different people is a pattern.
 *
 * Resolve and dismiss stay two endpoints, not one with a flag —
 * `action_taken = 'none'` (investigated, no action warranted) and `dismissed`
 * (the report itself was not actionable) are counted separately when judging
 * whether a category is working, and collapsing them makes both numbers
 * meaningless. The API sibling's docblock argues this at length; the panel
 * must not quietly re-merge them in the UI either, which is why the two live
 * in visibly separate forms on the detail page.
 */
class ReportController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Report::class);

        $request->validate([
            'status' => ['nullable', 'in:open,investigating,action_taken,dismissed'],
            'category' => ['nullable', 'string', 'max:40'],
            'mine' => ['nullable', 'boolean'],
            'unassigned' => ['nullable', 'boolean'],
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

            // "Mine" instead of the API's `assigned_to=<id>`: a staff member
            // browsing the panel wants their own queue, and a numeric user id
            // in a URL they have to look up first is not that. The API keeps
            // the general form because a script may legitimately ask for
            // someone else's.
            ->when($request->boolean('mine'), fn ($q) => $q->where('assigned_to', $request->user()->id))
            ->when($request->boolean('unassigned'), fn ($q) => $q->whereNull('assigned_to'))

            // Oldest first, and here that is a safety SLA rather than a
            // courtesy: the report that has waited longest is the harassment
            // nobody has looked at yet.
            ->orderBy('created_at')
            ->paginate(25)
            ->withQueryString();

        $this->hydrateHistory($reports->getCollection());

        return view('admin.reports.index', [
            'reports' => $reports,
            'categories' => config('matrimony.reports.categories', []),
            'openCount' => Report::query()->whereIn('status', ['open', 'investigating'])->count(),
        ]);
    }

    public function show(Report $report): View
    {
        $this->authorize('view', $report);

        $report->load([
            'reporter:id,profile_code,name,email,status',
            'reportedUser:id,profile_code,name,email,status',
            'assignee:id,name',
            'resolver:id,name',
        ]);

        $this->hydrateHistory(collect([$report]));

        return view('admin.reports.show', [
            'report' => $report,
            'categories' => config('matrimony.reports.categories', []),

            // Every other report against the same account, so a moderator sees
            // the pattern rather than being told a number and trusting it.
            'related' => Report::query()
                ->where('reported_user_id', $report->reported_user_id)
                ->whereKeyNot($report->getKey())
                ->with('reporter:id,profile_code,name')
                ->latest('created_at')
                ->limit(10)
                ->get(),
            'actions' => ResolveReport::ACTIONS,
        ]);
    }

    /**
     * One evidence file, behind a short-lived signed URL and an audit row.
     *
     * `AdminReportResource`'s docblock has always promised this — "the panel
     * fetches each one through a signed URL when the moderator opens it" — and
     * nothing implemented it, because the API surface sends only a count (a
     * private-disk path in a JSON body is either useless or a leak). The panel
     * is where that promise lands, and the shape is `VerificationController`'s
     * verbatim: audit first, mint second, redirect rather than embed, so the
     * bytes never pass through this app.
     *
     * The index is a position in `evidence_paths`, not a path — a path in a URL
     * is a request to read any file on the private disk.
     */
    public function evidence(Report $report, int $index, AuditLogger $audit): RedirectResponse
    {
        $this->authorize('view', $report);

        $path = ($report->evidence_paths ?? [])[$index] ?? abort(404);

        $audit->logAccess(
            'report.evidence_viewed',
            $report,
            "Viewed evidence file {$index} on report #{$report->id} against member {$report->reportedUser?->profile_code}",
        );

        return redirect()->away(Storage::disk('private')->temporaryUrl(
            $path,
            now()->addMinutes((int) config('matrimony.media.document.signed_url_ttl', 5)),
        ));
    }

    public function assign(AssignReportRequest $request, Report $report, AssignReport $assign): RedirectResponse
    {
        $this->authorize('assign', $report);

        // Defaults to the caller — picking up the next item in your own queue
        // is the common case and should not require naming yourself.
        $assignee = $request->filled('assignee_id')
            ? User::findOrFail($request->integer('assignee_id'))
            : $request->user();

        $assign($report, $assignee, $request->user());

        return back()->with('status', $assignee->is($request->user())
            ? 'Report assigned to you. It is now in your queue.'
            : "Report assigned to {$assignee->name}.");
    }

    public function resolve(ResolveReportRequest $request, Report $report, ResolveReport $resolve): RedirectResponse
    {
        $this->authorize('resolve', $report);

        $resolve(
            $report,
            $request->user(),
            $request->string('action_taken')->toString(),
            $request->input('note'),
        );

        return redirect()->route('admin.reports.index')->with('status', 'Report resolved and the reporter has been told.');
    }

    public function dismiss(DismissReportRequest $request, Report $report, DismissReport $dismiss): RedirectResponse
    {
        $this->authorize('resolve', $report);

        $dismiss($report, $request->user(), $request->input('note'));

        return redirect()->route('admin.reports.index')->with('status', 'Report dismissed as not actionable.');
    }

    // --- Internals ---------------------------------------------------------

    /**
     * How many times each accused account has been reported, in total — one
     * grouped query per page rather than a correlated subquery per row, so the
     * queue's plan does not depend on the page size. Copied from the API
     * sibling deliberately: two definitions of "reports against this account"
     * would eventually disagree, and this is a number decisions turn on.
     *
     * @param  Collection<int, Report>  $reports
     */
    protected function hydrateHistory(Collection $reports): void
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
