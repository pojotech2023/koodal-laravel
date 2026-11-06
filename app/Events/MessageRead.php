<?php

namespace App\Events;

use App\Models\Conversation;
use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Read receipts carry a high-water mark rather than a message id list — one
 * "everything up to 8841 is read" replaces a frame per message, which on a
 * thread opened after a day away is the difference between one packet and two
 * hundred.
 *
 * The privacy check lives in ReverbTransport::publishRead, not here: a user who
 * hid their online status has also opted out of announcing when they read.
 */
class MessageRead implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public string $broadcastQueue;

    public function __construct(
        public Conversation $conversation,
        public User $reader,
        public int $upToMessageId,
    ) {
        $this->broadcastQueue = config('queue.queues.high');
    }

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel('conversation.'.$this->conversation->id);
    }

    public function broadcastAs(): string
    {
        return 'message.read';
    }

    public function broadcastWith(): array
    {
        return [
            'conversation_id' => $this->conversation->id,
            'reader_id' => $this->reader->id,
            'up_to_message_id' => $this->upToMessageId,
            'read_at' => now()->toIso8601String(),
        ];
    }
}
