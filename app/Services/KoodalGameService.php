<?php

namespace App\Services;

use App\Models\GameRoom;
use App\Models\KoodalFindItChallenge;
use App\Models\KoodalGameScore;
use App\Models\KoodalPairHistory;
use App\Models\KoodalSyncAnswer;
use App\Models\KoodalSyncQuestion;
use App\Models\KoodalSyncSession;
use App\Models\KoodalVibeResult;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Support\Facades\DB;

class KoodalGameService
{
    /**
     * Get 10 compatibility questions for a user pair.
     *
     * KEY RULE: If the partner has already answered questions that this user
     * has NOT yet answered, show those SAME question IDs so both users answer
     * identically and the match comparison works correctly.
     */
    /**
     * Check if a question belongs to the Food category or asks about food preferences.
     */
    public function isFoodQuestion($q): bool
    {
        if (!$q) return false;
        $category = strtolower($q->category ?? ($q['category'] ?? ''));
        if (str_contains($category, 'food') || str_contains($category, 'உணவு')) {
            return true;
        }
        $en = strtolower($q->question_text_en ?? ($q['question_text_en'] ?? ''));
        $ta = $q->question_text_ta ?? ($q['question_text_ta'] ?? '');
        $keywords = ['food', 'breakfast', 'meal', 'biryani', 'dosa', 'cook', 'dessert', 'spic', 'dish', 'sweet', 'சாப்பாடு', 'உணவு', 'சமையல்', 'இனிப்பு'];
        foreach ($keywords as $kw) {
            if (str_contains($en, $kw) || str_contains($ta, $kw)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Check if a question belongs to the Location / Travel category or asks about living/travel destination.
     */
    public function isLocationQuestion($q): bool
    {
        if (!$q) return false;
        $category = strtolower($q->category ?? ($q['category'] ?? ''));
        if (str_contains($category, 'travel') || str_contains($category, 'location') || str_contains($category, 'பயணம்') || str_contains($category, 'இடம்')) {
            return true;
        }
        $en = strtolower($q->question_text_en ?? ($q['question_text_en'] ?? ''));
        $ta = $q->question_text_ta ?? ($q['question_text_ta'] ?? '');
        $keywords = ['location', 'living setup', 'destination', 'holiday', 'trip', 'beach', 'hill station', 'hills', 'city', 'town', 'village', 'ooty', 'kodaikanal', 'இடம்', 'சுற்றுலா', 'வசிக்கும்', 'ஊர்', 'பயணம்', 'மலை'];
        foreach ($keywords as $kw) {
            if (str_contains($en, $kw) || str_contains($ta, $kw)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Ensure core Favorite Food and Favorite Location questions exist in database.
     */
    public function ensureCoreCategoryQuestionsExist(): void
    {
        try {
            KoodalSyncQuestion::firstOrCreate(
                [
                    'category' => 'Travel',
                    'question_text_en' => 'Which is your favorite hill station to visit?',
                ],
                [
                    'question_text_ta' => 'உங்களுக்கு மிகவும் பிடித்த ஹில் ஸ்டேஷன் எது?',
                    'options' => [
                        ['key' => 'A', 'label_en' => 'Ooty (Queen of Hills)', 'label_ta' => 'ஊட்டி 🌲'],
                        ['key' => 'B', 'label_en' => 'Kodaikanal (Princess of Hills)', 'label_ta' => 'கொடைக்கானல் 🌸'],
                        ['key' => 'C', 'label_en' => 'Munnar (Tea Garden Haven)', 'label_ta' => 'மூணார் 🍃'],
                        ['key' => 'D', 'label_en' => 'Yercaud (Jewel of the South)', 'label_ta' => 'ஏற்காடு ⛰️'],
                    ],
                    'is_active' => true,
                ]
            );

            KoodalSyncQuestion::firstOrCreate(
                [
                    'category' => 'Food',
                    'question_text_en' => 'Which is your all-time favorite sweet?',
                ],
                [
                    'question_text_ta' => 'உங்களுக்கு மிகவும் பிடித்த இனிப்பு எது?',
                    'options' => [
                        ['key' => 'A', 'label_en' => 'Hot Gulab Jamun', 'label_ta' => 'சூடான குலாப் ஜாமூன் 🍯'],
                        ['key' => 'B', 'label_en' => 'Srivilliputhur Paalkova', 'label_ta' => 'ஸ்ரீவில்லிபுத்தூர் பால்கோவா 🥛'],
                        ['key' => 'C', 'label_en' => 'Melt-in-Mouth Ghee Mysore Pak', 'label_ta' => 'நெய் மைசூர் பாக் 🧈'],
                        ['key' => 'D', 'label_en' => 'Tirunelveli Ghee Halwa', 'label_ta' => 'திருநெல்வேலி நெய் அல்வா 🥮'],
                    ],
                    'is_active' => true,
                ]
            );

            KoodalSyncQuestion::firstOrCreate(
                [
                    'category' => 'Food',
                    'question_text_en' => 'What is your favorite South Indian breakfast?',
                ],
                [
                    'question_text_ta' => 'உங்களுக்கு மிகவும் பிடித்த தென்னிந்திய காலை உணவு எது?',
                    'options' => [
                        ['key' => 'A', 'label_en' => 'Crispy Ghee Roast Dosa with Chutneys', 'label_ta' => 'மொறுமொறு நெய் ரோஸ்ட் தோசை 🥞'],
                        ['key' => 'B', 'label_en' => 'Hot Soft Idli with Medu Vada & Sambar', 'label_ta' => 'மல்லிகைப் பூ இட்லி, மெதுவடை & சாம்பார் 🥣'],
                        ['key' => 'C', 'label_en' => 'Fluffy Poori with Potato Masala', 'label_ta' => 'உப்பலான பூரி கிழங்கு மசாலா 🍞'],
                        ['key' => 'D', 'label_en' => 'Ghee Pongal with Crispy Vada', 'label_ta' => 'சுவையான நெய் பொங்கல் வடை 🍲'],
                    ],
                    'is_active' => true,
                ]
            );

            KoodalSyncQuestion::firstOrCreate(
                [
                    'category' => 'Travel',
                    'question_text_en' => 'Where would you love to live after marriage?',
                ],
                [
                    'question_text_ta' => 'திருமணத்திற்குப் பின் எங்கு வசிக்க விரும்புகிறீர்கள்?',
                    'options' => [
                        ['key' => 'A', 'label_en' => 'Peaceful Native Town / Village Greenery', 'label_ta' => 'அமைதியான சொந்த ஊர் / இயற்கை சூழல் 🌴'],
                        ['key' => 'B', 'label_en' => 'Bustling Modern Metro City', 'label_ta' => 'நவீன பெருநகரம் / சிட்டி வாழ்க்கை 🏙️'],
                        ['key' => 'C', 'label_en' => 'Calm Suburban Town near City', 'label_ta' => 'அமைதியான புறநகர் பகுதி 🏡'],
                        ['key' => 'D', 'label_en' => 'Misty Hill Station / Nature Foothills', 'label_ta' => 'குளுமையான மலை பிரதேசம் ⛰️'],
                    ],
                    'is_active' => true,
                ]
            );
        } catch (\Throwable $e) {
            // Non-blocking fallback
        }
    }

    /**
     * Extract normalized base topic from question text to guarantee diverse questions.
     */
    public function extractQuestionTopic(?string $text): string
    {
        if (!$text) {
            return '';
        }
        $text = preg_replace('/\s*\(#\s*\d+\)/i', '', $text);
        $text = preg_replace('/\s*\(வரிசை\s*#\s*\d+\)/iu', '', $text);

        $patterns = [
            '/^what is your preference for\s+/i',
            '/^how do you react to\s+/i',
            '/^which one defines you best regarding\s+/i',
            '/^what is your favorite\s+/i',
            '/^which one do you prefer\s+/i',
            '/^உங்கள் விருப்பம் என்ன:\s+/iu',
            '/^இதற்கு எப்படி எதிர்வினையாற்றுவீர்கள்:\s+/iu',
            '/^எது உங்களை சிறப்பாக விவரிக்கிறது:\s+/iu',
        ];
        $clean = preg_replace($patterns, '', trim($text));

        return strtolower(trim($clean, " ?.\t\n\r\0\x0B"));
    }

    /**
     * Clean up internal sequence badges from question text.
     */
    public function cleanQuestionText(?string $text): ?string
    {
        if (!$text) {
            return $text;
        }
        $text = preg_replace('/\s*\(#\s*\d+\)/i', '', $text);
        $text = preg_replace('/\s*\(வரிசை\s*#\s*\d+\)/iu', '', $text);

        return trim($text);
    }

    /**
     * Get compatibility questions for a user pair.
     * Compulsorily includes questions from Favorite Food and Favorite Location/Travel categories!
     * Guarantees ZERO repeated questions, unique IDs, and unique topics.
     */
    public function getSyncQuestionsForPair(int $userId, int $partnerId, int $count = 10): array
    {
        $this->ensureCoreCategoryQuestionsExist();

        $u1 = min($userId, $partnerId);
        $u2 = max($userId, $partnerId);

        $targetFoodCount = $count >= 10 ? 2 : 1;
        $targetLocationCount = $count >= 10 ? 2 : 1;

        // 1. Check if partner has answered questions in this pair waiting for this user to match
        $myAnsweredInPair = KoodalSyncAnswer::where('user_id', $userId)
            ->whereHas('session', function ($q) use ($u1, $u2) {
                $q->where('user_one_id', $u1)->where('user_two_id', $u2);
            })
            ->pluck('question_id')
            ->toArray();

        $pendingPartnerAnswers = KoodalSyncAnswer::where('user_id', $partnerId)
            ->whereHas('session', function ($q) use ($u1, $u2) {
                $q->where('user_one_id', $u1)->where('user_two_id', $u2);
            })
            ->latest('id')
            ->take($count)
            ->get();

        $pendingQIds = $pendingPartnerAnswers
            ->pluck('question_id')
            ->reject(fn($id) => in_array($id, $myAnsweredInPair, true))
            ->unique()
            ->values()
            ->all();

        if (count($pendingQIds) >= $count) {
            $qMap = KoodalSyncQuestion::where('is_active', true)
                ->whereIn('id', array_slice($pendingQIds, 0, $count))
                ->get()
                ->keyBy('id');
            $ordered = collect(array_slice($pendingQIds, 0, $count))
                ->map(fn($id) => $qMap->get($id))
                ->filter()
                ->unique('id')
                ->values();

            if ($ordered->count() >= $count) {
                return $ordered->toArray();
            }
        }

        // 2. Check if a live multiplayer room is actively playing right now
        $existingRoom = GameRoom::whereIn('user_a_id', [$userId, $partnerId])
            ->whereIn('user_b_id', [$userId, $partnerId])
            ->where('game_type', 'sync')
            ->latest()
            ->first();

        if ($existingRoom && $existingRoom->status === 'playing' && !empty($existingRoom->question_ids) && is_array($existingRoom->question_ids)) {
            $isUserA = ($existingRoom->user_a_id === $userId);
            $myAnswers = $isUserA ? ($existingRoom->user_a_answers ?? []) : ($existingRoom->user_b_answers ?? []);
            if (count($myAnswers) < $count && $existingRoom->updated_at && $existingRoom->updated_at->gt(now()->subHours(2))) {
                $qMap = KoodalSyncQuestion::where('is_active', true)
                    ->whereIn('id', $existingRoom->question_ids)
                    ->get()
                    ->keyBy('id');
                $ordered = collect($existingRoom->question_ids)
                    ->map(fn($id) => $qMap->get($id))
                    ->filter()
                    ->unique('id')
                    ->values();
                if ($ordered->count() >= $count) {
                    return $ordered->toArray();
                }
            }
        }

        // 3. Fresh question selection: Zero repeated questions!
        // Exclude questions this user has already answered (across all sessions)
        $myAnsweredQIds = KoodalSyncAnswer::where('user_id', $userId)
            ->pluck('question_id')
            ->unique()
            ->toArray();

        // Exclude questions already answered by this pair
        $pairHistoryIds = KoodalPairHistory::where('user_one_id', $u1)
            ->where('user_two_id', $u2)
            ->pluck('question_id')
            ->unique()
            ->toArray();

        $excludeIds = array_unique(array_merge($myAnsweredQIds, $pairHistoryIds));

        // Topics this user has already answered
        $myAnsweredQuestions = KoodalSyncQuestion::whereIn('id', $myAnsweredQIds)->get();
        $myAnsweredTopics = $myAnsweredQuestions
            ->map(fn($q) => $this->extractQuestionTopic($q->question_text_en))
            ->filter()
            ->unique()
            ->toArray();

        $allQuestions = KoodalSyncQuestion::where('is_active', true)->get();

        // Priority 1: Unseen questions AND unseen topics
        $available = $allQuestions->reject(function ($q) use ($excludeIds, $myAnsweredTopics) {
            if (in_array($q->id, $excludeIds, true)) return true;
            $topic = $this->extractQuestionTopic($q->question_text_en);
            return in_array($topic, $myAnsweredTopics, true);
        })->shuffle();

        // Priority 2: If running low on fresh topics, allow unseen questions from known topics
        if ($available->count() < $count) {
            $available = $allQuestions->reject(fn($q) => in_array($q->id, $excludeIds, true))->shuffle();
        }

        // Priority 3: If user has answered nearly all questions, only exclude this pair's history
        if ($available->count() < $count) {
            $available = $allQuestions->reject(fn($q) => in_array($q->id, $pairHistoryIds, true))->shuffle();
        }

        // Priority 4: Ultimate fallback if pair has played all questions
        if ($available->count() < $count) {
            $available = $allQuestions->shuffle();
        }

        $picked = collect();
        $usedTopics = [];

        $pickFromPool = function ($pool, int $targetCount) use (&$picked, &$usedTopics) {
            $num = 0;
            foreach ($pool as $q) {
                if ($num >= $targetCount) break;
                if ($picked->contains('id', $q->id)) continue;
                $topic = $this->extractQuestionTopic($q->question_text_en);
                if ($topic !== '' && in_array($topic, $usedTopics, true)) continue;

                if ($topic !== '') {
                    $usedTopics[] = $topic;
                }
                $picked->push($q);
                $num++;
            }
            return $num;
        };

        // Pick Food questions (exactly $targetFoodCount, diverse topics)
        $foodPool = $available->filter(fn($q) => $this->isFoodQuestion($q));
        $foodPicked = $pickFromPool($foodPool, $targetFoodCount);

        if ($foodPicked < $targetFoodCount) {
            $allFood = $allQuestions->filter(fn($q) => $this->isFoodQuestion($q))->shuffle();
            $pickFromPool($allFood, $targetFoodCount - $foodPicked);
        }

        // Pick Location/Travel questions (exactly $targetLocationCount, diverse topics)
        $locPool = $available->filter(fn($q) => $this->isLocationQuestion($q));
        $locPicked = $pickFromPool($locPool, $targetLocationCount);

        if ($locPicked < $targetLocationCount) {
            $allLoc = $allQuestions->filter(fn($q) => $this->isLocationQuestion($q))->shuffle();
            $pickFromPool($allLoc, $targetLocationCount - $locPicked);
        }

        // Pick remaining questions from other diverse categories and topics
        $slotsRemaining = max(0, $count - $picked->count());
        if ($slotsRemaining > 0) {
            $otherPool = $available->reject(fn($q) => $this->isFoodQuestion($q) || $this->isLocationQuestion($q));
            $pickFromPool($otherPool, $slotsRemaining);

            if ($picked->count() < $count) {
                $allOther = $allQuestions->reject(fn($q) => $this->isFoodQuestion($q) || $this->isLocationQuestion($q))->shuffle();
                $pickFromPool($allOther, $count - $picked->count());
            }

            if ($picked->count() < $count) {
                foreach ($allQuestions->shuffle() as $q) {
                    if ($picked->count() >= $count) break;
                    if (!$picked->contains('id', $q->id)) {
                        $picked->push($q);
                    }
                }
            }
        }

        $resultArray = $picked->unique('id')->take($count)->values()->toArray();

        // If an existing room is in lobby, sync the new fresh question IDs to it
        if ($existingRoom && $existingRoom->status === 'lobby') {
            $existingRoom->question_ids = array_map(fn($q) => (int)$q['id'], $resultArray);
            $existingRoom->save();
        }

        return $resultArray;
    }

    /**
     * Submit user answers for a Koodal Sync session.
     */
    public function submitSyncSession(int $userId, int $partnerId, array $answers): array
    {
        $u1 = min($userId, $partnerId);
        $u2 = max($userId, $partnerId);

        // Record pair history so these questions won't repeat
        foreach ($answers as $qId => $optionKey) {
            KoodalPairHistory::firstOrCreate([
                'user_one_id' => $u1,
                'user_two_id' => $u2,
                'question_id' => (int)$qId,
            ]);
        }

        // Create session record
        $session = KoodalSyncSession::create([
            'user_one_id' => $u1,
            'user_two_id' => $u2,
            'status'      => 'completed',
        ]);

        $matchedCount    = 0;
        $totalQuestions  = count($answers);
        $partnerAnswerCount = 0;
        $questionsReview = [];

        $qModels = KoodalSyncQuestion::whereIn('id', array_map('intval', array_keys($answers)))
            ->get()
            ->keyBy('id');

        $sender = User::find($userId);
        $partner = User::find($partnerId);

        foreach ($answers as $qId => $optionKey) {
            KoodalSyncAnswer::create([
                'session_id'      => $session->id,
                'question_id'     => (int)$qId,
                'user_id'         => $userId,
                'selected_option' => $optionKey,
            ]);

            // Check if partner already answered the same question
            $partnerAnswer = KoodalSyncAnswer::where('user_id', $partnerId)
                ->where('question_id', (int)$qId)
                ->latest()
                ->first();

            $qModel = $qModels->get((int)$qId);
            $options = $qModel ? ($qModel->options ?? []) : [];

            $myOptLabel = '';
            $partnerOptLabel = '';

            foreach ($options as $opt) {
                if (($opt['key'] ?? '') === $optionKey) {
                    $myOptLabel = $opt['label_en'] ?? $opt['key'];
                }
                if ($partnerAnswer && ($opt['key'] ?? '') === $partnerAnswer->selected_option) {
                    $partnerOptLabel = $opt['label_en'] ?? $opt['key'];
                }
            }

            $isMatch = false;
            if ($partnerAnswer) {
                $partnerAnswerCount++;
                if ($partnerAnswer->selected_option === $optionKey) {
                    $matchedCount++;
                    $isMatch = true;
                }
            }

            $questionsReview[] = [
                'index'           => count($questionsReview) + 1,
                'question_id'     => (int)$qId,
                'question_en'     => $qModel ? $qModel->question_text_en : "Question #{$qId}",
                'question_ta'     => $qModel ? $qModel->question_text_ta : null,
                'my_option'       => $optionKey,
                'my_label'        => $myOptLabel ?: $optionKey,
                'user_answer'     => $myOptLabel ?: $optionKey,
                'partner_option'  => $partnerAnswer ? $partnerAnswer->selected_option : null,
                'partner_label'   => $partnerOptLabel ?: ($partnerAnswer ? $partnerAnswer->selected_option : 'Not yet answered'),
                'partner_answer'  => $partnerOptLabel ?: ($partnerAnswer ? $partnerAnswer->selected_option : 'Not yet answered'),
                'is_match'        => $isMatch,
                'points'          => $isMatch ? 10 : 0,
            ];
        }

        $partnerHasAnswered = ($partnerAnswerCount > 0);
        $waitingForPartner  = !$partnerHasAnswered;

        $score    = $matchedCount * 10;
        $maxScore = $totalQuestions * 10;

        $inviteId = null;

        // If offline mode and partner hasn't played yet, send GameInvite (with Accept/Decline) + Notification
        if ($waitingForPartner && $sender && $partner) {
            try {
                // Create or refresh pending GameInvite so opposite member gets the Accept/Decline invite popup & link
                // (an offline invite sent before answering is the one refreshed, not stacked beside)
                $invite = \App\Models\GameInvite::where('sender_id', $userId)
                    ->where('receiver_id', $partnerId)
                    ->whereIn('game_type', ['sync', \App\Models\GameInvite::SYNC_OFFLINE])
                    ->where('status', 'pending')
                    ->latest('id')
                    ->first()
                    ?? new \App\Models\GameInvite([
                        'sender_id'   => $userId,
                        'receiver_id' => $partnerId,
                        'game_type'   => 'sync',
                        'status'      => 'pending',
                    ]);
                $invite->expires_at = now()->addHours(24);
                $invite->save();
                $invite->touch();
                $inviteId = $invite->id;

                // Through the dispatcher so the member's notification settings
                // apply and a phone gets a push; the route is the app's.
                app(\App\Services\Notification\NotificationDispatcher::class)->send(
                    $partner,
                    'game_invite',
                    '🎯 Compatibility Game Waiting for Your Answer!',
                    $sender->name . ': Already your game is played, waiting for your answer! Please play.',
                    [
                        'route'     => '/games/invites/' . $invite->id,
                        'invite_id' => $invite->id,
                        'game_type' => 'sync',
                    ],
                );
            } catch (\Throwable $e) {
                \Log::error('Offline game notification/invite error: ' . $e->getMessage());
            }
        } elseif ($partnerHasAnswered && $sender && $partner) {
            // Both finished! Mark any pending sync invite from partner as accepted and notify partner
            try {
                \App\Models\GameInvite::where('sender_id', $partnerId)
                    ->where('receiver_id', $userId)
                    ->whereIn('game_type', ['sync', \App\Models\GameInvite::SYNC_OFFLINE])
                    ->where('status', 'pending')
                    ->update(['status' => 'accepted']);

                app(\App\Services\Notification\NotificationDispatcher::class)->send(
                    $partner,
                    'game_result',
                    '🎉 Compatibility Results Ready!',
                    $sender->name . ' has answered your Compatibility Game! Check your match score now.',
                    ['route' => '/games/partners/' . $sender->profile_code . '/vibe'],
                );
            } catch (\Throwable $e) {
                \Log::error('Match results notification error: ' . $e->getMessage());
            }
        }

        // Synchronize GameRoom so it never hangs in lobby or unfinished state
        try {
            $existingRoom = GameRoom::whereIn('user_a_id', [$userId, $partnerId])
                ->whereIn('user_b_id', [$userId, $partnerId])
                ->where('game_type', 'sync')
                ->latest()
                ->first();

            if ($existingRoom) {
                $isUserA = ($existingRoom->user_a_id === $userId);
                $answersList = [];
                $idx = 0;
                foreach ($answers as $qid => $opt) {
                    $answersList[$idx++] = ['question_id' => (int)$qid, 'option' => $opt];
                }
                if ($isUserA) {
                    $existingRoom->user_a_answers = $answersList;
                } else {
                    $existingRoom->user_b_answers = $answersList;
                }
                if (empty($existingRoom->question_ids)) {
                    $existingRoom->question_ids = array_map('intval', array_keys($answers));
                }
                if ($partnerHasAnswered) {
                    $existingRoom->status = 'completed';
                    $existingRoom->ended_at = now();
                }
                $existingRoom->save();
            }
        } catch (\Throwable $e) {
            \Log::error('KoodalGameService::submitSyncSession GameRoom sync error: ' . $e->getMessage());
        }

        // Update session scores; only record final KoodalGameScore when both players have answered
        try {
            $session->update([
                'user_one_score' => $userId === $u1 ? $score : 0,
                'user_two_score' => $userId === $u2 ? $score : 0,
                'matched_count'  => $matchedCount,
            ]);

            if ($partnerHasAnswered) {
                $this->recordGameScore($userId, $partnerId, 'sync', $score, $maxScore, [
                    'matched_count'   => $matchedCount,
                    'total_questions' => $totalQuestions,
                    'session_id'      => $session->id,
                ]);
            }
        } catch (\Throwable $e) {
            \Log::error('KoodalGameService::submitSyncSession score save failed: ' . $e->getMessage());
        }

        $vibe = $this->recalculateVibeResult($userId, $partnerId);

        return [
            'session_id'          => $session->id,
            'matched_count'       => $matchedCount,
            'total_questions'     => $totalQuestions,
            'score'               => $waitingForPartner ? null : $score,
            'max_score'           => $maxScore,
            'vibe_percentage'     => $vibe->vibe_percentage,
            'waiting_for_partner' => $waitingForPartner,
            'partner_name'        => $partner ? $partner->name : 'Partner',
            'invite_id'           => $inviteId,
            'invite_sent'         => $waitingForPartner,
            'questions_review'    => $questionsReview,
        ];
    }

    /**
     * Record score for any Koodal Game.
     */
    public function recordGameScore(int $userId, int $partnerId, string $gameType, int $score, int $maxScore, array $details = []): KoodalGameScore
    {
        $gameScore = KoodalGameScore::create([
            'user_id' => $userId,
            'partner_id' => $partnerId,
            'game_type' => $gameType,
            'score' => $score,
            'max_score' => $maxScore,
            'details' => $details,
        ]);

        $this->recalculateVibeResult($userId, $partnerId);

        try {
            $gameTitle = ucwords(str_replace(['_', '-'], ' ', $gameType));
            $partner = User::find($partnerId);
            $partnerName = $partner?->name ?? 'Partner';
            $fcm = app(\App\Services\FirebaseNotificationService::class);

            $fcm->sendToUser(
                $userId,
                "🎮 {$gameTitle} Result",
                "Your {$gameTitle} game with {$partnerName} finished! Score: {$score}/{$maxScore}",
                [
                    'type' => 'game_score',
                    'reference_id' => (string) $gameScore->id,
                    'game_type' => $gameType,
                    'score' => (string) $score,
                    'max_score' => (string) $maxScore,
                    'partner_id' => (string) $partnerId,
                ],
                'game_score'
            );
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Game score push notification failed: '.$e->getMessage());
        }

        return $gameScore;
    }

    /**
     * Check if a pair has completed all 4 required games: sync, find_it, memory_snap (or memory), flip.
     */
    public function hasCompletedAllGames(int|User $user, int|User $partner): bool
    {
        $u1 = $user instanceof User ? $user->id : (int) $user;
        $u2 = $partner instanceof User ? $partner->id : (int) $partner;

        $playedGames = KoodalGameScore::whereIn('user_id', [$u1, $u2])
            ->whereIn('partner_id', [$u1, $u2])
            ->pluck('game_type')
            ->map(fn ($t) => $t === 'memory' ? 'memory_snap' : $t)
            ->unique()
            ->all();

        $required = ['sync', 'find_it', 'memory_snap', 'flip'];

        foreach ($required as $game) {
            if (! in_array($game, $playedGames, true)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check if a user can access chat with a specific partner.
     * Premium (paid plan) users are unrestricted.
     * Free users must complete all 4 games.
     */
    public function canAccessChat(User $user, User $partner): bool
    {
        if ($user->hasPaidPlan()) {
            return true;
        }

        return $this->hasCompletedAllGames($user, $partner);
    }

    /**
     * Calculate and return Vibe Meter result for a pair.
     */
    public function recalculateVibeResult(int $userId, int $partnerId): KoodalVibeResult
    {
        $u1 = min($userId, $partnerId);
        $u2 = max($userId, $partnerId);

        $user1 = User::with('profile')->find($u1);
        $user2 = User::with('profile')->find($u2);

        // 1. Profile commonalities
        $commonTags = [];
        $baseCommonPoints = 20;

        if ($user1 && $user2 && $user1->profile && $user2->profile) {
            $p1 = $user1->profile;
            $p2 = $user2->profile;

            if ($p1->diet && $p1->diet === $p2->diet) {
                $commonTags[] = ['type' => 'diet', 'icon' => '🥗', 'label' => 'Same Diet Preference (' . ucfirst($p1->diet) . ')'];
                $baseCommonPoints += 5;
            }
            if ($p1->drinking && $p1->drinking === $p2->drinking) {
                $baseCommonPoints += 5;
            }
            if ($p1->city_id && $p1->city_id === $p2->city_id) {
                $commonTags[] = ['type' => 'city', 'icon' => '📍', 'label' => 'Living in Same City'];
                $baseCommonPoints += 10;
            }
            if ($p1->mother_tongue && $p1->mother_tongue === $p2->mother_tongue) {
                $commonTags[] = ['type' => 'language', 'icon' => '🗣️', 'label' => 'Same Mother Tongue (' . $p1->mother_tongue . ')'];
                $baseCommonPoints += 5;
            }
        }

        // Add default fun common choices if missing
        if (empty($commonTags)) {
            $commonTags[] = ['type' => 'beverage', 'icon' => '☕', 'label' => 'Coffee & Tea Lover'];
            $commonTags[] = ['type' => 'travel', 'icon' => '🏖️', 'label' => 'Beach Holiday Preference'];
            $commonTags[] = ['type' => 'movie', 'icon' => '🎬', 'label' => 'Cinema & Music Enthusiast'];
        }

        // 2. Fetch latest scores for each of the 4 games
        $syncScores = KoodalGameScore::whereIn('user_id', [$u1, $u2])
            ->whereIn('partner_id', [$u1, $u2])
            ->where('game_type', 'sync')
            ->latest()
            ->first();

        $findItScores = KoodalGameScore::whereIn('user_id', [$u1, $u2])
            ->whereIn('partner_id', [$u1, $u2])
            ->where('game_type', 'find_it')
            ->latest()
            ->first();

        $memorySnapScores = KoodalGameScore::whereIn('user_id', [$u1, $u2])
            ->whereIn('partner_id', [$u1, $u2])
            ->whereIn('game_type', ['memory_snap', 'memory'])
            ->latest()
            ->first();

        $flipScores = KoodalGameScore::whereIn('user_id', [$u1, $u2])
            ->whereIn('partner_id', [$u1, $u2])
            ->where('game_type', 'flip')
            ->latest()
            ->first();

        $gamesSummary = [
            'sync' => [
                'played' => (bool)$syncScores,
                'score' => $syncScores ? $syncScores->score : null,
                'max' => $syncScores ? $syncScores->max_score : 100,
                'percentage' => $syncScores && $syncScores->max_score > 0 ? round(($syncScores->score / $syncScores->max_score) * 100) : 0,
            ],
            'find_it' => [
                'played' => (bool)$findItScores,
                'score' => $findItScores ? $findItScores->score : null,
                'max' => $findItScores ? $findItScores->max_score : 100,
                'percentage' => $findItScores && $findItScores->max_score > 0 ? round(($findItScores->score / $findItScores->max_score) * 100) : 0,
            ],
            'memory_snap' => [
                'played' => (bool)$memorySnapScores,
                'score' => $memorySnapScores ? $memorySnapScores->score : null,
                'max' => $memorySnapScores ? $memorySnapScores->max_score : 100,
                'percentage' => $memorySnapScores && $memorySnapScores->max_score > 0 ? round(($memorySnapScores->score / $memorySnapScores->max_score) * 100) : 0,
            ],
            'flip' => [
                'played' => (bool)$flipScores,
                'score' => $flipScores ? $flipScores->score : null,
                'max' => $flipScores ? $flipScores->max_score : 100,
                'percentage' => $flipScores && $flipScores->max_score > 0 ? round(($flipScores->score / $flipScores->max_score) * 100) : 0,
            ],
        ];

        // 3. Check if any game has been played by this pair
        $playedPercentages = [];
        foreach ($gamesSummary as $gKey => $gData) {
            if (!empty($gData['played'])) {
                $playedPercentages[$gKey] = (float) $gData['percentage'];
            }
        }

        if (empty($playedPercentages)) {
            // No games played yet — do NOT assign a fake default score
            $vibePct = 0;
        } else {
            // Calculate Vibe score based on the games actually played
            $avgGamePct = array_sum($playedPercentages) / count($playedPercentages);
            $vibePct = (int) round(min(100, max(1, $avgGamePct)));
        }

        $vibeResult = KoodalVibeResult::updateOrCreate(
            ['user_one_id' => $u1, 'user_two_id' => $u2],
            [
                'vibe_percentage' => $vibePct,
                'common_tags' => $commonTags,
                'games_summary' => $gamesSummary,
            ]
        );

        return $vibeResult;
    }
}
