<?php

namespace Database\Seeders;

use App\Models\MemorySnapChallenge;
use App\Models\MemorySnapQuestion;
use Illuminate\Database\Seeder;

class MemorySnapSeeder extends Seeder
{
    public function run(): void
    {
        $challenges = [
            [
                'title' => 'Madurai Temple & Festival Memory',
                'image_url' => '/image/01_Koodal_Sync.png',
                'questions' => [
                    [
                        'question_text' => 'What color was the main header circle in this photo?',
                        'option_a' => 'Bright Pink (#E0245E)',
                        'option_b' => 'Deep Purple (#7C3AED)',
                        'option_c' => 'Golden Yellow (#F59E0B)',
                        'option_d' => 'Emerald Green (#10B981)',
                        'correct_option' => 'A',
                        'sort_order' => 1,
                    ],
                    [
                        'question_text' => 'What was the badge text displayed in the top right corner?',
                        'option_a' => '⭐ 100 pts',
                        'option_b' => '⭐ 0 pts',
                        'option_c' => '⭐ 50 pts',
                        'option_d' => '⭐ 25 pts',
                        'correct_option' => 'B',
                        'sort_order' => 2,
                    ],
                    [
                        'question_text' => 'What was the partner status shown in the middle status banner?',
                        'option_a' => 'Online',
                        'option_b' => 'Offline / Away',
                        'option_c' => 'Playing game',
                        'option_d' => 'In call',
                        'correct_option' => 'A',
                        'sort_order' => 3,
                    ],
                ]
            ],
            [
                'title' => 'Wedding Reception Memory',
                'image_url' => '/image/02_Koodal_Find_It.png',
                'questions' => [
                    [
                        'question_text' => 'How many total differences were mentioned in the bottom cyan banner?',
                        'option_a' => '5 Differences',
                        'option_b' => '8-10 Differences',
                        'option_c' => '12 Differences',
                        'option_d' => '15 Differences',
                        'correct_option' => 'B',
                        'sort_order' => 1,
                    ],
                    [
                        'question_text' => 'What was the time limit specified in the banner?',
                        'option_a' => '30 Seconds',
                        'option_b' => '45 Seconds',
                        'option_c' => '60 Seconds',
                        'option_d' => '90 Seconds',
                        'correct_option' => 'C',
                        'sort_order' => 2,
                    ],
                    [
                        'question_text' => 'What was the primary background accent color of this challenge card?',
                        'option_a' => 'Soft Cyan / Light Blue (#ECFEFF)',
                        'option_b' => 'Deep Violet (#5B21B6)',
                        'option_c' => 'Lime Green (#DCFCE7)',
                        'option_d' => 'Rose Pink (#FFF0F5)',
                        'correct_option' => 'A',
                        'sort_order' => 3,
                    ],
                ]
            ],
            [
                'title' => 'Romantic Sunset & Travel Memory',
                'image_url' => '/image/03_Koodal_Memory.png',
                'questions' => [
                    [
                        'question_text' => 'Which flower symbol was displayed in position #3 of the sequence?',
                        'option_a' => 'Rose (🌹)',
                        'option_b' => 'Hibiscus (🌺)',
                        'option_c' => 'Sunflower (🌻)',
                        'option_d' => 'Lotus (🪷)',
                        'correct_option' => 'A',
                        'sort_order' => 1,
                    ],
                    [
                        'question_text' => 'What was the overall background color of the grid mockup card?',
                        'option_a' => 'Soft Lavender / Purple (#F3E8FF)',
                        'option_b' => 'Bright Yellow (#FEF08A)',
                        'option_c' => 'Dark Charcoal (#1F2937)',
                        'option_d' => 'Teal Blue (#CCFBF1)',
                        'correct_option' => 'A',
                        'sort_order' => 2,
                    ],
                ]
            ],
        ];

        foreach ($challenges as $cData) {
            $challenge = MemorySnapChallenge::create([
                'title' => $cData['title'],
                'image_url' => $cData['image_url'],
                'is_active' => true,
            ]);

            foreach ($cData['questions'] as $qData) {
                MemorySnapQuestion::create([
                    'challenge_id' => $challenge->id,
                    'question_text' => $qData['question_text'],
                    'option_a' => $qData['option_a'],
                    'option_b' => $qData['option_b'],
                    'option_c' => $qData['option_c'],
                    'option_d' => $qData['option_d'],
                    'correct_option' => $qData['correct_option'],
                    'sort_order' => $qData['sort_order'],
                ]);
            }
        }
    }
}
