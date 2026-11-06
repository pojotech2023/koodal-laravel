<?php

namespace App\Listeners;

use App\Events\InterestDeclined;
use App\Services\Notification\NotificationDispatcher;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

/**
 * Tells the sender their interest was answered — deliberately without naming
 * the person who declined and without a reason.
 *
 * "Ananya S declined you" invites a second attempt aimed at a specific person;
 * "your interest was declined" closes the loop and points at the list, where
 * the row already carries the name if they want it. The cooldown on
 * Interest::canResend() is the hard limit behind the soft wording.
 */
class SendDeclineNotification
{
    use InteractsWithQueue;

    public int $tries = 3;

    public array $backoff = [10, 60, 300];

    public function __construct(protected NotificationDispatcher $notifications) {}

    public function handle(InterestDeclined $event): void
    {
        $interest = $event->interest->loadMissing('sender');

        if (! $interest->sender) {
            return;
        }

        $this->notifications->send(
            user: $interest->sender,
            type: 'interest_declined',
            title: 'Interest update',
            body: 'One of the interests you sent was not accepted. Keep exploring — there are plenty more matches for you.',
            data: ['interest_id' => $interest->id],
        );
    }
}
