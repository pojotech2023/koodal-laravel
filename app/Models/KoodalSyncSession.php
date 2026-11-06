<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KoodalSyncSession extends Model
{
    use HasFactory;

    protected $table = 'koodal_sync_sessions';

    protected $fillable = [
        'user_one_id',
        'user_two_id',
        'status',
        'user_one_score',
        'user_two_score',
        'matched_count',
    ];

    public function userOne()
    {
        return $this->belongsTo(User::class, 'user_one_id');
    }

    public function userTwo()
    {
        return $this->belongsTo(User::class, 'user_two_id');
    }

    public function answers()
    {
        return $this->hasMany(KoodalSyncAnswer::class, 'session_id');
    }
}
