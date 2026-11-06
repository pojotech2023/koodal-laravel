<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MatchPlayer extends Model
{
    use HasFactory;

    protected $table = 'match_players';

    protected $fillable = [
        'match_id',
        'user_id',
        'invited_email',
        'status',
        'score',
        'correct_count',
        'wrong_count',
        'last_claim_at',
    ];

    protected $casts = [
        'score' => 'integer',
        'correct_count' => 'integer',
        'wrong_count' => 'integer',
        'last_claim_at' => 'datetime',
    ];

    public function match(): BelongsTo
    {
        return $this->belongsTo(GameMatch::class, 'match_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
