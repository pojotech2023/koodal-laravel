<?php

namespace App\Services\Chat;

use App\Events\MessageSent;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use App\Services\Chat\Contracts\RealtimeTransport;
use Kreait\Firebase\Contract\Auth as FirebaseAuth;
use Kreait\Firebase\Contract\Database;

/**
 * Firebase alternative.
 *
 * Writes a SLIM payload — id, sender, preview, timestamp — not the message
 * body. RTDB is used as a notification bus, and clients still fetch content
 * from our API. That keeps exactly one copy of message content, keeps admin
 * moderation working, and avoids paying RTDB egress for chat history.
 */
class FirebaseTransport implements RealtimeTransport
{
    public function __construct(
        protected Database $database,
        protected FirebaseAuth $auth,
    ) {}

    public function publishMessage(Conversation $conversation, Message $message): void
    {
        $this->database
            ->getReference("conversations/{$conversation->id}/messages/{$message->id}")
            ->set([
                'id' => $message->id,
                'sender_id' => $message->sender_id,
                'type' => $message->type,
                'preview' => $message->preview(),
                'created_at' => $message->created_at->timestamp,
            ]);

        // Raised explicitly here because ReverbTransport gets it for free —
        // `broadcast()` dispatches the event on destruct, which is what runs
        // SendMessageNotification. Without this line the push half of chat
        // silently stops working the moment CHAT_TRANSPORT flips to firebase,
        // and nothing in the send path would look wrong.
        //
        // MessageSent is a ShouldBroadcast event, so choosing this transport
        // means BROADCAST_CONNECTION must be `null` — otherwise every message
        // goes out over RTDB *and* the configured broadcaster.
        event(new MessageSent($conversation, $message));
    }

    public function publishTyping(Conversation $conversation, User $sender): void
    {
        $this->database
            ->getReference("conversations/{$conversation->id}/typing/{$sender->id}")
            ->set(['at' => now()->timestamp]);
    }

    public function publishRead(Conversation $conversation, User $reader, int $upToMessageId): void
    {
        if (! ($reader->privacySettings?->show_online_status ?? true)) {
            return;
        }

        $this->database
            ->getReference("conversations/{$conversation->id}/read/{$reader->id}")
            ->set(['up_to' => $upToMessageId, 'at' => now()->timestamp]);
    }

    public function publishPresence(User $user, bool $online): void
    {
        $this->database
            ->getReference("presence/{$user->id}")
            ->set(['online' => $online, 'at' => now()->timestamp]);
    }

    public function clientConfig(User $user): array
    {
        // Scoped custom token — security rules restrict it to this user's
        // conversations. The client never receives the service account.
        $token = $this->auth->createCustomToken((string) $user->id, ['matrimony_user' => true]);

        return [
            'transport' => 'firebase',
            'database_url' => config('services.firebase.database_url'),
            'custom_token' => $token->toString(),
            'paths' => [
                'conversations' => 'conversations',
                'presence' => 'presence',
            ],
        ];
    }
}
