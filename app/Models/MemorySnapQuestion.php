<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MemorySnapQuestion extends Model
{
    use HasFactory;

    protected $table = 'memory_snap_questions';

    protected $fillable = [
        'challenge_id',
        'image_url',
        'question_text',
        'option_a',
        'option_b',
        'option_c',
        'option_d',
        'correct_option',
        'sort_order',
    ];

    public function challenge()
    {
        return $this->belongsTo(MemorySnapChallenge::class, 'challenge_id');
    }
}
