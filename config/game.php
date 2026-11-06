<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Real-Time Communication Driver
    |--------------------------------------------------------------------------
    | Supported drivers: "polling" (for shared hosting), "reverb" (Laravel Reverb + Echo)
    */
    'driver' => env('GAME_DRIVER', 'polling'),

    /*
    |--------------------------------------------------------------------------
    | Minimum Differences Required to Activate Puzzle
    |--------------------------------------------------------------------------
    */
    'min_differences' => 7,

    /*
    |--------------------------------------------------------------------------
    | Scoring Parameters
    |--------------------------------------------------------------------------
    */
    'scoring' => [
        'correct_claim' => 100,
        'max_speed_bonus' => 50,
        'streak_bonus' => 25,
        'wrong_claim_penalty' => -20,
        'wrong_claim_cooldown_seconds' => 2,
    ],

    /*
    |--------------------------------------------------------------------------
    | In-game chat
    |--------------------------------------------------------------------------
    | Free members may only send the quick-reply chips. `blocked_words` is
    | matched case-insensitively against a paid member's free text.
    */
    'chat' => [
        // Matched against the whole message, emoji and punctuation removed
        // ("Nice Play 🔥" is `nice play`), so a chip can't carry other text.
        'chips' => [
            'super', 'good', 'wow', 'excellent', 'nice', 'nice play', 'all the best',
            'fantastic', 'awesome', 'well played', 'love it',
            // Motivational quick messages (same chips on the website and app).
            'please play quickly', 'oops hurry up', 'oh no your partner is waiting',
            'come on complete the games', 'lets finish the games and start chatting',
        ],
        'blocked_words' => ['badword1', 'abuse', 'spam'],
        'max_length' => 250,
    ],

    /*
    |--------------------------------------------------------------------------
    | Timings enforced by the API (seconds)
    |--------------------------------------------------------------------------
    */
    'timing' => [
        // Find It is played without a clock (0). A positive number brings the
        // round limit back, enforced by the server for web and app alike.
        'find_it_limit' => (int) env('GAME_FIND_IT_LIMIT', 0),
        'find_it_grace' => 3,
        'memory_snap_countdown' => 3.5,
        'memory_snap_recall' => 10.0,
        'memory_snap_reveal' => 2.5,
        'invite_ttl_hours' => 24,
        'invite_max_reminders' => 3,
        'solo_session_ttl_minutes' => 30,
    ],
];
