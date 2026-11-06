<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PuzzleDifference extends Model
{
    use HasFactory;

    protected $fillable = [
        'puzzle_id',
        'label',
        'x_pct',
        'y_pct',
        'radius_pct',
        'animation_type',
        'animate_on',
        'sprite_path',
        'sprite_x_pct',
        'sprite_y_pct',
        'sprite_w_pct',
        'sprite_h_pct',
        'lottie_path',
        'sort_order',
    ];

    protected $casts = [
        'x_pct' => 'float',
        'y_pct' => 'float',
        'radius_pct' => 'float',
        'sprite_x_pct' => 'float',
        'sprite_y_pct' => 'float',
        'sprite_w_pct' => 'float',
        'sprite_h_pct' => 'float',
        'sort_order' => 'integer',
    ];

    public function puzzle(): BelongsTo
    {
        return $this->belongsTo(Puzzle::class);
    }
}
