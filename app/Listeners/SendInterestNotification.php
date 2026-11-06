<?php

namespace App\Listeners;

use App\Events\InterestSent;
use App\Services\Notification\NotificationDispatcher;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Str;

/**
 * Queued, so the sender's HTTP response never waits on a notification write —
 * and so a failure here can be retried without the interest itself being at
 * risk. The interest is already committed by the time this runs.
 */
class SendInterestNotification
{
    use InteractsWithQueue;

    public int $tries = 3;

    public array $backoff = [10, 60, 300];

    public function __construct(protected NotificationDispatcher $notifications) {}

    public function handle(InterestSent $event): void
    {
        // loadMissing rather than load: SerializesModels restores the Interest
        // from the database, so nothing is eager-loaded when the job wakes up.
        $interest = $event->interest->loadMissing('sender', 'receiver');

        if (! $interest->sender || ! $interest->receiver) {
            return;     // account deleted between the send and this job
        }

        $this->notifications->send(
            user: $interest->receiver,
            type: 'interest_received',
            title: 'New interest received',
            body: $this->body($interest->sender->name, $interest->message),
            data: [
                'interest_id' => $interest->id,
                // The client routes on `route` and deep-links to the profile
                // with this — it never has to guess a URL from the copy.
                'profile_code' => $interest->sender->profile_code,
            ],
        );
    }

    /**
     * The personal note, when there is one, is what makes this worth opening.
     * Truncated because a notification tray gives us roughly one line and a
     * clipped sentence mid-word reads as a bug.
     */
    protected function body(string $name, ?string $message): string
    {
        $opening = "{$name} has expressed interest in your profile";

        return $message
            ? $opening.': "'.Str::limit($message, 70).'"'
            : $opening.'.';
    }
}
