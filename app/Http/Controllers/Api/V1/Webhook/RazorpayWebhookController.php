<?php

namespace App\Http\Controllers\Api\V1\Webhook;

use App\Actions\Payment\VerifyRazorpayPayment;
use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Subscription;
use App\Services\Membership\FeatureGate;
use App\Services\Payment\RazorpayClient;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * The authoritative payment path.
 *
 * A user who force-quits the app between paying and the checkout callback has
 * still paid, and this is where they get what they bought. It is not a backup
 * for /payments/verify — it is the other half of a pair where either one may
 * arrive first and the second is a no-op.
 *
 * Two rules govern everything below.
 *
 * **Authenticity is the HMAC, and nothing else.** No auth middleware, no CSRF,
 * no IP allow-list. The signature is computed over the RAW body, so the body
 * must be hashed before any decoding — `$request->all()` has already lost the
 * exact bytes Razorpay signed, and a re-encoded payload will never match.
 *
 * **Once the signature is valid, the answer is 200.** For a duplicate, for an
 * event we do not handle, for an order we have never heard of. Razorpay retries
 * a non-2xx for hours, and a webhook endpoint that answers 500 to a payload it
 * will never successfully process is a self-inflicted denial of service.
 */
class RazorpayWebhookController extends Controller
{
    public function __construct(
        protected RazorpayClient $razorpay,
        protected VerifyRazorpayPayment $verify,
        protected FeatureGate $features,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $signature = (string) $request->header('X-Razorpay-Signature');

        if (! $this->razorpay->verifyWebhookSignature($request->getContent(), $signature)) {
            Log::channel('security')->warning('Razorpay webhook signature mismatch', [
                'ip' => $request->ip(),
                'event' => $request->input('event'),
            ]);

            // The one case that is NOT 200. An unsigned caller is not Razorpay,
            // and there is nothing to retry.
            return ApiResponse::error('Invalid signature.', [], 401);
        }

        $event = (string) $request->input('event');

        Log::channel('payments')->info('Razorpay webhook received', [
            'event' => $event,
            'razorpay_event_id' => $request->header('X-Razorpay-Event-Id'),
        ]);

        match ($event) {
            'payment.captured', 'order.paid' => $this->handleCaptured($request),
            'payment.failed' => $this->handleFailed($request),
            'refund.processed' => $this->handleRefunded($request),
            default => null,      // acknowledged and ignored
        };

        return ApiResponse::success(null, 'Webhook processed');
    }

    // --- Handlers ----------------------------------------------------------

    protected function handleCaptured(Request $request): void
    {
        $entity = $this->paymentEntity($request);

        if (! isset($entity['order_id'], $entity['id'])) {
            return;
        }

        try {
            $this->verify->fromWebhook($entity['order_id'], $entity['id']);
        } catch (\Throwable $e) {
            // Swallowed on purpose, and loudly. A throw here becomes a 500,
            // which makes Razorpay retry — and if the failure is deterministic
            // (a plan row deleted, say) it retries for hours. The log is the
            // alert; reconciliation is manual and rare.
            Log::channel('payments')->error('Webhook activation failed', [
                'order_id' => $entity['order_id'],
                'error' => $e->getMessage(),
            ]);
        }
    }

    protected function handleFailed(Request $request): void
    {
        $entity = $this->paymentEntity($request);

        if (! isset($entity['order_id'])) {
            return;
        }

        // Only an unpaid row is touched. A `failed` event arriving after a
        // successful retry on the same order must not undo the subscription.
        Payment::where('razorpay_order_id', $entity['order_id'])
            ->where('status', '!=', 'paid')
            ->update([
                'status' => 'failed',
                'failure_reason' => substr((string) ($entity['error_description'] ?? 'gateway_failure'), 0, 255),
            ]);
    }

    protected function handleRefunded(Request $request): void
    {
        $entity = $request->input('payload.refund.entity', []);

        if (! isset($entity['payment_id'])) {
            return;
        }

        $payment = Payment::with('user')->where('razorpay_payment_id', $entity['payment_id'])->first();

        if (! $payment) {
            return;
        }

        $payment->update([
            'status' => 'refunded',
            'refund_id' => $entity['id'] ?? null,
            // Back to rupees: the entity speaks paise, our column does not.
            'refunded_amount' => isset($entity['amount']) ? $entity['amount'] / 100 : $payment->amount,
        ]);

        // The entitlement goes with the money. Leaving the subscription active
        // after a refund is a free membership granted by the refund process.
        //
        // Updated by key rather than through the relation: this runs outside a
        // request that eager-loaded anything, and preventLazyLoading would
        // throw on `$payment->subscription` in every non-production environment.
        if ($payment->subscription_id) {
            Subscription::whereKey($payment->subscription_id)->update([
                'status' => 'cancelled',
                'cancelled_at' => now(),
                'cancellation_reason' => 'Payment refunded',
            ]);
        }

        // The resolved feature map is cached for five minutes. Without this the
        // refunded member keeps premium behaviour until it happens to expire.
        if ($payment->user) {
            $this->features->flush($payment->user);
        }
    }

    /** @return array<string, mixed> */
    protected function paymentEntity(Request $request): array
    {
        return (array) $request->input('payload.payment.entity', []);
    }
}
