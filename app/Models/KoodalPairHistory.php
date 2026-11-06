<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KoodalPairHistory extends Model
{
    use HasFactory;

    protected $table = 'koodal_sync_pair_histories';

    public $timestamps = false;

    protected $fillable = [
        'user_one_id',
        'user_two_id',
        'question_id',
        'created_at',
    ];
}
