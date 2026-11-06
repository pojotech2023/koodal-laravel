<?php

namespace App\Jobs;

use App\Models\Notification;
use App\Services\Notification\PushSender;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Delivers one already-written notification to FCM.
 *
 * The record is created first and always — see NotificationDispatcher. This
 * job is delivery, and delivery is allowed to fail: a user whose phone was off
 * still sees the interest when they open the app, because the in-app list does
 * not depend on Google having been reachable.
 *
 * Only the id is carried, never the model. A serialised Notification is a
 * snapshot of a row the user may have read, or deleted, in the seconds the
 * queue was behind.
 */
class SendPushNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    /** Short and widening: a push that lands ten minutes late is barely a push. */
    public array $backoff = [10, 60, 300];

    public function __construct(public int $notificationId)
    {
        $this->onQueue(config('queue.queues.high'));
    }

    public function handle(PushSender $push): void
    {
        $notification = Notification::with('user')->find($this->notificationId);

        // Deleted, or its account was. Retrying would never help.
        if (! $notification || ! $notification->user) {
            return;
        }

        // A retry after a partial failure, or a duplicate dispatch. Sending
        // again would buzz the devices that already received it.
        if ($notification->sent_at) {
            return;
        }

        // Read before the push escaped the queue — usually because they were in
        // the app the whole time. Pushing now is noise about something they
        // have already dealt with.
        if ($notification->isRead()) {
            return;
        }

        $result = $push->sendToUser(
            $notification->user,
            $notification->title,
            (string) $notification->body,
            $this->payload($notification),
        );

        // No tokens is not a failure: the user is web-only, or logged out
        // everywhere. Recording it as failed would fill the failure column with
        // a condition nobody can act on.
        if ($result['sent'] === 0 && $result['failed'] > 0) {
            $notification->update([
                'failed_at' => now(),
                'failure_reason' => 'fcm_delivery_failed',
            ]);

            return;
        }

        $notification->update(['sent_at' => now(), 'channel' => 'push']);
    }

    /**
     * What the client's tap handler reads. `route` is the whole point — a new
     * notification type becomes deep-linkable without a mobile release.
     *
     * @return array<string, mixed>
     */
    protected function payload(Notification $notification): array
    {
        return array_filter([
            'type' => $notification->type,
            'route' => $notification->route(),
            'notification_id' => $notification->id,
        ] + (array) $notification->data, fn ($v) => $v !== null);
    }

    /**
     * Reached only after the last retry. The record stays exactly where it is,
     * unread and visible in the app — which is the behaviour the notifications
     * spec asks for when FCM is unreachable.
     */
    public function failed(\Throwable $e): void
    {
        Notification::whereKey($this->notificationId)->update([
            'failed_at' => now(),
            'failure_reason' => substr($e->getMessage(), 0, 255),
        ]);
    }
}
