<?php

namespace App\Events;

use App\Models\Payment;
use App\Models\Subscription;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Money confirmed and entitlement granted, in one event.
 *
 * Raised from inside VerifyRazorpayPayment's transaction, which returns early
 * when the payment is already `paid`. That is what makes it safe against the
 * two paths that produce it: whichever of /payments/verify and the Razorpay
 * webhook arrives second does nothing, so the receipt goes out exactly once.
 *
 * The subscription rides along because every listener needs the plan and the
 * expiry, and looking them up again would re-read a row the producer just
 * wrote.
 */
class PaymentVerified
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Payment $payment,
        public Subscription $subscription,
    ) {}
}
