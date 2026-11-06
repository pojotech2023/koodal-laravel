<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KoodalFindItChallenge extends Model
{
    use HasFactory;

    protected $table = 'koodal_find_it_challenges';

    protected $fillable = [
        'title',
        'image_a',
        'image_b',
        'differences',
        'is_active',
    ];

    protected $casts = [
        'differences' => 'array',
        'is_active' => 'boolean',
    ];
}
