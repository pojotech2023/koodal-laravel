<?php

namespace App\Listeners;

use App\Events\MessageSent;
use App\Models\User;
use App\Services\Notification\NotificationDispatcher;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

/**
 * The push half of a chat message; the socket handles anyone with the thread
 * already open.
 *
 * Runs on `high` — every other notification in the system can afford to sit
 * behind a nightly recompute, but a chat message that arrives four minutes
 * late is a broken product.
 *
 * `collapse_key` is carried in the notification data so several unread
 * messages in one conversation replace each other in the tray rather than
 * stacking into twelve rows.
 */
class SendMessageNotification
{
    use InteractsWithQueue;

    public int $tries = 3;

    public array $backoff = [5, 30, 120];

    public function __construct(protected NotificationDispatcher $notifications) {}

    public function viaQueue(): string
    {
        return config('queue.queues.high');
    }

    public function handle(MessageSent $event): void
    {
        // SerializesModels restores the Message from the database with no
        // relations attached, and preventLazyLoading would throw on ->sender.
        $message = $event->message->loadMissing('sender');
        $recipient = User::find($event->conversation->otherUserId($message->sender_id));

        if (! $recipient) {
            return;
        }

        // A system message has no author to announce. It exists to caption a
        // state change the recipient was already notified about.
        if ($message->type === 'system') {
            return;
        }

        // Skip push if recipient is currently active in this conversation thread
        $isActiveInChat = false;
        try {
            $chatActiveKey = "chat_active_{$event->conversation->id}_{$recipient->id}";
            $isActiveInChat = \Illuminate\Support\Facades\Cache::has($chatActiveKey);
        } catch (\Throwable $e) {
            $isActiveInChat = false;
        }

        if ($isActiveInChat) {
            return;
        }

        $senderName = $message->sender?->name ?? 'New message';
        $preview = $message->preview();

        $this->notifications->send(
            user: $recipient,
            type: 'chat_message',
            title: $senderName,
            body: $preview,
            data: [
                'reference_id' => (string) $event->conversation->id,
                'conversation_id' => $event->conversation->id,
                'message_id' => $message->id,
                'collapse_key' => 'conversation-'.$event->conversation->id,
                'route' => '/chat/'.$event->conversation->id,
            ],
        );

        // The dispatcher above already pushes this (chat_message is not a
        // digest type); a second direct push here doubled every message.
    }
}
