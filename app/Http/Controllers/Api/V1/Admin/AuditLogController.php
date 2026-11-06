<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\AuditLogResource;
use App\Models\AuditLog;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Read and filter. There is no store, update or destroy here and there never
 * will be — an audit log an admin can edit is not an audit log, and the only
 * thing that removes rows is the retention sweep in `PruneStaleRecords`.
 *
 * Gated on `settings.audit` rather than `settings.view`: the trail names which
 * moderator suspended whom, which is exactly the information a compromised or
 * disgruntled staff account wants, and it is not needed to read the settings
 * screen.
 */
class AuditLogController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $this->authorize('settings.audit');

        $request->validate([
            'action' => ['nullable', 'string', 'max:80'],
            'user_id' => ['nullable', 'integer'],
            'auditable_type' => ['nullable', 'string', 'max:80'],
            'auditable_id' => ['nullable', 'integer'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $logs = AuditLog::query()
            ->with('user:id,name,email')
            ->action($request->string('action')->toString() ?: null)
            ->when($request->integer('user_id'), fn ($q, $id) => $q->where('user_id', $id))

            // The client filters by short class name — `Payment`, not
            // `App\Models\Payment` — because that is what the resource shows
            // it. Resolving the namespace here keeps the two ends agreeing
            // without leaking the namespace into the query string.
            ->when($request->filled('auditable_type'), fn ($q) => $q->where(
                'auditable_type',
                'App\\Models\\'.class_basename($request->string('auditable_type')->toString()),
            ))
            ->when($request->integer('auditable_id'), fn ($q, $id) => $q->where('auditable_id', $id))
            ->when($request->date('from'), fn ($q, $from) => $q->where('created_at', '>=', $from->startOfDay()))
            ->when($request->date('to'), fn ($q, $to) => $q->where('created_at', '<=', $to->endOfDay()))
            ->latest('id')
            ->paginate($request->integer('per_page', 30));

        return ApiResponse::paginated($logs, AuditLogResource::class, 'Audit log retrieved successfully');
    }
}
