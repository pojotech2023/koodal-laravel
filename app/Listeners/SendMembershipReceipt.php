<?php

namespace App\Listeners;

use App\Events\PaymentVerified;
use App\Services\Notification\NotificationDispatcher;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

/**
 * The in-app confirmation that a purchase landed.
 *
 * Not the invoice: the PDF and the emailed receipt are GenerateInvoice's job,
 * dispatched from the same transaction. Splitting them means a slow PDF render
 * cannot delay the one message the user is actually staring at the screen
 * waiting for, and the invoice number is left out of this copy for the same
 * reason — it does not exist yet when this runs.
 */
class SendMembershipReceipt implements ShouldQueue
{
    use InteractsWithQueue;

    public int $tries = 3;

    public array $backoff = [10, 60, 300];

    public function __construct(protected NotificationDispatcher $notifications) {}

    public function handle(PaymentVerified $event): void
    {
        $subscription = $event->subscription->loadMissing('user', 'plan');

        if (! $subscription->user || ! $subscription->plan) {
            return;
        }

        $this->notifications->send(
            user: $subscription->user,
            type: 'membership_purchased',
            title: "Your {$subscription->plan->name} membership is active",
            body: $this->body($subscription),
            data: [
                'payment_id' => $event->payment->id,
                'subscription_id' => $subscription->id,
                'plan' => $subscription->plan->name,
            ],
        );
    }

    protected function body(object $subscription): string
    {
        // A lifetime plan has no expiry. Rendering "valid until —" for it is
        // the kind of detail that generates a support ticket.
        return $subscription->expires_at
            ? 'Valid until '.$subscription->expires_at->format('j M Y').'. Your invoice will arrive by email shortly.'
            : 'Your membership does not expire. Your invoice will arrive by email shortly.';
    }
}
