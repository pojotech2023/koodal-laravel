<?php

namespace App\Http\Controllers\Web\Admin;

use App\Actions\Payment\RefundPayment;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\RefundPaymentRequest;
use App\Jobs\ExportPayments;
use App\Models\MembershipPlan;
use App\Models\Payment;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * The `Web\Admin` sibling of {@see \App\Http\Controllers\Api\V1\Admin\
 * PaymentManagementController} — same filters, same `PaymentPolicy` abilities,
 * same `RefundPayment` Action, same queued `ExportPayments`.
 *
 * Two things carried over verbatim because they are decisions, not details:
 *
 * 1. **No totals on the list.** A sum over one page is revenue that is wrong
 *    by however many rows did not fit, and it would be read as revenue anyway.
 *    The aggregates live in `AnalyticsReports::revenue`, over the whole window,
 *    and the dashboard is where they belong.
 * 2. **Refund is POST, and the route is not idempotent-looking on purpose.**
 *    It moves money at a third party; a PATCH invites a retry, and a retried
 *    refund is a second refund. The Policy carries both business rules — only
 *    a paid payment, never twice — ahead of the permission check, so a super
 *    admin is bound by them too.
 */
class PaymentController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('payments.view');

        $request->validate([
            'status' => ['nullable', 'in:created,attempted,paid,failed,refunded'],
            'plan_id' => ['nullable', 'integer'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'q' => ['nullable', 'string', 'max:120'],
        ]);

        $payments = Payment::query()
            ->whereHas('user')
            ->with(['user:id,profile_code,name,email,mobile', 'plan:id,name,duration_days'])
            ->status($request->string('status')->toString() ?: null)
            ->when($request->integer('plan_id'), fn ($q, $id) => $q->where('membership_plan_id', $id))
            ->when($request->filled('q'), fn ($q) => $this->applySearch($q, trim($request->string('q')->toString())))
            ->when($request->date('from'), fn ($q, $from) => $q->where('created_at', '>=', $from->startOfDay()))
            ->when($request->date('to'), fn ($q, $to) => $q->where('created_at', '<=', $to->endOfDay()))
            ->latest('id')
            ->paginate(25)
            ->withQueryString();

        return view('admin.payments.index', [
            'payments' => $payments,
            'plans' => MembershipPlan::query()->orderBy('sort_order')->get(['id', 'name']),
        ]);
    }

    public function show(Payment $payment): View
    {
        $this->authorize('view', $payment);

        $payment->load([
            'user:id,profile_code,name,email,mobile',
            'plan:id,name,duration_days',
            'subscription:id,status,starts_at,expires_at',
        ]);

        return view('admin.payments.show', ['payment' => $payment]);
    }

    /**
     * Queued, and it answers with a redirect rather than a file.
     *
     * `ExportPayments` writes a CSV to the private disk and notifies the
     * requester (`export_ready`), which `ExportController` then signs on
     * demand. Streaming it from here instead would mean holding a request open
     * across a full-table scan — the reason the job exists.
     */
    public function export(Request $request): RedirectResponse
    {
        $this->authorize('export', Payment::class);

        ExportPayments::dispatch(
            $request->user()->id,
            $request->only(['status', 'plan_id', 'from', 'to']),
        );

        return back()->with('status', 'Export started. You will be notified when the file is ready to download.');
    }

    public function refund(RefundPaymentRequest $request, Payment $payment, RefundPayment $refund): RedirectResponse
    {
        $this->authorize('refund', $payment);

        $refund(
            $payment,
            $request->user(),
            $request->string('reason')->toString(),

            // Absent means the whole payment — the common case, and the one
            // that must not require retyping a figure that could be got wrong.
            $request->filled('amount') ? (float) $request->input('amount') : null,
        );

        return back()->with('status', 'Refund issued. Razorpay will confirm it by webhook shortly — the status here updates then, not now.');
    }

    // --- Internals ---------------------------------------------------------

    /**
     * The list's one free-text box, dispatched on what was typed: an invoice
     * number, a Razorpay id, or a member. The API sibling takes `user_id`
     * instead, which is right for a script and useless to a support agent
     * holding a printed invoice.
     *
     * @param  Builder<Payment>  $query
     */
    protected function applySearch($query, string $term): void
    {
        if (str_starts_with($term, 'order_') || str_starts_with($term, 'pay_')) {
            $query->where('razorpay_order_id', $term)->orWhere('razorpay_payment_id', $term);

            return;
        }

        if (str_contains(strtoupper($term), 'INV')) {
            $query->where('invoice_number', strtoupper($term));

            return;
        }

        $query->whereHas(
            'user',
            fn ($u) => $u->where('profile_code', strtoupper($term))->orWhere('name', 'like', $term.'%'),
        );
    }
}
