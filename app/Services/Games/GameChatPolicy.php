<?php

namespace App\Services\Games;

use App\Models\User;
use App\Support\ApiResponse;
use Illuminate\Http\Exceptions\HttpResponseException;

/**
 * One copy of the in-game chat rule. The website has it pasted into three
 * controllers using a `str_contains` match, which lets "wow, call me on 98…"
 * through the free-plan filter; this one compares the whole normalised message
 * against the chips.
 */
class GameChatPolicy
{
    /**
     * @return string the cleaned message, safe to store
     *
     * @throws HttpResponseException 403 paywall or 422 for a blocked word
     */
    public function authorize(User $user, string $message): string
    {
        $text = trim(preg_replace('/\s+/u', ' ', $message));
        $max = (int) config('game.chat.max_length', 250);
        $text = mb_substr($text, 0, $max);

        if ($text === '') {
            throw new HttpResponseException(ApiResponse::error('Message cannot be empty.', [], 422));
        }

        if (! $user->hasPaidPlan() && ! $this->isChip($text)) {
            throw new HttpResponseException(ApiResponse::upgradeRequired(
                'can_chat',
                'Free members can only send quick replies (Super, Good, Wow, Excellent). Upgrade to chat freely.',
            ));
        }

        foreach (config('game.chat.blocked_words', []) as $word) {
            if ($word !== '' && stripos($text, $word) !== false) {
                throw new HttpResponseException(ApiResponse::error('Message contains inappropriate language.', [], 422));
            }
        }

        return $text;
    }

    public function isChip(string $text): bool
    {
        $clean = mb_strtolower(trim(preg_replace('/[^\p{L}\p{N}\s]/u', '', $text)));

        return in_array($clean, config('game.chat.chips', []), true);
    }
}
