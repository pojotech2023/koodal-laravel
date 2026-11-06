<?php

namespace App\Services;

use App\Models\FlipGame;
use App\Models\GameInvite;
use App\Models\GameRoom;
use App\Models\User;
use Illuminate\Support\Str;

class GameInviteService
{
    /**
     * Send an invite to a partner, or return existing pending invite if already sent.
     */
    public function sendInvite(User $sender, User $receiver, string $gameType = 'sync'): array
    {
        // The pair's newest room — the one the pages and the app's
        // findActiveBetween() use. (`first()` picked the oldest, so a re-invite
        // could reset a room nobody was in while the players sat in another.)
        $room = GameRoom::whereIn('user_a_id', [$sender->id, $receiver->id])
            ->whereIn('user_b_id', [$sender->id, $receiver->id])
            ->where('game_type', $gameType)
            ->latest('id')
            ->first();

        $redirectUrl = null;
        $roomCode = null;

        if ($gameType === 'flip') {
            try {
                $flipGameService = new FlipGameService();
                $playerToken = 'user_' . $sender->id;
                session(['flip_player_token' => $playerToken]);
                cookie()->queue('flip_player_token', $playerToken, 60 * 24 * 30);

                $hasUserCols = FlipGameService::hasUserColumns();
                $forceFresh = request()->boolean('force_fresh');
                $flipGame = null;
                if (!$forceFresh) {
                    $query = FlipGame::where('status', 'waiting');
                    if ($hasUserCols) {
                        $query->where(function($q) use ($sender, $receiver, $playerToken) {
                            $q->where(function($sq) use ($sender, $receiver) {
                                $sq->where('user_one_id', $sender->id)->where('user_two_id', $receiver->id);
                            })->orWhere('p1_token', $playerToken);
                        });
                    } else {
                        $query->where('p1_token', $playerToken);
                    }
                    $candidate = $query->latest('id')->first();
                    if ($candidate && empty($candidate->matched)) {
                        $flipGame = $candidate;
                    }
                }

                if ($forceFresh || !$flipGame) {
                    $abandonQuery = FlipGame::whereIn('status', ['waiting', 'playing']);
                    if ($hasUserCols) {
                        $abandonQuery->where(function($q) use ($sender, $playerToken) {
                            $q->where('user_one_id', $sender->id)->orWhere('p1_token', $playerToken);
                        });
                    } else {
                        $abandonQuery->where('p1_token', $playerToken);
                    }
                    $abandonQuery->update(['status' => 'abandoned']);
                    $flipGame = $flipGameService->createGame($sender->name, $playerToken, $sender->id, $receiver->id);
                } else {
                    $updateData = ['p1_token' => $playerToken, 'p1_ready' => false, 'p2_ready' => false, 'status' => 'waiting'];
                    if ($hasUserCols) {
                        $updateData['user_one_id'] = $sender->id;
                        $updateData['user_two_id'] = $receiver->id;
                    }
                    $flipGame->update($updateData);
                }
                $roomCode = $flipGame->code;
                $redirectUrl = '/flip/' . $flipGame->code;
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::error("Flip invite generation error: " . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
                $fallbackCode = strtoupper(Str::random(8));
                $roomCode = $fallbackCode;
                $redirectUrl = '/flip/' . $fallbackCode;
            }
        } elseif ($gameType === 'memory_snap') {
            try {
                $snap = app(\App\Services\Games\Rooms\MemorySnapRoom::class);
                $forceFresh = request()->boolean('force_fresh');
                $msGame = null;
                if (! $forceFresh) {
                    $candidate = $snap->findActiveBetween($sender, $receiver);
                    if ($candidate && $candidate->status === 'waiting') {
                        $msGame = $candidate;
                    }
                }
                if (! $msGame) {
                    $msGame = $snap->create($sender, $receiver);
                }
                $snap->join($msGame, $sender);
                $roomCode = $msGame->code;
                $redirectUrl = '/memory-snap/room/' . $msGame->code;
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::error("Memory snap invite generation error: " . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
                $redirectUrl = route('games.memory_snap.start', $receiver->id, false);
            }
        } elseif ($gameType === 'find_it') {
            try {
                if (!$room) {
                    $room = GameRoom::create([
                        'room_code' => (string) Str::uuid(),
                        'user_a_id' => $sender->id,
                        'user_b_id' => $receiver->id,
                        'game_type' => 'find_it',
                        'status'    => 'lobby',
                    ]);
                } elseif (request()->boolean('force_fresh') && $room->status !== 'playing') {
                    $room->update([
                        'status'                 => 'lobby',
                        'user_a_answers'         => [],
                        'user_b_answers'         => [],
                        'user_a_score'           => 0,
                        'user_b_score'           => 0,
                        'user_a_ready'           => false,
                        'user_b_ready'           => false,
                        'started_at'             => null,
                        'ended_at'               => null,
                        'current_question_index' => 0,
                    ]);
                }
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning("Find It invite room creation error: " . $e->getMessage());
            }
            $redirectUrl = '/games/' . $receiver->id . '/find-it';
            $roomCode = $room ? $room->room_code : null;
        } elseif ($gameType === 'sync' && request()->input('mode') === 'offline') {
            $redirectUrl = '/games/' . $receiver->id . '/sync';
            $roomCode = $room ? $room->room_code : null;
        } else {
            $gameService = new KoodalGameService();
            $questions = $gameService->getSyncQuestionsForPair($sender->id, $receiver->id, 10);
            $questionIds = array_map(fn($q) => (int)$q['id'], $questions);

            if (!$room) {
                $room = GameRoom::create([
                    'room_code'              => (string) Str::uuid(),
                    'user_a_id'              => $sender->id,
                    'user_b_id'              => $receiver->id,
                    'game_type'              => $gameType,
                    'status'                 => 'lobby',
                    'question_ids'           => $questionIds,
                    'user_a_answers'         => [],
                    'user_b_answers'         => [],
                    'user_a_score'           => 0,
                    'user_b_score'           => 0,
                    'user_a_ready'           => false,
                    'user_b_ready'           => false,
                    'current_question_index' => 0,
                ]);
            } elseif ($room->status !== 'playing') {
                // A round in progress is joined, not wiped by a fresh invite.
                $room->update([
                    'room_code'              => (string) Str::uuid(),
                    'status'                 => 'lobby',
                    'current_question_index' => 0,
                    'user_a_answers'         => [],
                    'user_b_answers'         => [],
                    'user_a_score'           => 0,
                    'user_b_score'           => 0,
                    'user_a_ready'           => false,
                    'user_b_ready'           => false,
                    'started_at'             => null,
                    'ended_at'               => null,
                    'question_ids'           => $questionIds,
                ]);
            }
            $redirectUrl = '/games/room/' . $room->room_code;
            $roomCode = $room->room_code;
        }

        $inviteGameType = ($gameType === 'sync' && request()->input('mode') === 'offline') ? 'sync_offline' : $gameType;

        // Expire any previous pending invites of the same game_type so receiver gets a fresh real-time popup
        GameInvite::where('sender_id', $sender->id)
            ->where('receiver_id', $receiver->id)
            ->where('game_type', $inviteGameType)
            ->where('status', 'pending')
            ->update(['status' => 'expired']);

        $invite = GameInvite::create([
            'sender_id'     => $sender->id,
            'receiver_id'   => $receiver->id,
            'game_type'     => $inviteGameType,
            'status'        => 'pending',
            'expires_at'    => now()->addHours(24),
        ]);

        return [
            'invite'       => $invite,
            'room_code'    => $roomCode,
            'redirect_url' => $redirectUrl,
            'is_duplicate' => false,
            'can_remind'   => true,
        ];
    }

    /**
     * Send a reminder for a pending invite (max 3/day).
     */
    public function sendReminder(GameInvite $invite): array
    {
        if ($invite->status !== 'pending' || $invite->expires_at < now()) {
            return ['success' => false, 'message' => 'Invite is no longer active.'];
        }

        if ($invite->reminders_sent >= 3) {
            return ['success' => false, 'message' => 'Maximum limit of 3 reminders per day reached.'];
        }

        $invite->increment('reminders_sent');
        $invite->update(['last_reminder_at' => now()]);

        return ['success' => true, 'reminders_sent' => $invite->reminders_sent];
    }

    /**
     * Accept an invite and return or create the associated live GameRoom.
     */
    public function acceptInvite(GameInvite $invite): GameRoom
    {
        $invite->update(['status' => 'accepted']);

        // Check if a room exists between these two users for this game type
        $room = GameRoom::whereIn('user_a_id', [$invite->sender_id, $invite->receiver_id])
            ->whereIn('user_b_id', [$invite->sender_id, $invite->receiver_id])
            ->where('game_type', $invite->game_type)
            ->latest('id')
            ->first();

        // Accepting joins a round already under way rather than resetting it.
        if ($room && $room->status === 'playing') {
            return $room;
        }

        // If an existing room already has question_ids assigned, preserve them so both players see the exact same questions!
        $questionIds = ($room && !empty($room->question_ids)) ? $room->question_ids : [];

        $gameService = new KoodalGameService();
        $needsQuestions = empty($questionIds);
        if (!$needsQuestions && $invite->game_type !== 'find_it') {
            if (count($questionIds) < 10) {
                $needsQuestions = true;
            } else {
                $currentQs = \App\Models\KoodalSyncQuestion::where('is_active', true)->whereIn('id', $questionIds)->get();
                $hasFood = $currentQs->contains(fn($q) => $gameService->isFoodQuestion($q));
                $hasLocation = $currentQs->contains(fn($q) => $gameService->isLocationQuestion($q));
                if (!$hasFood || !$hasLocation) {
                    $needsQuestions = true;
                }
            }
        }

        if ($needsQuestions) {
            if ($invite->game_type === 'find_it') {
                $challenge = \App\Models\KoodalFindItChallenge::where('is_active', true)->inRandomOrder()->first();
                $questionIds = $challenge ? [(int)$challenge->id] : [];
            } else {
                $questions = $gameService->getSyncQuestionsForPair($invite->sender_id, $invite->receiver_id, 10);
                $questionIds = array_map(fn($q) => (int)$q['id'], $questions);
            }
        }

        if ($room) {
            $room->update([
                'status'                 => 'lobby',
                'current_question_index' => 0,
                'user_a_answers'         => [],
                'user_b_answers'         => [],
                'user_a_score'           => 0,
                'user_b_score'           => 0,
                'user_a_ready'           => false,
                'user_b_ready'           => false,
                'started_at'             => null,
                'ended_at'               => null,
                'question_ids'           => $questionIds,
            ]);
        } else {
            $room = GameRoom::create([
                'room_code'              => (string) Str::uuid(),
                'user_a_id'              => $invite->sender_id,
                'user_b_id'              => $invite->receiver_id,
                'game_type'              => $invite->game_type,
                'status'                 => 'lobby',
                'question_ids'           => $questionIds,
                'user_a_answers'         => [],
                'user_b_answers'         => [],
                'user_a_score'           => 0,
                'user_b_score'           => 0,
                'user_a_ready'           => false,
                'user_b_ready'           => false,
                'current_question_index' => 0,
            ]);
        }

        return $room;
    }
}
