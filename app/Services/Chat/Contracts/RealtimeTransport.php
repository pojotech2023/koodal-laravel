<?php

namespace App\Services\Chat\Contracts;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;

/**
 * The seam that keeps chat from being welded to one provider.
 *
 * The brief allows WebSocket *or* Firebase, so neither may leak past this
 * interface. Controllers and Actions depend on the contract; the concrete
 * class is bound from config('matrimony.chat.transport').
 *
 * Note what is NOT here: reading messages. History always comes from MySQL via
 * the REST API. The transport is a delivery optimisation — if it disappears
 * entirely, chat degrades to polling and nothing is lost. That also keeps
 * admin moderation possible, which a Firebase-first design would not.
 */
interface RealtimeTransport
{
    public function publishMessage(Conversation $conversation, Message $message): void;

    public function publishTyping(Conversation $conversation, User $sender): void;

    public function publishRead(Conversation $conversation, User $reader, int $upToMessageId): void;

    public function publishPresence(User $user, bool $online): void;

    /**
     * Connection details for the client, returned by GET /chat/config.
     *
     * This is what makes one app binary work against either backend: the client
     * asks the server which transport to use and gets the matching credentials,
     * rather than compiling in a choice.
     */
    public function clientConfig(User $user): array;
}
