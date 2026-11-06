<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MatchClaim extends Model
{
    use HasFactory;

    protected $table = 'match_claims';

    protected $fillable = [
        'match_id',
        'user_id',
        'difference_id',
        'x_pct',
        'y_pct',
        'is_correct',
        'points',
    ];

    protected $casts = [
        'x_pct' => 'float',
        'y_pct' => 'float',
        'is_correct' => 'boolean',
        'points' => 'integer',
    ];

    public function match(): BelongsTo
    {
        return $this->belongsTo(GameMatch::class, 'match_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function difference(): BelongsTo
    {
        return $this->belongsTo(PuzzleDifference::class, 'difference_id');
    }
}
