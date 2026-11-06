<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * The `Web\Admin` sibling of {@see \App\Http\Controllers\Api\V1\Admin\
 * AuditLogController}. Read and filter — there is no store, update or destroy
 * here and there never will be. An audit log an admin can edit is not an audit
 * log; the only thing that removes rows is the retention sweep in
 * `PruneStaleRecords`.
 *
 * Gated on `settings.audit`, not `settings.view`: the trail names which
 * moderator suspended whom, which is exactly what a compromised or disgruntled
 * staff account wants, and it is not needed to read the settings screen. Only
 * Super Admin holds it in `RolePermissionSeeder`.
 */
class AuditLogController extends Controller
{
    public function __invoke(Request $request): View
    {
        $this->authorize('settings.audit');

        $request->validate([
            'action' => ['nullable', 'string', 'max:80'],
            'user_id' => ['nullable', 'integer'],
            'auditable_type' => ['nullable', 'string', 'max:80'],
            'auditable_id' => ['nullable', 'integer'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ]);

        $logs = AuditLog::query()
            ->with('user:id,name,email')
            ->action($request->string('action')->toString() ?: null)
            ->when($request->integer('user_id'), fn ($q, $id) => $q->where('user_id', $id))

            // Filtered by short class name — `Payment`, not
            // `App\Models\Payment` — because that is what the table shows.
            // Resolving the namespace here keeps both ends agreeing without
            // putting it in the query string.
            ->when($request->filled('auditable_type'), fn ($q) => $q->where(
                'auditable_type',
                'App\\Models\\'.class_basename($request->string('auditable_type')->toString()),
            ))
            ->when($request->integer('auditable_id'), fn ($q, $id) => $q->where('auditable_id', $id))
            ->when($request->date('from'), fn ($q, $from) => $q->where('created_at', '>=', $from->startOfDay()))
            ->when($request->date('to'), fn ($q, $to) => $q->where('created_at', '<=', $to->endOfDay()))
            ->latest('id')
            ->paginate(30)
            ->withQueryString();

        return view('admin.audit.index', [
            'logs' => $logs,

            // The action vocabulary as it actually exists in the table, not a
            // hardcoded list: actions are strings written by whichever Action
            // logged them (`profile.approved`, `plan.updated`, …), and a
            // hand-maintained picker would go stale the first time a module
            // adds one. Distinct over an indexed column, capped.
            'actions' => AuditLog::query()
                ->select('action')
                ->distinct()
                ->orderBy('action')
                ->limit(200)
                ->pluck('action'),
        ]);
    }
}
