<?php

namespace App\Actions\Payment;

use App\Events\PaymentVerified;
use App\Events\SubscriptionActivated;
use App\Exceptions\PaymentVerificationException;
use App\Jobs\GenerateInvoice;
use App\Models\Payment;
use App\Models\Subscription;
use App\Services\Membership\FeatureGate;
use App\Services\Payment\InvoiceNumber;
use App\Services\Payment\RazorpayClient;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Turns a Razorpay payment into an active subscription.
 *
 * Called from two places that can arrive in either order and can arrive
 * simultaneously: the client's /payments/verify call, and Razorpay's webhook.
 * Whichever lands first wins; the second is a no-op. That is the whole design.
 *
 * The client's callback is never trusted on its own — it is JavaScript we do
 * not control, running on a device we do not trust. Truth comes from the
 * signature (recomputed with our secret) plus a confirmation fetch against
 * Razorpay's API.
 */
class VerifyRazorpayPayment
{
    public function __construct(
        protected RazorpayClient $razorpay,
        protected FeatureGate $features,
    ) {}

    public function __invoke(string $orderId, string $paymentId, string $signature): Payment
    {
        $payment = Payment::where('razorpay_order_id', $orderId)->firstOrFail();

        // Timing-safe. A `===` here leaks signature bytes through response time.
        if (! $this->razorpay->verifySignature($orderId, $paymentId, $signature)) {
            $payment->update(['status' => 'failed', 'failure_reason' => 'signature_mismatch']);

            Log::channel('security')->warning('Razorpay signature mismatch', [
                'payment_id' => $payment->id,
                'order_id' => $orderId,
            ]);

            throw new PaymentVerificationException('This payment could not be verified.');
        }

        // A valid signature proves the payload came from someone holding the
        // secret. It does not prove the money moved — only Razorpay knows that.
        $remote = $this->razorpay->fetchPayment($paymentId);

        if ($remote['status'] !== 'captured') {
            $payment->update(['status' => 'failed', 'failure_reason' => 'not_captured']);

            throw new PaymentVerificationException('This payment has not been completed.');
        }

        // Amount is authoritative from the plan, never the request. A mismatch
        // means someone tampered with the checkout.
        if ((int) $remote['amount'] !== $this->razorpay->toPaise($payment->amount)) {
            $payment->update(['status' => 'failed', 'failure_reason' => 'amount_mismatch']);

            Log::channel('security')->error('Razorpay amount mismatch', [
                'payment_id' => $payment->id,
                'expected' => $payment->amount,
                'received' => $remote['amount'],
            ]);

            throw new PaymentVerificationException('Payment amount did not match the selected plan.');
        }

        return $this->activate($payment, $paymentId, $signature, $remote['method'] ?? null);
    }

    /**
     * The webhook's entry point — the other of the two paths this class exists
     * to reconcile.
     *
     * There is no client signature to check here, and none is needed: the
     * webhook body was already authenticated with the webhook secret before the
     * controller called us, which is a stronger proof than the checkout
     * callback offers. Everything after that is identical, including the
     * confirmation fetch — a signed body still does not prove the money moved.
     */
    public function fromWebhook(string $orderId, string $paymentId): ?Payment
    {
        $payment = Payment::where('razorpay_order_id', $orderId)->first();

        // An order we never wrote. Razorpay delivering an event for someone
        // else's account is not something we can fix by retrying, so it is
        // swallowed and the controller still answers 200.
        if (! $payment) {
            Log::channel('payments')->warning('Webhook for unknown order', ['order_id' => $orderId]);

            return null;
        }

        if ($payment->status === 'paid') {
            return $payment;      // /payments/verify already won the race
        }

        $remote = $this->razorpay->fetchPayment($paymentId);

        if ($remote['status'] !== 'captured'
            || (int) $remote['amount'] !== $this->razorpay->toPaise($payment->amount)) {
            Log::channel('security')->error('Webhook payment failed confirmation', [
                'payment_id' => $payment->id,
                'remote_status' => $remote['status'] ?? null,
            ]);

            return null;
        }

        return $this->activate($payment, $paymentId, null, $remote['method'] ?? null);
    }

    protected function activate(Payment $payment, string $paymentId, ?string $signature, ?string $method): Payment
    {
        return DB::transaction(function () use ($payment, $paymentId, $signature, $method) {
            // Locked so /payments/verify and the webhook racing each other
            // cannot both create a subscription.
            $payment = Payment::whereKey($payment->id)->lockForUpdate()->first();

            if ($payment->status === 'paid') {
                return $payment;      // the other path won; nothing to do
            }

            $plan = $payment->plan;
            $user = $payment->user;

            $existing = $user->activeSubscription;

            // Upgrades extend rather than refund: remaining days carry over.
            // A prorated refund means a gateway refund on every upgrade and
            // accounting noise, for no benefit the user can perceive.
            $startsAt = $existing && config('matrimony.membership.upgrade_strategy') === 'extend'
                ? $existing->expires_at
                : now();

            $existing?->update(['status' => 'superseded']);

            $subscription = Subscription::create([
                'user_id' => $user->id,
                'membership_plan_id' => $plan->id,
                'payment_id' => $payment->id,
                'starts_at' => $startsAt,
                'expires_at' => $plan->duration_days ? $startsAt->copy()->addDays($plan->duration_days) : null,
                'status' => 'active',
                // Frozen here. An admin editing the plan tomorrow must not
                // change what this person just bought.
                'features_snapshot' => $plan->features,
            ]);

            $payment->update([
                'razorpay_payment_id' => $paymentId,
                'razorpay_signature' => $signature,
                'method' => $method,
                'status' => 'paid',
                'subscription_id' => $subscription->id,
                'verified_at' => now(),
                // Assigned here rather than in GenerateInvoice so the number is
                // in the /payments/verify response the user is staring at, and
                // so the gap-free sequence is claimed under the same lock that
                // makes activation single-shot. The job only renders the PDF.
                'invoice_number' => InvoiceNumber::next(),
            ]);

            $this->features->flush($user);

            GenerateInvoice::dispatch($payment);

            // Two events, not one. PaymentVerified is the receipt trigger and
            // carries the payment; SubscriptionActivated is the entitlement
            // change, which also happens for a comped plan where no money moved.
            // The early return above is what keeps both firing exactly once
            // when /payments/verify and the webhook race.
            event(new PaymentVerified($payment, $subscription));
            event(new SubscriptionActivated($subscription));

            return $payment->fresh();
        });
    }
}
