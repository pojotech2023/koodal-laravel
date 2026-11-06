<?php

namespace App\Services;

use App\Models\GameMatch;
use App\Models\MatchClaim;
use App\Models\MatchPlayer;

class ScoringService
{
    /**
     * Calculate points awarded for a correct claim.
     */
    public static function calculateCorrectPoints(GameMatch $match, MatchPlayer $player): int
    {
        $base = config('game.scoring.correct_claim', 100);

        // Speed bonus
        $timeLimit = $match->puzzle->time_limit_seconds ?? 180;
        $secondsLeft = 0;
        if ($match->ends_at) {
            $secondsLeft = max(0, now()->diffInSeconds($match->ends_at, false));
        }
        $speedBonus = (int) round(config('game.scoring.max_speed_bonus', 50) * ($secondsLeft / max(1, $timeLimit)));

        // Streak bonus (+25 if previous claim by this user in this match was correct)
        $lastClaim = MatchClaim::where('match_id', $match->id)
            ->where('user_id', $player->user_id)
            ->latest('id')
            ->first();

        $streakBonus = 0;
        if ($lastClaim && $lastClaim->is_correct) {
            $streakBonus = config('game.scoring.streak_bonus', 25);
        }

        return $base + $speedBonus + $streakBonus;
    }

    /**
     * Calculate penalty points for a wrong click.
     */
    public static function getWrongPenalty(): int
    {
        return config('game.scoring.wrong_claim_penalty', -20);
    }

    /**
     * Determine winner according to score and tie-breaker rules.
     */
    public static function determineWinner(GameMatch $match): ?int
    {
        $players = MatchPlayer::where('match_id', $match->id)->get();

        if ($players->count() < 2) {
            return $players->first()?->user_id;
        }

        $p1 = $players->first();
        $p2 = $players->last();

        // 1. Higher total score
        if ($p1->score > $p2->score) {
            return $p1->user_id;
        }
        if ($p2->score > $p1->score) {
            return $p2->user_id;
        }

        // 2. Tie-breaker 1: More differences found (correct_count)
        if ($p1->correct_count > $p2->correct_count) {
            return $p1->user_id;
        }
        if ($p2->correct_count > $p1->correct_count) {
            return $p2->user_id;
        }

        // 3. Tie-breaker 2: Earlier last_claim_at
        if ($p1->last_claim_at && $p2->last_claim_at) {
            if ($p1->last_claim_at->lt($p2->last_claim_at)) {
                return $p1->user_id;
            }
            if ($p2->last_claim_at->lt($p1->last_claim_at)) {
                return $p2->user_id;
            }
        } elseif ($p1->last_claim_at) {
            return $p1->user_id;
        } elseif ($p2->last_claim_at) {
            return $p2->user_id;
        }

        // 4. Else draw
        return null;
    }
}
