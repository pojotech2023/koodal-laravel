<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KoodalSyncQuestion extends Model
{
    use HasFactory;

    protected $table = 'koodal_sync_questions';

    protected $fillable = [
        'category',
        'question_text_en',
        'question_text_ta',
        'options',
        'is_active',
    ];

    protected $casts = [
        'options' => 'array',
        'is_active' => 'boolean',
    ];

    public function getQuestionTextEnAttribute($value): ?string
    {
        if ($value === null) {
            return null;
        }
        return trim(preg_replace('/\s*\(#\s*\d+\)/i', '', (string)$value));
    }

    public function getQuestionTextTaAttribute($value): ?string
    {
        if ($value === null) {
            return null;
        }
        return trim(preg_replace('/\s*\(வரிசை\s*#\s*\d+\)/iu', '', (string)$value));
    }

    public const CATEGORIES = [
        'Lifestyle' => 'Lifestyle / வாழ்க்கை முறை',
        'Food' => 'Food & Dining / உணவு விருந்து',
        'Travel' => 'Travel & Vacation / பயணம் & உலா',
        'Location' => 'Location & Travel / இடம் & பயணம்',
        'Daily habits' => 'Daily Habits / தினசரி பழக்கங்கள்',
        'Personality' => 'Personality & Mindset / ஆளுமை & எண்ணங்கள்',
        'Money preferences' => 'Money Preferences / பண நிர்வாகம்',
        'Family/life situations' => 'Family & Life Situations / குடும்பம் & வாழ்க்கை',
        'Fun hypothetical situations' => 'Fun Hypotheticals / ஜாலியான கற்பனைகள்',
        'Future preferences' => 'Future Preferences / எதிர்கால திட்டங்கள்',
        'Entertainment' => 'Entertainment & Movies / பொழுதுபோக்கு & திரைப்படங்கள்',
        'Morning/Night habits' => 'Morning/Night Routine / காலை/இரவு வழக்கங்கள்',
    ];
}
