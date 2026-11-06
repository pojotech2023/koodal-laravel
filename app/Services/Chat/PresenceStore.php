<?php

namespace App\Services\Chat;

use App\Models\User;
use Illuminate\Support\Facades\Redis;

/**
 * Presence and typing state.
 *
 * Redis with a TTL rather than a database column, for one reason: presence is
 * wrong by default. A process that crashes mid-session leaves a stale "online"
 * flag forever in MySQL, whereas a key with a 60-second TTL corrects itself
 * without anyone writing a reaper.
 */
class PresenceStore
{
    protected const ONLINE_KEY = 'presence:online:';

    protected const TYPING_KEY = 'presence:typing:';

    protected function connection()
    {
        return Redis::connection('presence');
    }

    public function markOnline(User $user): void
    {
        $this->connection()->setex(
            static::ONLINE_KEY.$user->id,
            config('matrimony.chat.presence_ttl'),
            (string) now()->timestamp,
        );
    }

    public function markOffline(User $user): void
    {
        $this->connection()->del(static::ONLINE_KEY.$user->id);
    }

    /**
     * Honours the privacy setting at read time, not write time. A user who
     * turns "show online status" off must disappear immediately, not once
     * their existing key expires.
     */
    public function isOnline(User $user): bool
    {
        if (! ($user->privacySettings?->show_online_status ?? true)) {
            return false;
        }

        return (bool) $this->connection()->exists(static::ONLINE_KEY.$user->id);
    }

    /** @return array<int, bool> keyed by user id */
    public function onlineMap(array $userIds): array
    {
        if ($userIds === []) {
            return [];
        }

        $keys = array_map(fn (int $id) => static::ONLINE_KEY.$id, $userIds);
        $values = $this->connection()->mget($keys);

        return array_combine($userIds, array_map(fn ($v) => $v !== null, $values));
    }

    public function startTyping(int $conversationId, int $userId): void
    {
        $this->connection()->setex(
            static::TYPING_KEY.$conversationId.':'.$userId,
            config('matrimony.chat.typing_throttle') * 2,
            '1',
        );
    }

    public function isTyping(int $conversationId, int $userId): bool
    {
        return (bool) $this->connection()->exists(static::TYPING_KEY.$conversationId.':'.$userId);
    }

    /** Last-seen timestamp for the "active 5 minutes ago" line. */
    public function lastSeen(User $user): ?int
    {
        $value = $this->connection()->get(static::ONLINE_KEY.$user->id);

        return $value !== null ? (int) $value : $user->last_active_at?->timestamp;
    }
}
