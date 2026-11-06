<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MemorySnapChallenge extends Model
{
    use HasFactory;

    protected $table = 'memory_snap_challenges';

    protected $fillable = [
        'title',
        'image_url',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function questions()
    {
        return $this->hasMany(MemorySnapQuestion::class, 'challenge_id')->orderBy('sort_order');
    }
}
