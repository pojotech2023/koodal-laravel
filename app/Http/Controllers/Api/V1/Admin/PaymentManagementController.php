<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Actions\Payment\RefundPayment;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\RefundPaymentRequest;
use App\Http\Resources\Admin\AdminPaymentResource;
use App\Jobs\ExportPayments;
use App\Models\Payment;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymentManagementController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->authorize('payments.view');

        $request->validate([
            'status' => ['nullable', 'in:created,attempted,paid,failed,refunded'],
            'plan_id' => ['nullable', 'integer'],
            'user_id' => ['nullable', 'integer'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $payments = Payment::query()
            ->whereHas('user')
            ->with([
                'user:id,profile_code,name,email,mobile',
                'plan:id,name,duration_days',
            ])
            ->status($request->string('status')->toString() ?: null)
            ->when($request->integer('plan_id'), fn ($q, $id) => $q->where('membership_plan_id', $id))
            ->when($request->integer('user_id'), fn ($q, $id) => $q->where('user_id', $id))
            ->when($request->date('from'), fn ($q, $from) => $q->where('created_at', '>=', $from->startOfDay()))
            ->when($request->date('to'), fn ($q, $to) => $q->where('created_at', '<=', $to->endOfDay()))
            ->latest('id')
            ->paginate($request->integer('per_page', 25));

        // No totals in this payload, deliberately. A sum over one page is
        // revenue that is wrong by however many rows did not fit, and it would
        // be read as revenue anyway — the aggregates live in
        // `AnalyticsReports::revenue`, over the whole window.
        return ApiResponse::paginated($payments, AdminPaymentResource::class, 'Payments retrieved successfully');
    }

    public function export(Request $request): JsonResponse
    {
        $this->authorize('export', Payment::class);

        ExportPayments::dispatch(
            $request->user()->id,
            $request->only(['status', 'plan_id', 'user_id', 'from', 'to']),
        );

        return ApiResponse::success(
            ['queued' => true],
            'Export started. You will be notified when the file is ready.',
            202,
        );
    }

    public function show(Payment $payment): JsonResponse
    {
        $this->authorize('view', $payment);

        $payment->load(['user:id,profile_code,name,email,mobile', 'plan:id,name,duration_days']);

        return ApiResponse::success(new AdminPaymentResource($payment), 'Payment retrieved successfully');
    }

    /**
     * POST rather than PATCH, and that is not pedantry: this moves money at a
     * third party. A PATCH invites a client library to retry it on a timeout,
     * and a retried refund is a second refund.
     *
     * The Policy carries both business rules — only a paid payment, and never
     * twice — ahead of the permission check, so a super admin is bound by them
     * too.
     */
    public function refund(RefundPaymentRequest $request, Payment $payment, RefundPayment $refund): JsonResponse
    {
        $this->authorize('refund', $payment);

        $payment = $refund(
            $payment,
            $request->user(),
            $request->string('reason')->toString(),
            $request->filled('amount') ? (float) $request->input('amount') : null,
        );

        return ApiResponse::success(
            new AdminPaymentResource($payment->load('user:id,profile_code,name', 'plan:id,name')),
            'Refund issued. Razorpay will confirm it by webhook shortly.',
        );
    }
}
