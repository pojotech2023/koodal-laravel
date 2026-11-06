<?php

namespace App\Models;

use App\Models\Concerns\BelongsToUser;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Our own table, not Laravel's polymorphic `notifications`. The badge count,
 * the list and the per-type settings all query by (user, read_at, created_at),
 * which a morph table indexes badly.
 */
class Notification extends Model
{
    use BelongsToUser, HasFactory;

    protected function casts(): array
    {
        return [
            'data' => 'array',
            'read_at' => 'datetime',
            'sent_at' => 'datetime',
            'failed_at' => 'datetime',
        ];
    }

    // --- Accessors ---------------------------------------------------------

    public function isRead(): bool
    {
        return $this->read_at !== null;
    }

    /** The deep link the client's tap handler follows. */
    public function route(): ?string
    {
        return $this->data['route'] ?? config("matrimony.notifications.types.{$this->type}.route");
    }

    // --- Scopes ------------------------------------------------------------

    public function scopeUnread(Builder $query): Builder
    {
        return $query->whereNull('read_at');
    }

    public function scopeOfType(Builder $query, ?string $type): Builder
    {
        return $type ? $query->where('type', $type) : $query;
    }
}
