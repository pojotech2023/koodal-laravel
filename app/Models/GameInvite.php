<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GameInvite extends Model
{
    use HasFactory;

    protected $table = 'game_invites';

    protected $fillable = [
        'sender_id',
        'receiver_id',
        'game_type',
        'room_code',
        'status',
        'reminders_sent',
        'last_reminder_at',
        'expires_at',
    ];

    /**
     * Sync sent to be answered one at a time. The website stores it as a game
     * type of its own; it is still Sync — same questions, same result.
     */
    public const SYNC_OFFLINE = 'sync_offline';

    protected $casts = [
        'last_reminder_at' => 'datetime',
        'expires_at'       => 'datetime',
    ];

    /** The game this invite is for (`sync` for an offline Sync invite). */
    public function baseGameType(): string
    {
        return $this->game_type === self::SYNC_OFFLINE ? 'sync' : $this->game_type;
    }

    public function isOfflineSync(): bool
    {
        return $this->game_type === self::SYNC_OFFLINE;
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'receiver_id');
    }
}
