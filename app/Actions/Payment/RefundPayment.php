<?php

namespace App\Actions\Payment;

use App\Models\Payment;
use App\Models\Subscription;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\Membership\FeatureGate;
use App\Services\Payment\RazorpayClient;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * A staff-initiated refund.
 *
 * The gateway call happens **outside** the transaction and **before** the row
 * is touched, which is the opposite of the usual order and is the only correct
 * one here. Money leaving Razorpay is the part that cannot be rolled back: if
 * the local write failed after a successful refund we would have sent money
 * and have no record of it, and the next admin to look at the payment would
 * refund it again. Failing before the gateway call costs nothing.
 *
 * `refund.processed` will arrive at the webhook shortly afterwards and write
 * the same columns. That duplication is deliberate — the webhook is the
 * authority when a refund is issued from the Razorpay dashboard rather than
 * from here — and it is safe because both paths write the same terminal state
 * from the same entity, and `PaymentPolicy::refund` refuses a payment that
 * already carries a `refund_id`.
 */
class RefundPayment
{
    public function __construct(
        protected RazorpayClient $razorpay,
        protected AuditLogger $audit,
        protected FeatureGate $features,
    ) {}

    /**
     * @param  float|null  $amount  rupees; null refunds the full payment.
     */
    public function __invoke(Payment $payment, User $actor, string $reason, ?float $amount = null): Payment
    {
        // Re-checked here and not only in the Policy: this Action is reachable
        // from a console command and from a future bulk tool, and "never refund
        // twice" is a rule about the payment, not about the HTTP request.
        abort_unless($payment->isPaid(), 422, 'Only a paid payment can be refunded.');
        abort_if($payment->refund_id !== null, 409, 'This payment has already been refunded.');
        abort_if($payment->razorpay_payment_id === null, 422, 'This payment was never captured at the gateway.');

        $amount ??= (float) $payment->amount;

        abort_if(
            $amount <= 0 || $amount > (float) $payment->amount,
            422,
            'The refund amount must be between 0 and the amount paid.',
        );

        try {
            $refund = $this->razorpay->refund(
                $payment->razorpay_payment_id,
                $this->razorpay->toPaise($amount),
                ['reason' => $reason, 'refunded_by' => $actor->profile_code],
            );
        } catch (\Throwable $e) {
            Log::channel('payments')->error('Refund failed at the gateway', [
                'payment_id' => $payment->id,
                'razorpay_payment_id' => $payment->razorpay_payment_id,
                'error' => $e->getMessage(),
            ]);

            // 502, not 500: nothing on our side is broken and nothing was
            // written. The honest message is that the gateway refused, and the
            // correct next action is to look at the Razorpay dashboard rather
            // than to retry blindly.
            abort(502, 'The payment gateway refused the refund. Check the Razorpay dashboard before retrying.');
        }

        $isFull = $amount >= (float) $payment->amount;

        $payment = DB::transaction(function () use ($payment, $refund, $reason, $amount, $isFull, $actor) {
            $payment->update([
                // A partial refund leaves the payment `paid` — the money for
                // the rest of it is still ours, and reporting it as `refunded`
                // would understate revenue by the whole transaction.
                'status' => $isFull ? 'refunded' : 'paid',
                'refund_id' => $refund['id'] ?? null,
                'refunded_amount' => $amount,
                'refund_reason' => $reason,
            ]);

            // The entitlement goes with the money, but only when all of it
            // went back. A partial refund is a goodwill gesture, not a
            // cancellation, and revoking the membership over one would turn a
            // support concession into a second complaint.
            if ($isFull && $payment->subscription_id) {
                Subscription::whereKey($payment->subscription_id)->update([
                    'status' => 'cancelled',
                    'cancelled_at' => now(),
                    'cancellation_reason' => 'Payment refunded',
                ]);
            }

            $this->audit->log(
                'payment.refunded',
                $payment,
                newValues: [
                    'refund_id' => $payment->refund_id,
                    'refunded_amount' => $amount,
                    'full_refund' => $isFull,
                ],
                description: $reason,
                actor: $actor,
            );

            return $payment->fresh();
        });

        // Outside the transaction: the resolved feature map is cached for five
        // minutes, and a refunded member keeping premium behaviour for those
        // minutes is the visible half of the refund not happening.
        if ($isFull && $payment->user) {
            $this->features->flush($payment->user);
        }

        return $payment;
    }
}
