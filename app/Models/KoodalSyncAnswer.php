<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KoodalSyncAnswer extends Model
{
    use HasFactory;

    protected $table = 'koodal_sync_answers';

    protected $fillable = [
        'session_id',
        'question_id',
        'user_id',
        'selected_option',
    ];

    public function session()
    {
        return $this->belongsTo(KoodalSyncSession::class, 'session_id');
    }

    public function question()
    {
        return $this->belongsTo(KoodalSyncQuestion::class, 'question_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
