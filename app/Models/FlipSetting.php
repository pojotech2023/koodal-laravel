<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class FlipSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'card_back_path',
        'turn_timeout_seconds',
        'preview_seconds',
        'mismatch_show_seconds',
    ];

    public function getCardBackUrlAttribute(): ?string
    {
        if (!$this->card_back_path) {
            return null;
        }

        if (str_starts_with($this->card_back_path, 'http://') || str_starts_with($this->card_back_path, 'https://')) {
            return $this->card_back_path;
        }

        return Storage::disk('public')->url($this->card_back_path);
    }

    public static function instance(): self
    {
        $setting = static::first();
        if (!$setting) {
            $setting = static::create([
                'turn_timeout_seconds' => 60,
                'preview_seconds' => 2,
                'mismatch_show_seconds' => 3,
            ]);
        }

        return $setting;
    }
}
