<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MemorySnapGame extends Model
{
    use HasFactory;

    protected $table = 'memory_snap_games';

    protected $fillable = [
        'code',
        'user_one_id',
        'user_two_id',
        'user_one_name',
        'user_two_name',
        'user_one_avatar',
        'user_two_avatar',
        'status',
        'image_started_at',
        'user_one_joined',
        'user_two_joined',
        'user_one_ready',
        'user_two_ready',
        'challenge_data',
        'questions_data',
        'current_question_index',
        'user_one_answers',
        'user_two_answers',
        'user_one_score',
        'user_two_score',
        'chat_messages',
        'celebration_event',
        'next_game_url',
        'disconnect_info',
    ];

    protected $casts = [
        'user_one_joined' => 'boolean',
        'user_two_joined' => 'boolean',
        'user_one_ready' => 'boolean',
        'user_two_ready' => 'boolean',
        'challenge_data' => 'array',
        'questions_data' => 'array',
        'user_one_answers' => 'array',
        'user_two_answers' => 'array',
        'chat_messages' => 'array',
        'celebration_event' => 'array',
        'disconnect_info' => 'array',
    ];

    public function userOne()
    {
        return $this->belongsTo(User::class, 'user_one_id');
    }

    public function userTwo()
    {
        return $this->belongsTo(User::class, 'user_two_id');
    }
}
