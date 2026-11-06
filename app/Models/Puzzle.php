<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Puzzle extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'original_image',
        'modified_image',
        'width',
        'height',
        'difficulty',
        'time_limit_seconds',
        'is_active',
    ];

    protected $casts = [
        'width' => 'integer',
        'height' => 'integer',
        'time_limit_seconds' => 'integer',
        'is_active' => 'boolean',
    ];

    public function differences(): HasMany
    {
        return $this->hasMany(PuzzleDifference::class)->orderBy('sort_order');
    }

    public function matches(): HasMany
    {
        return $this->hasMany(GameMatch::class, 'puzzle_id');
    }
}
