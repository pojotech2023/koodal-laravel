<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KoodalVibeResult extends Model
{
    use HasFactory;

    protected $table = 'koodal_vibe_results';

    protected $fillable = [
        'user_one_id',
        'user_two_id',
        'vibe_percentage',
        'common_tags',
        'games_summary',
    ];

    protected $casts = [
        'common_tags' => 'array',
        'games_summary' => 'array',
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
