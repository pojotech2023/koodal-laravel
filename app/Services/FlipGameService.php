<?php

namespace App\Services;

use App\Models\FlipGame;
use App\Models\FlipSetting;
use App\Models\FlipTileImage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class FlipGameService
{
    protected static ?bool $hasUserColumns = null;

    public static function hasUserColumns(): bool
    {
        if (static::$hasUserColumns === null) {
            try {
                static::$hasUserColumns = \Illuminate\Support\Facades\Schema::hasColumn('flip_games', 'user_one_id');
            } catch (\Throwable $e) {
                static::$hasUserColumns = false;
            }
        }
        return (bool) static::$hasUserColumns;
    }

    public function seedDefaultTileImages(): void
    {
        try {
            $samples = [
                ['name' => 'Red Rose', 'emoji' => '🌹', 'color' => '#f43f5e', 'bg' => '#ffe4e6'],
                ['name' => 'Sunflower', 'emoji' => '🌻', 'color' => '#eab308', 'bg' => '#fef9c3'],
                ['name' => 'Hibiscus', 'emoji' => '🌺', 'color' => '#ec4899', 'bg' => '#fce7f3'],
                ['name' => 'Tulip', 'emoji' => '🌷', 'color' => '#fb7185', 'bg' => '#fff1f2'],
                ['name' => 'Peacock', 'emoji' => '🦚', 'color' => '#0284c7', 'bg' => '#e0f2fe'],
                ['name' => 'Diamond Gem', 'emoji' => '💎', 'color' => '#0284c7', 'bg' => '#e0f2fe'],
                ['name' => 'Gold Crown', 'emoji' => '👑', 'color' => '#d97706', 'bg' => '#fef3c7'],
                ['name' => 'Ring', 'emoji' => '💍', 'color' => '#4f46e5', 'bg' => '#e0e7ff'],
            ];

            \Illuminate\Support\Facades\Storage::disk('public')->makeDirectory('flip-tiles');

            foreach ($samples as $index => $sample) {
                $filename = 'flip-tiles/sample_' . sprintf('%02d', $index + 1) . '.svg';
                if (!\Illuminate\Support\Facades\Storage::disk('public')->exists($filename)) {
                    $svg = "<svg xmlns=\"http://www.w3.org/2000/svg\" width=\"300\" height=\"300\" viewBox=\"0 0 300 300\"><rect width=\"300\" height=\"300\" rx=\"36\" fill=\"{$sample['bg']}\"/><circle cx=\"150\" cy=\"150\" r=\"110\" fill=\"#ffffff\" opacity=\"0.8\"/><text x=\"150\" y=\"175\" font-size=\"110\" text-anchor=\"middle\" dominant-baseline=\"central\">{$sample['emoji']}</text></svg>";
                    \Illuminate\Support\Facades\Storage::disk('public')->put($filename, $svg);
                }

                FlipTileImage::updateOrCreate(
                    ['path' => $filename],
                    ['name' => $sample['name'], 'is_active' => true]
                );
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning("Could not auto-seed flip tile images: " . $e->getMessage());
        }
    }

    /**
     * Create a new Flip the Tiles game.
     */
    public function createGame(?string $nickname, string $playerToken, ?int $userId = null, ?int $partnerId = null): FlipGame
    {
        $activeImages = FlipTileImage::where('is_active', true)->get();

        if ($activeImages->count() < 8) {
            $this->seedDefaultTileImages();
            $activeImages = FlipTileImage::where('is_active', true)->get();
        }

        if ($activeImages->count() >= 8) {
            $selected8 = $activeImages->random(8)->pluck('id')->toArray();
        } elseif ($activeImages->count() > 0) {
            $ids = $activeImages->pluck('id')->toArray();
            $selected8 = [];
            while (count($selected8) < 8) {
                $selected8 = array_merge($selected8, $ids);
            }
            $selected8 = array_slice($selected8, 0, 8);
        } else {
            $selected8 = range(1, 8);
        }

        // Duplicate to 16 tiles (4x4 board)
        $tiles = array_merge($selected8, $selected8);

        // Fisher-Yates shuffle using random_int for true randomness
        for ($i = count($tiles) - 1; $i > 0; $i--) {
            $j = random_int(0, $i);
            $temp = $tiles[$i];
            $tiles[$i] = $tiles[$j];
            $tiles[$j] = $temp;
        }

        // Generate unique 8-char code
        do {
            $code = strtoupper(Str::random(8));
        } while (FlipGame::where('code', $code)->exists());

        $p1Name = trim($nickname ?: '') ?: 'Player 1';

        $authUser = auth()->guard('web')->user();
        $u1Id = $userId ?: ($authUser ? $authUser->id : null);
        if (!$u1Id && str_starts_with($playerToken, 'user_')) {
            $u1Id = (int) str_replace('user_', '', $playerToken);
        }

        $gameData = [
            'code' => $code,
            'board' => $tiles,
            'matched' => [],
            'messages' => [],
            'p1_token' => $playerToken,
            'p1_name' => $p1Name,
            'p1_score' => 0,
            'p2_score' => 0,
            'turn' => 1,
            'status' => 'waiting',
            'last_move_at' => now(),
        ];

        if (static::hasUserColumns()) {
            $gameData['user_one_id'] = $u1Id;
            $gameData['user_two_id'] = $partnerId;
        }

        $game = FlipGame::create($gameData);

        return $game;
    }

    /**
     * Join an existing game.
     */
    public function join(FlipGame $game, ?string $nickname, string $playerToken, ?int $userId = null): FlipGame
    {
        return DB::transaction(function () use ($game, $nickname, $playerToken, $userId) {
            $lockedGame = FlipGame::where('id', $game->id)->lockForUpdate()->first();

            $pNum = $lockedGame->playerNumber($playerToken);

            if ($pNum === 1) {
                if ($nickname && trim($nickname) !== '') {
                    $lockedGame->p1_name = trim($nickname);
                    $lockedGame->save();
                }
                return $lockedGame;
            }

            if ($pNum === 2) {
                if ($nickname && trim($nickname) !== '') {
                    $lockedGame->p2_name = trim($nickname);
                    $lockedGame->save();
                }
                return $lockedGame;
            }

            // Seat 2 is taken only while the game is waiting, and only if it is
            // free and either open (no member named) or named for this member;
            // the named member may also reclaim it. It used to go to anyone
            // who opened the link — replacing a player 2 already seated.
            $isU2 = static::hasUserColumns() && $userId && (int) $lockedGame->user_two_id === (int) $userId;
            $open = $lockedGame->p2_token === null
                && (! static::hasUserColumns() || $lockedGame->user_two_id === null || $isU2);
            if ($lockedGame->isWaiting() && ($open || $isU2)) {
                $authUser = auth()->guard('web')->user();
                $u2Id = $userId ?: ($authUser ? $authUser->id : null);
                if (!$u2Id && str_starts_with($playerToken, 'user_')) {
                    $u2Id = (int) str_replace('user_', '', $playerToken);
                }
                if ($u2Id && static::hasUserColumns()) {
                    $lockedGame->user_two_id = $u2Id;
                }

                $p2Name = trim($nickname ?: '');
                if (!$p2Name && $authUser) {
                    $p2Name = $authUser->name;
                } elseif (!$p2Name && $u2Id) {
                    $p2User = \App\Models\User::find($u2Id);
                    $p2Name = $p2User?->name;
                } elseif (!$p2Name && !empty($lockedGame->p2_name) && $lockedGame->p2_name !== 'Player 2') {
                    $p2Name = $lockedGame->p2_name;
                }
                if (!$p2Name) {
                    $p2Name = 'Bride';
                }

                $lockedGame->p2_token = $playerToken;
                $lockedGame->p2_name = $p2Name;
                if ($lockedGame->p1_ready && $lockedGame->p2_ready) {
                    $lockedGame->status = 'playing';
                } else {
                    $lockedGame->status = 'waiting';
                }
                $lockedGame->last_move_at = now();
                $lockedGame->save();
            }

            return $lockedGame;
        });
    }

    /**
     * Mark a player as ready.
     */
    public function toggleReady(FlipGame $game, string $playerToken): FlipGame
    {
        return DB::transaction(function () use ($game, $playerToken) {
            /** @var FlipGame $lockedGame */
            $lockedGame = FlipGame::where('id', $game->id)->lockForUpdate()->first();

            $pNum = $lockedGame->playerNumber($playerToken);
            if (!$pNum) {
                abort(403, 'You are not a player in this game.');
            }

            if ($lockedGame->status !== 'waiting') {
                return $lockedGame;
            }

            if ($pNum === 1) {
                $lockedGame->p1_ready = true;
                if (!$lockedGame->p1_token) {
                    $lockedGame->p1_token = $playerToken;
                }
            } elseif ($pNum === 2) {
                $lockedGame->p2_ready = true;
                if (!$lockedGame->p2_token) {
                    $lockedGame->p2_token = $playerToken;
                }
            }

            if ($lockedGame->p1_ready && $lockedGame->p2_ready) {
                $lockedGame->status = 'playing';
                $lockedGame->last_move_at = now();
            }

            $lockedGame->save();
            return $lockedGame;
        });
    }

    /**
     * Check and process timeouts for turn (60s) and abandonment (5m).
     */
    public function checkTimeouts(FlipGame $game): void
    {
        if ($game->status !== 'playing') {
            return;
        }

        $setting = FlipSetting::instance();
        $turnTimeout = $setting->turn_timeout_seconds ?? 60;

        if (!$game->last_move_at) {
            $game->last_move_at = now();
            $game->save();
            return;
        }

        $secondsInactive = $game->last_move_at->diffInSeconds(now());

        // 1 hour inactivity -> mark abandoned
        if ($secondsInactive >= 3600) {
            $game->status = 'abandoned';
            $game->save();
            return;
        }
    }

    /**
     * Flip a tile.
     */
    public function flip(FlipGame $game, int $tileIndex, string $playerToken): array
    {
        return DB::transaction(function () use ($game, $tileIndex, $playerToken) {
            /** @var FlipGame $lockedGame */
            $lockedGame = FlipGame::where('id', $game->id)->lockForUpdate()->first();

            $this->checkTimeouts($lockedGame);

            if ($lockedGame->status !== 'playing') {
                abort(409, 'Game is not currently active.');
            }

            $playerNum = $lockedGame->playerNumber($playerToken);
            if ($playerNum !== $lockedGame->turn) {
                abort(403, 'It is not your turn.');
            }

            if ($tileIndex < 0 || $tileIndex > 15) {
                abort(422, 'Invalid tile index.');
            }

            // Check if tile is already matched
            $matchedIndices = collect($lockedGame->matched ?? [])->pluck('index')->toArray();
            if (in_array($tileIndex, $matchedIndices)) {
                abort(409, 'Tile is already matched.');
            }

            // Check if tile is same as first flip
            if ($lockedGame->first_flip !== null && $lockedGame->first_flip === $tileIndex) {
                abort(422, 'Tile is already flipped.');
            }

            // Check mismatch animation in progress
            if ($lockedGame->mismatch_until && $lockedGame->mismatch_until->isFuture()) {
                abort(409, 'Please wait for the current turn to complete.');
            }

            // Check active preview
            if ($lockedGame->preview_until && $lockedGame->preview_until->isFuture()) {
                abort(409, 'Preview is currently active.');
            }

            // If a previous mismatch time has passed, clear flipped tiles (2nd player keeps the turn to open the next tile)
            if ($lockedGame->mismatch_until && $lockedGame->mismatch_until->isPast()) {
                $lockedGame->first_flip = null;
                $lockedGame->second_flip = null;
                $lockedGame->mismatch_until = null;
                $lockedGame->save();

                if ($playerNum !== $lockedGame->turn) {
                    abort(403, 'It is not your turn.');
                }
            }

            $board = $lockedGame->board;
            $imageId = $board[$tileIndex] ?? null;
            if (!$imageId) {
                abort(422, 'Tile image not found.');
            }

            $tileImage = FlipTileImage::find($imageId);
            $imageUrl = $tileImage ? $tileImage->url : '';

            $setting = FlipSetting::instance();
            $mismatchSecs = max(3.5, (float)($setting->mismatch_show_seconds ?? 3.5));

            if ($lockedGame->first_flip === null) {
                // First flip of pair (Current player opens 1st tile, then passes turn to opponent to open 2nd tile)
                $lockedGame->first_flip = $tileIndex;
                $lockedGame->turn = ($playerNum === 1 ? 2 : 1);
                $lockedGame->last_move_at = now();
                $lockedGame->save();

                return [
                    'index' => $tileIndex,
                    'image' => $imageUrl,
                    'matched' => false,
                ];
            }

            // Second flip of turn
            $firstIndex = $lockedGame->first_flip;
            $lockedGame->second_flip = $tileIndex;

            $firstImageId = $board[$firstIndex];
            $secondImageId = $board[$tileIndex];

            if ($firstImageId === $secondImageId) {
                // Match!
                // Partner chemistry rule: The score point goes to the partner who opened the FIRST tile,
                // and the 2nd player ($playerNum) keeps the turn to open the next tile!
                $firstFlipper = ($playerNum === 1 ? 2 : 1);

                $matched = $lockedGame->matched ?? [];
                $matched[] = [
                    'index' => $firstIndex,
                    'image_id' => $firstImageId,
                    'by' => $firstFlipper,
                ];
                $matched[] = [
                    'index' => $tileIndex,
                    'image_id' => $secondImageId,
                    'by' => $firstFlipper,
                ];

                $lockedGame->matched = $matched;
                if ($firstFlipper === 1) {
                    $lockedGame->p1_score += 1;
                } else {
                    $lockedGame->p2_score += 1;
                }

                $lockedGame->first_flip = null;
                $lockedGame->second_flip = null;
                $lockedGame->turn = $playerNum;

                if (count($matched) >= 16) {
                    $lockedGame->status = 'finished';

                    // Extract user IDs to record game scores in KoodalGameScore table
                    $u1Id = $lockedGame->user_one_id;
                    $u2Id = $lockedGame->user_two_id;

                    if (!$u1Id && str_starts_with($lockedGame->p1_token ?? '', 'user_')) {
                        $u1Id = (int) str_replace('user_', '', $lockedGame->p1_token);
                    }
                    if (!$u2Id && str_starts_with($lockedGame->p2_token ?? '', 'user_')) {
                        $u2Id = (int) str_replace('user_', '', $lockedGame->p2_token);
                    }

                    // Only the two members actually seated are scored. (A fallback
                    // here took the site's latest Flip invite — anyone's — and
                    // credited its sender and receiver with this game.)
                    if ($u1Id && $u2Id && $u1Id !== $u2Id) {
                        try {
                            $koodalService = app(KoodalGameService::class);
                            $diff = abs(($lockedGame->p1_score ?? 0) - ($lockedGame->p2_score ?? 0));
                            $balanceMap = [
                                0 => 100, // 4-4 balance
                                1 => 90,
                                2 => 80,  // 5-3 balance
                                3 => 70,
                                4 => 60,  // 6-2 balance
                                5 => 45,
                                6 => 30,  // 7-1 balance
                                7 => 15,
                                8 => 0    // 8-0 balance
                            ];
                            $teamChemistryScore = $balanceMap[$diff] ?? 0;

                            $koodalService->recordGameScore($u1Id, $u2Id, 'flip', $teamChemistryScore, 100, [
                                'pairs_matched' => $lockedGame->p1_score,
                                'opponent_pairs' => $lockedGame->p2_score,
                                'total_pairs' => 8,
                                'game_code' => $lockedGame->code,
                            ]);

                            $koodalService->recordGameScore($u2Id, $u1Id, 'flip', $teamChemistryScore, 100, [
                                'pairs_matched' => $lockedGame->p2_score,
                                'opponent_pairs' => $lockedGame->p1_score,
                                'total_pairs' => 8,
                                'game_code' => $lockedGame->code,
                            ]);
                        } catch (\Throwable $e) {
                            \Log::error('FlipGameService finish score record error: ' . $e->getMessage());
                        }
                    }
                }

                $lockedGame->last_move_at = now();
                $lockedGame->save();

                return [
                    'index' => $tileIndex,
                    'image' => $imageUrl,
                    'matched' => true,
                    'awarded_to' => $firstFlipper,
                ];
            }

            // Mismatch!
            $lockedGame->mismatch_until = now()->addMilliseconds((int)($mismatchSecs * 1000));
            $lockedGame->last_move_at = now();
            $lockedGame->save();

            return [
                'index' => $tileIndex,
                'image' => $imageUrl,
                'matched' => false,
            ];
        });
    }

    /**
     * Trigger preview.
     */
    public function usePreview(FlipGame $game, string $playerToken): void
    {
        DB::transaction(function () use ($game, $playerToken) {
            /** @var FlipGame $lockedGame */
            $lockedGame = FlipGame::where('id', $game->id)->lockForUpdate()->first();

            $this->checkTimeouts($lockedGame);

            if ($lockedGame->status !== 'playing') {
                abort(409, 'Game is not playing.');
            }

            $pNum = $lockedGame->playerNumber($playerToken);
            if (!$pNum) {
                abort(403, 'You are not a player in this game.');
            }

            if ($lockedGame->preview_used) {
                abort(409, 'Preview has already been used.');
            }

            // A mismatched pair whose show time is over is already face down on
            // both screens; it is only cleared on the next read or flip, so do
            // that here rather than refuse the peek over tiles nobody can see.
            if ($lockedGame->mismatch_until && $lockedGame->mismatch_until->isPast()) {
                $lockedGame->first_flip = null;
                $lockedGame->second_flip = null;
                $lockedGame->mismatch_until = null;
            }

            if ($lockedGame->first_flip !== null || $lockedGame->second_flip !== null) {
                abort(409, 'Cannot use preview while tiles are open.');
            }

            if ($lockedGame->mismatch_until && $lockedGame->mismatch_until->isFuture()) {
                abort(409, 'Cannot use preview during turn flip.');
            }

            $setting = FlipSetting::instance();
            $previewSecs = $setting->preview_seconds ?? 2;

            $lockedGame->preview_used = true;
            $lockedGame->preview_until = now()->addSeconds($previewSecs);
            $lockedGame->save();
        });
    }

    /**
     * Send live chat message.
     */
    public function sendMessage(FlipGame $game, string $playerToken, string $message): array
    {
        return DB::transaction(function () use ($game, $playerToken, $message) {
            /** @var FlipGame $lockedGame */
            $lockedGame = FlipGame::where('id', $game->id)->lockForUpdate()->first();

            $pNum = $lockedGame->playerNumber($playerToken);
            if (!$pNum) {
                abort(403, 'You are not in this game.');
            }

            $name = $pNum === 1 ? ($lockedGame->p1_name ?? 'Player 1') : ($lockedGame->p2_name ?? 'Player 2');
            $text = trim($message);

            if ($text === '') {
                abort(422, 'Message text cannot be empty.');
            }

            $messages = $lockedGame->messages ?? [];
            $newMessage = [
                'id' => (string) \Illuminate\Support\Str::uuid(),
                'sender' => $pNum,
                'name' => $name,
                'text' => mb_substr($text, 0, 250),
                'time' => now()->format('H:i'),
            ];

            $messages[] = $newMessage;
            $lockedGame->messages = array_slice($messages, -50); // Keep last 50
            $lockedGame->save();

            return $newMessage;
        });
    }

    /**
     * Get game state payload.
     */
    public function state(FlipGame $game, ?string $playerToken): array
    {
        $this->checkTimeouts($game);

        // Auto transition to playing if both players are marked ready
        if ($game->p1_ready && $game->p2_ready && $game->status === 'waiting') {
            DB::transaction(function () use ($game) {
                /** @var FlipGame $lockedGame */
                $lockedGame = FlipGame::where('id', $game->id)->lockForUpdate()->first();
                if ($lockedGame && $lockedGame->p1_ready && $lockedGame->p2_ready && $lockedGame->status === 'waiting') {
                    $lockedGame->status = 'playing';
                    $lockedGame->last_move_at = now();
                    $lockedGame->save();
                }
            });
            $game->refresh();
        }

        // Check if mismatch duration has elapsed
        if ($game->mismatch_until && $game->mismatch_until->isPast() && $game->status === 'playing') {
            DB::transaction(function () use ($game) {
                /** @var FlipGame $lockedGame */
                $lockedGame = FlipGame::where('id', $game->id)->lockForUpdate()->first();
                if ($lockedGame->mismatch_until && $lockedGame->mismatch_until->isPast()) {
                    $lockedGame->first_flip = null;
                    $lockedGame->second_flip = null;
                    $lockedGame->mismatch_until = null;
                    $lockedGame->save();
                }
            });
            $game->refresh();
        }

        $you = $game->playerNumber($playerToken);

        // Pre-fetch all images on the board for URL resolution
        $imageIds = array_unique($game->board ?? []);
        $imagesMap = FlipTileImage::whereIn('id', $imageIds)->get()->keyBy('id');

        // Form matched array
        $matchedList = [];
        foreach ($game->matched ?? [] as $m) {
            $imgId = $m['image_id'] ?? null;
            $matchedList[] = [
                'index' => (int) $m['index'],
                'image' => (isset($imagesMap[$imgId]) ? $imagesMap[$imgId]->url : ''),
                'by' => (int) $m['by'],
            ];
        }

        // Form flipped array (unmatched open tiles)
        $flippedList = [];
        if ($game->first_flip !== null) {
            $imgId1 = $game->board[$game->first_flip] ?? null;
            $flippedList[] = [
                'index' => (int) $game->first_flip,
                'image' => (isset($imagesMap[$imgId1]) ? $imagesMap[$imgId1]->url : ''),
            ];
        }
        if ($game->second_flip !== null) {
            $imgId2 = $game->board[$game->second_flip] ?? null;
            $flippedList[] = [
                'index' => (int) $game->second_flip,
                'image' => (isset($imagesMap[$imgId2]) ? $imagesMap[$imgId2]->url : ''),
            ];
        }

        // Form preview object
        $isPreviewActive = $game->preview_until && $game->preview_until->isFuture();
        $previewImages = null;

        if ($isPreviewActive) {
            $previewImages = [];
            foreach ($game->board as $idx => $imgId) {
                $previewImages[] = [
                    'index' => $idx,
                    'image' => isset($imagesMap[$imgId]) ? $imagesMap[$imgId]->url : '',
                ];
            }
        }

        // Determine winner
        $winner = null;
        $chemistryScore = null;
        if ($game->status === 'finished') {
            if ($game->p1_score > $game->p2_score) {
                $winner = 1;
            } elseif ($game->p2_score > $game->p1_score) {
                $winner = 2;
            } else {
                $winner = 'draw';
            }

            $diff = abs(($game->p1_score ?? 0) - ($game->p2_score ?? 0));
            $balanceMap = [
                0 => 100, // 4-4
                1 => 90,
                2 => 80,  // 5-3
                3 => 70,
                4 => 60,  // 6-2
                5 => 45,
                6 => 30,  // 7-1
                7 => 15,
                8 => 0    // 8-0
            ];
            $chemistryScore = $balanceMap[$diff] ?? 0;
        }

        $authUser = auth()->guard('web')->user();
        $partnerId = null;
        if ($authUser) {
            if ($game->user_one_id && $game->user_one_id == $authUser->id) {
                $partnerId = $game->user_two_id;
            } elseif ($game->user_two_id && $game->user_two_id == $authUser->id) {
                $partnerId = $game->user_one_id;
            }
        }
        if (!$partnerId && $authUser) {
            if (str_starts_with($game->p1_token ?? '', 'user_')) {
                $p1UId = (int) str_replace('user_', '', $game->p1_token);
                if ($p1UId == $authUser->id && str_starts_with($game->p2_token ?? '', 'user_')) {
                    $partnerId = (int) str_replace('user_', '', $game->p2_token);
                } elseif ($p1UId != $authUser->id) {
                    $partnerId = $p1UId;
                }
            }
        }
        if (!$partnerId) {
            $partnerId = session('active_partner_id');
        }
        if (!$partnerId && $authUser) {
            $invite = \App\Models\GameInvite::where('game_type', 'flip')
                ->where(function($q) use ($authUser) {
                    $q->where('sender_id', $authUser->id)->orWhere('receiver_id', $authUser->id);
                })->latest()->first();
            if ($invite) {
                $partnerId = ($invite->sender_id == $authUser->id) ? $invite->receiver_id : $invite->sender_id;
            }
        }

        $setting = FlipSetting::instance();

        return [
            'status' => $game->status,
            'you' => $you,
            'p2_joined' => !empty($game->p2_token) || !empty($game->user_two_id) || (bool) $game->p2_ready || (!empty($game->p2_name) && $game->p2_name !== 'Player 2'),
            'turn' => (int) $game->turn,
            'names' => [
                '1' => $game->p1_name ?? 'Player 1',
                '2' => $game->p2_name ?? 'Player 2',
            ],
            'scores' => [
                '1' => (int) $game->p1_score,
                '2' => (int) $game->p2_score,
            ],
            'ready' => [
                '1' => (bool) $game->p1_ready,
                '2' => (bool) $game->p2_ready,
                'both' => (bool) ($game->p1_ready && $game->p2_ready),
            ],
            'matched' => $matchedList,
            'flipped' => $flippedList,
            'preview' => [
                'active' => (bool) $isPreviewActive,
                'until' => $game->preview_until ? $game->preview_until->toIso8601String() : null,
                'images' => $previewImages,
            ],
            'preview_used' => (bool) $game->preview_used,
            'winner' => $winner,
            'chemistry_score' => $chemistryScore,
            'partner_id' => $partnerId,
            'invite_url' => '/flip/join/' . $game->code,
            'card_back' => $setting->card_back_url,
            'messages' => $game->messages ?? [],
        ];
    }
}
