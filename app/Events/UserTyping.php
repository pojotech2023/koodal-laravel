<?php

namespace App\Events;

use App\Models\Conversation;
use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * The one broadcast that is NOT queued.
 *
 * A typing indicator is only true for the two or three seconds it describes.
 * Behind a busy queue it arrives after the message it was announcing, which is
 * worse than never showing it at all — so it goes out inline and is dropped on
 * failure rather than retried.
 *
 * Never persisted, for the same reason.
 */
class UserTyping implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Conversation $conversation,
        public User $sender,
    ) {}

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel('conversation.'.$this->conversation->id);
    }

    public function broadcastAs(): string
    {
        return 'user.typing';
    }

    public function broadcastWith(): array
    {
        return [
            'conversation_id' => $this->conversation->id,
            'user_id' => $this->sender->id,
            // The client hides the indicator on its own timer. Relying on a
            // "stopped typing" frame leaves the dots spinning forever whenever
            // one goes missing, which on a mobile network it will.
            'expires_in' => config('matrimony.chat.typing_ttl'),
        ];
    }
}
