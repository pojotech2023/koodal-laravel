<?php

namespace App\Listeners;

use App\Events\InterestAccepted;
use App\Services\Notification\NotificationDispatcher;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

/**
 * Notifies the person who was waiting — the interest's sender.
 *
 * Only them. The acceptor performed the action and already has the answer in
 * their HTTP response; pushing "you are connected" to someone who just tapped
 * Accept is the notification users cite when they turn notifications off. On
 * the auto-match path (both sides reached out independently) the same rule
 * holds: the one who tapped last gets the 201, the one who has been waiting
 * gets the push.
 */
class SendMatchNotification
{
    use InteractsWithQueue;

    public int $tries = 3;

    public array $backoff = [10, 60, 300];

    public function __construct(protected NotificationDispatcher $notifications) {}

    public function handle(InterestAccepted $event): void
    {
        $interest = $event->interest->loadMissing('sender', 'receiver');

        if (! $interest->sender || ! $interest->receiver) {
            return;
        }

        $this->notifications->send(
            user: $interest->sender,
            type: 'interest_accepted',
            title: "You're connected!",
            body: "{$interest->receiver->name} accepted your interest. You can start a conversation now.",
            data: [
                'interest_id' => $interest->id,
                'conversation_id' => $event->conversation->id,
                'profile_code' => $interest->receiver->profile_code,
            ],
        );
    }
}
