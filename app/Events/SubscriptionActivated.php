<?php

namespace App\Events;

use App\Models\Subscription;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * The entitlement half of a purchase, kept separate from PaymentVerified on
 * purpose: a subscription can also begin without a payment — a comped plan
 * granted by an admin, or a migration from a legacy system — and anything
 * reacting to "this user now has Gold" must fire in those cases too.
 */
class SubscriptionActivated
{
    use Dispatchable, SerializesModels;

    public function __construct(public Subscription $subscription) {}
}
