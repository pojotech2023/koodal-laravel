<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KoodalGameScore extends Model
{
    use HasFactory;

    protected $table = 'koodal_game_scores';

    protected $fillable = [
        'user_id',
        'partner_id',
        'game_type',
        'score',
        'max_score',
        'details',
    ];

    protected $casts = [
        'details' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function partner()
    {
        return $this->belongsTo(User::class, 'partner_id');
    }
}
