<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GameRoom extends Model
{
    use HasFactory;

    protected $table = 'game_rooms';

    protected $fillable = [
        'room_code',
        'user_a_id',
        'user_b_id',
        'game_type',
        'status',
        'user_a_ready',
        'user_b_ready',
        'current_question_index',
        'question_ids',
        'user_a_answers',
        'user_b_answers',
        'user_a_score',
        'user_b_score',
        'started_at',
        'ended_at',
    ];

    protected $casts = [
        'user_a_ready'  => 'boolean',
        'user_b_ready'  => 'boolean',
        'question_ids'   => 'array',
        'user_a_answers' => 'array',
        'user_b_answers' => 'array',
        'started_at'     => 'datetime',
        'ended_at'       => 'datetime',
    ];

    public function userA(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_a_id');
    }

    public function userB(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_b_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(GameChatMessage::class, 'room_id');
    }
}
