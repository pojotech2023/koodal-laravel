<?php

namespace App\Http\Controllers\Api\V1\User;

use App\Actions\Report\SubmitReport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Report\SubmitReportRequest;
use App\Http\Resources\ReportResource;
use App\Models\User;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    /**
     * The picker on the report sheet. Served from config so the client never
     * hardcodes a list that the validation rule would then disagree with.
     */
    public function categories(): JsonResponse
    {
        $categories = collect(config('matrimony.reports.categories'))
            ->map(fn (string $label, string $value) => ['value' => $value, 'label' => $label])
            ->values()
            ->all();

        return ApiResponse::success($categories, 'Report categories retrieved successfully');
    }

    public function mine(Request $request): JsonResponse
    {
        $reports = $request->user()->reportsMade()
            ->with(['reportedUser:id,name,profile_code'])
            ->latest()
            ->paginate($request->integer('per_page', 20));

        return ApiResponse::paginated($reports, ReportResource::class, 'Your reports retrieved successfully');
    }

    public function store(SubmitReportRequest $request, SubmitReport $submit): JsonResponse
    {
        $report = $submit(
            reporter: $request->user(),
            target: User::findOrFail($request->integer('reported_user_id')),
            category: $request->string('category')->toString(),
            description: $request->input('description'),
            evidence: $request->file('evidence', []),
            messageIds: $request->input('message_ids', []),
            block: $request->boolean('block'),
        );

        return ApiResponse::created(
            new ReportResource($report->load('reportedUser:id,name,profile_code')),
            'Thank you. Our team will review this report.',
        );
    }
}
