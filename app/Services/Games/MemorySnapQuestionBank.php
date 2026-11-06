<?php

namespace App\Services\Games;

use App\Models\MemorySnapChallenge;
use App\Models\MemorySnapQuestion;

/**
 * Picks the five photo questions for a Memory Snap round. Lifted out of
 * MemorySnapGameController so the website and the API deal the same way; the
 * controller now delegates here.
 */
class MemorySnapQuestionBank
{
    /**
     * Helper to retrieve exactly 5 questions with individual image URLs.
     */
    public function five(): array
    {
        $challenge = MemorySnapChallenge::where('is_active', true)->with('questions')->inRandomOrder()->first();

        $questions = collect();
        if ($challenge && $challenge->questions->count() >= 5) {
            $questions = $challenge->questions->take(5);
        } elseif ($challenge) {
            $questions = $challenge->questions;
            $remaining = 5 - $questions->count();
            $extra = MemorySnapQuestion::with('challenge')
                ->where('challenge_id', '!=', $challenge->id)
                ->inRandomOrder()
                ->take($remaining)
                ->get();
            $questions = $questions->concat($extra);
        } else {
            $questions = MemorySnapQuestion::with('challenge')->inRandomOrder()->take(5)->get();
        }

        $fallbackImage = ($challenge && $challenge->image_url) ? $challenge->image_url : '/image/03_Koodal_Memory.png';

        if ($questions->isEmpty()) {
            $prepared = [];
            for ($i = 1; $i <= 5; $i++) {
                $prepared[] = [
                    'id' => $i,
                    'image_url' => '/image/03_Koodal_Memory.png',
                    'question_text' => 'What color was prominent in this photo?',
                    'option_a' => 'Purple',
                    'option_b' => 'Pink',
                    'option_c' => 'Yellow',
                    'option_d' => 'Teal',
                    'correct_option' => 'A',
                    'sort_order' => $i,
                ];
            }
            return $prepared;
        }

        $prepared = [];
        $qList = $questions->values()->all();
        $totalAvail = count($qList);

        for ($i = 0; $i < 5; $i++) {
            $q = $qList[$i % $totalAvail];
            $img = $q->image_url ?: ($q->challenge->image_url ?? $fallbackImage);
            $prepared[] = [
                'id' => $q->id ?? ($i + 1),
                'image_url' => $img,
                'question_text' => $q->question_text,
                'option_a' => $q->option_a,
                'option_b' => $q->option_b,
                'option_c' => $q->option_c,
                'option_d' => $q->option_d,
                'correct_option' => $q->correct_option,
                'sort_order' => $i + 1,
            ];
        }

        return $prepared;
    }
}
