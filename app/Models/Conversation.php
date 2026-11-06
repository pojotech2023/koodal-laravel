<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * The denormalised columns (preview, unread counts, last_message_at) exist so
 * the conversation list — the most-polled screen in the app — is a single
 * indexed read with no joins and no subqueries. They are maintained inside the
 * same transaction as the message insert.
 */
class Conversation extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'last_message_at' => 'datetime',
            'user_one_unread_count' => 'integer',
            'user_two_unread_count' => 'integer',
            'messages_count' => 'integer',
        ];
    }

    // --- Relations ---------------------------------------------------------

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    public function userOne(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_one_id');
    }

    public function userTwo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_two_id');
    }

    public function matchRecord(): BelongsTo
    {
        return $this->belongsTo(MatchRecord::class, 'match_id');
    }

    // --- Accessors ---------------------------------------------------------

    public function includes(int $userId): bool
    {
        return $this->user_one_id === $userId || $this->user_two_id === $userId;
    }

    public function otherUserId(int $userId): int
    {
        return $this->user_one_id === $userId ? $this->user_two_id : $this->user_one_id;
    }

    /** Which denormalised counter belongs to this participant. */
    public function unreadColumnFor(int $userId): string
    {
        return $this->user_one_id === $userId ? 'user_one_unread_count' : 'user_two_unread_count';
    }

    public function unreadCountFor(int $userId): int
    {
        return (int) $this->{$this->unreadColumnFor($userId)};
    }

    // --- Scopes ------------------------------------------------------------

    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where(fn (Builder $q) => $q
            ->where('user_one_id', $userId)
            ->orWhere('user_two_id', $userId));
    }
}
