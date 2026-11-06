<?php

namespace App\Services\Games;

/**
 * The games a member can start. Content (questions, images) is admin-managed on
 * the website; this is only the menu the app draws.
 */
class GameCatalog
{
    /** @return array<int, array<string, mixed>> */
    public function all(): array
    {
        return [
            [
                'type' => 'sync',
                'title' => 'Koodal Sync',
                'subtitle' => 'Answer 10 questions and see how alike you think',
                'icon' => '🎯',
                'mode' => 'both',
                'players' => 2,
                'vibe_key' => 'sync',
                'available' => true,
            ],
            [
                'type' => 'find_it',
                'title' => 'Find It',
                'subtitle' => 'Spot the 7 differences together — no time limit',
                'icon' => '🔍',
                'mode' => 'live',
                'players' => 2,
                'vibe_key' => 'find_it',
                'available' => true,
            ],
            [
                'type' => 'memory_snap',
                'title' => 'Memory Snap',
                'subtitle' => 'Study the photo, then answer questions about it',
                'icon' => '🧠',
                'mode' => 'live',
                'players' => 2,
                'vibe_key' => 'memory_snap',
                'available' => true,
            ],
            [
                'type' => 'flip',
                'title' => 'Flip the Tiles',
                'subtitle' => 'Take turns uncovering matching pairs',
                'icon' => '🧩',
                'mode' => 'live',
                'players' => 2,
                'vibe_key' => 'flip',
                'available' => true,
            ],
        ];
    }
}
