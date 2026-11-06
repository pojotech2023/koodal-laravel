<?php

namespace App\Services\Chat;

use App\Events\MessageRead;
use App\Events\MessageSent;
use App\Events\UserTyping;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use App\Services\Chat\Contracts\RealtimeTransport;

/**
 * Default transport. Chosen because it keeps everything in one stack and
 * because channel authorisation reuses the Sanctum token the REST API already
 * issues — no second auth system to keep in sync.
 */
class ReverbTransport implements RealtimeTransport
{
    public function publishMessage(Conversation $conversation, Message $message): void
    {
        broadcast(new MessageSent($conversation, $message))->toOthers();
    }

    public function publishTyping(Conversation $conversation, User $sender): void
    {
        // Never persisted — a typing indicator that outlives the typing is
        // worse than none at all.
        broadcast(new UserTyping($conversation, $sender))->toOthers();
    }

    public function publishRead(Conversation $conversation, User $reader, int $upToMessageId): void
    {
        // Receipts honour privacy: a user who hid their online status has also
        // opted out of telling people exactly when they read a message.
        if (! ($reader->privacySettings?->show_online_status ?? true)) {
            return;
        }

        broadcast(new MessageRead($conversation, $reader, $upToMessageId))->toOthers();
    }

    public function publishPresence(User $user, bool $online): void
    {
        // Presence lives in Redis with a TTL; the channel roster does the rest.
        $key = "online:{$user->id}";

        $online
            ? cache()->put($key, true, config('matrimony.chat.presence_ttl'))
            : cache()->forget($key);
    }

    public function clientConfig(User $user): array
    {
        return [
            'transport' => 'reverb',
            'key' => config('reverb.apps.apps.0.key'),
            'host' => config('reverb.apps.apps.0.options.host'),
            'port' => (int) config('reverb.apps.apps.0.options.port'),
            'scheme' => config('reverb.apps.apps.0.options.scheme'),
            'auth_endpoint' => url('/api/v1/broadcasting/auth'),
            'channels' => [
                'user' => "private-user.{$user->id}",
                'presence' => 'presence-online',
            ],
        ];
    }
}
