<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FlipGame extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'user_one_id',
        'user_two_id',
        'board',
        'matched',
        'messages',
        'p1_token',
        'p2_token',
        'p1_name',
        'p2_name',
        'p1_ready',
        'p2_ready',
        'p1_score',
        'p2_score',
        'turn',
        'first_flip',
        'second_flip',
        'mismatch_until',
        'preview_used',
        'preview_until',
        'status',
        'last_move_at',
    ];

    protected $casts = [
        'board' => 'array',
        'matched' => 'array',
        'messages' => 'array',
        'p1_ready' => 'boolean',
        'p2_ready' => 'boolean',
        'preview_used' => 'boolean',
        'mismatch_until' => 'datetime',
        'preview_until' => 'datetime',
        'last_move_at' => 'datetime',
        'p1_score' => 'integer',
        'p2_score' => 'integer',
        'turn' => 'integer',
        'first_flip' => 'integer',
        'second_flip' => 'integer',
    ];

    public function playerNumber(?string $token): ?int
    {
        if ($token) {
            if ($token === $this->p1_token) {
                return 1;
            }

            if ($token === $this->p2_token) {
                return 2;
            }
        }

        $userId = auth()->guard('web')->id();
        if ($userId) {
            if ($this->user_one_id && (int) $this->user_one_id === (int) $userId) {
                return 1;
            }
            if ($this->user_two_id && (int) $this->user_two_id === (int) $userId) {
                return 2;
            }
        }

        return null;
    }

    public function isFinished(): bool
    {
        return $this->status === 'finished';
    }

    public function isWaiting(): bool
    {
        return $this->status === 'waiting';
    }

    public function isPlaying(): bool
    {
        return $this->status === 'playing';
    }

    public function isAbandoned(): bool
    {
        return $this->status === 'abandoned';
    }

    public function userOne(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'user_one_id');
    }

    public function userTwo(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'user_two_id');
    }
}
