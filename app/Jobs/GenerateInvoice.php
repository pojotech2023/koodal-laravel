<?php

namespace App\Jobs;

use App\Models\Payment;
use App\Models\Setting;
use App\Services\Payment\InvoiceNumber;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

/**
 * Renders the invoice PDF for a verified payment.
 *
 * The invoice *number* is not assigned here — VerifyRazorpayPayment claims it
 * inside the activation transaction, under the same lock that makes activation
 * single-shot, so the sequence stays gap-free even if this job never runs. This
 * job only draws the document. That split is deliberate: a PDF renderer is the
 * most failure-prone thing in the payment path, and the one part of it that an
 * accountant checks must not depend on dompdf being happy.
 *
 * Private disk, always. An invoice carries a name, an amount and an address.
 * `PaymentController::invoice` mints a short-lived signed URL for it.
 *
 * Idempotent: a retry after a partial failure overwrites the same path rather
 * than producing INV-2026-000318 twice under different names.
 */
class GenerateInvoice implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public array $backoff = [30, 120, 600];

    public function __construct(public Payment $payment)
    {
        // `reports` rather than `default`: a PDF render is measured in seconds
        // and must not sit in front of an interest notification.
        $this->onQueue(config('queue.queues.reports'));
    }

    public function handle(): void
    {
        $payment = Payment::with(['user.profile', 'plan', 'subscription'])->find($this->payment->id);

        if (! $payment || ! $payment->isPaid()) {
            return;
        }

        // Belt and braces. The number is normally already there; if an older
        // row somehow reaches here without one, claiming it now is better than
        // rendering an invoice with a blank identifier.
        if (! $payment->invoice_number) {
            $payment->update(['invoice_number' => InvoiceNumber::next()]);
        }

        $path = "invoices/{$payment->user_id}/{$payment->invoice_number}.pdf";

        Storage::disk('private')->put($path, $this->render($payment));

        $payment->update(['invoice_path' => $path]);
    }

    protected function render(Payment $payment): string
    {
        return Pdf::loadView('invoices.membership', [
            'payment' => $payment,
            'user' => $payment->user,
            'plan' => $payment->plan,
            'subscription' => $payment->subscription,
            // GST and the company block come from settings so finance can
            // correct an address without a deploy.
            'seller' => [
                'name' => Setting::get('company_name', config('app.name')),
                'address' => Setting::get('company_address'),
                'gstin' => Setting::get('company_gstin'),
                'email' => Setting::get('support_email'),
            ],
        ])->setPaper('a4')->output();
    }
}
