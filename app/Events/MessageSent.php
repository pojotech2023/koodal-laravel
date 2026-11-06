<?php

namespace App\Events;

use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * The one event that is both broadcast and listened to: the socket delivers it
 * to a device that has the thread open, and SendMessageNotification pushes to
 * one that does not.
 *
 * Queued rather than ShouldBroadcastNow, on `high`. Broadcasting inline would
 * put the WebSocket server on the critical path of the send request — a slow
 * Reverb node would then slow down message *persistence*, which is the one
 * part of chat that must never be at risk.
 */
class MessageSent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /** Set here rather than as a literal so the queue names stay in config. */
    public string $broadcastQueue;

    public function __construct(
        public Conversation $conversation,
        public Message $message,
    ) {
        $this->broadcastQueue = config('queue.queues.high');
    }

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel('conversation.'.$this->conversation->id);
    }

    /** Named, so the client binds to `message.sent` instead of a PHP FQCN. */
    public function broadcastAs(): string
    {
        return 'message.sent';
    }

    /**
     * Deliberately the minimum needed to render the bubble optimistically.
     *
     * No attachment URL: chat media lives on a private disk behind short-lived
     * signed URLs, and a URL minted at broadcast time would expire in the
     * socket buffer of a backgrounded app. Image messages carry enough here to
     * draw the placeholder; the client resolves the URL through the REST
     * resource, which signs it at read time.
     */
    public function broadcastWith(): array
    {
        return [
            'id' => $this->message->id,
            'conversation_id' => $this->conversation->id,
            'sender_id' => $this->message->sender_id,
            'type' => $this->message->type,
            'body' => $this->message->body,
            'attachment_meta' => $this->message->attachment_meta,
            // Echoed back so the sender's own optimistic bubble is replaced
            // rather than duplicated when the frame arrives on a second device.
            'client_message_id' => $this->message->client_message_id,
            'created_at' => $this->message->created_at?->toIso8601String(),
        ];
    }
}
