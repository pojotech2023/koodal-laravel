<?php

namespace App\Services;

use App\Events\MatchUpdatedEvent;
use App\Models\GameMatch;
use App\Models\MatchClaim;
use App\Models\MatchPlayer;
use App\Models\PuzzleDifference;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

class MatchService
{
    /**
     * Attempt to claim a hotspot position (x_pct, y_pct) for a player.
     * Uses DB transaction with lockForUpdate to ensure server-authoritative concurrency safety.
     */
    public function claimSpot(GameMatch $match, User $user, float $xPct, float $yPct): array
    {
        return DB::transaction(function () use ($match, $user, $xPct, $yPct) {
            // Lock match record for update to serialize concurrent claims
            $lockedMatch = GameMatch::where('id', $match->id)->lockForUpdate()->first();

            if ($lockedMatch->status !== 'playing') {
                return ['success' => false, 'reason' => 'match_not_playing'];
            }

            if ($lockedMatch->started_at && now()->lt($lockedMatch->started_at)) {
                return ['success' => false, 'reason' => 'not_started'];
            }

            if ($lockedMatch->ends_at && now()->gte($lockedMatch->ends_at)) {
                $this->finishMatch($lockedMatch);
                return ['success' => false, 'reason' => 'match_ended'];
            }

            $player = MatchPlayer::where('match_id', $lockedMatch->id)
                ->where('user_id', $user->id)
                ->lockForUpdate()
                ->first();

            if (!$player) {
                return ['success' => false, 'reason' => 'not_participant'];
            }

            // Enforce 2-second wrong click cooldown
            $cooldownSecs = config('game.scoring.wrong_claim_cooldown_seconds', 2);
            if ($player->last_claim_at) {
                // Carbon 3 diffs are signed ($b - $a): measured from the last
                // claim forward, so it grows from 0. The other way round it is
                // negative and the cooldown never ends.
                $secsSince = $player->last_claim_at->diffInSeconds(now());
                $lastClaimWasWrong = MatchClaim::where('match_id', $lockedMatch->id)
                    ->where('user_id', $user->id)
                    ->latest('id')
                    ->first()?->is_correct === false;

                if ($lastClaimWasWrong && $secsSince < $cooldownSecs) {
                    return ['success' => false, 'reason' => 'cooldown', 'cooldown_remaining' => (int) ceil($cooldownSecs - $secsSince)];
                }
            }

            $puzzle = $lockedMatch->puzzle;
            $aspectRatio = ($puzzle->height > 0 && $puzzle->width > 0) ? ($puzzle->height / $puzzle->width) : 1.0;

            // Fetch unclaimed differences for this puzzle
            $claimedDiffIds = MatchClaim::where('match_id', $lockedMatch->id)
                ->whereNotNull('difference_id')
                ->pluck('difference_id')
                ->toArray();

            $unclaimedDiffs = PuzzleDifference::where('puzzle_id', $puzzle->id)
                ->whereNotIn('id', $claimedDiffIds)
                ->get();

            $matchedDiff = null;

            foreach ($unclaimedDiffs as $diff) {
                // Aspect-ratio-corrected distance in percentage space
                $dx = $xPct - $diff->x_pct;
                $dy = ($yPct - $diff->y_pct) * $aspectRatio;
                $dist = sqrt($dx * $dx + $dy * $dy);

                if ($dist <= $diff->radius_pct) {
                    $matchedDiff = $diff;
                    break;
                }
            }

            if ($matchedDiff) {
                try {
                    $points = ScoringService::calculateCorrectPoints($lockedMatch, $player);

                    $claim = MatchClaim::create([
                        'match_id' => $lockedMatch->id,
                        'user_id' => $user->id,
                        'difference_id' => $matchedDiff->id,
                        'x_pct' => $xPct,
                        'y_pct' => $yPct,
                        'is_correct' => true,
                        'points' => $points,
                    ]);

                    $player->increment('correct_count');
                    $player->increment('score', $points);
                    $player->update(['last_claim_at' => now()]);

                    // Check if all differences found
                    $totalFound = MatchClaim::where('match_id', $lockedMatch->id)
                        ->whereNotNull('difference_id')
                        ->count();

                    if ($totalFound >= $puzzle->differences()->count()) {
                        $this->finishMatch($lockedMatch);
                    }

                    $this->broadcastUpdate($lockedMatch);

                    return [
                        'success' => true,
                        'is_correct' => true,
                        'difference' => $matchedDiff,
                        'claim' => $claim,
                        'points' => $points,
                    ];
                } catch (QueryException $e) {
                    // Unique constraint [match_id, difference_id] failed => already claimed by opponent!
                    return ['success' => false, 'reason' => 'already_claimed_by_opponent'];
                }
            }

            // Wrong click
            $penalty = ScoringService::getWrongPenalty(); // e.g. -20
            $claim = MatchClaim::create([
                'match_id' => $lockedMatch->id,
                'user_id' => $user->id,
                'difference_id' => null,
                'x_pct' => $xPct,
                'y_pct' => $yPct,
                'is_correct' => false,
                'points' => $penalty,
            ]);

            $player->increment('wrong_count');
            $newScore = max(0, $player->score + $penalty);
            $player->update([
                'score' => $newScore,
                'last_claim_at' => now(),
            ]);

            $this->broadcastUpdate($lockedMatch);

            return [
                'success' => true,
                'is_correct' => false,
                'penalty' => $penalty,
                'cooldown_seconds' => $cooldownSecs,
            ];
        });
    }

    /**
     * Mark match ready and start playing if both ready.
     */
    public function toggleReady(GameMatch $match, User $user): GameMatch
    {
        return DB::transaction(function () use ($match, $user) {
            $lockedMatch = GameMatch::where('id', $match->id)->lockForUpdate()->first();

            // Ready is a lobby gesture. Once the round is running (or over) a
            // late tap must not reset the clock or reopen a finished match.
            if (! in_array($lockedMatch->status, ['waiting', 'ready'], true)) {
                return $lockedMatch;
            }

            $player = MatchPlayer::where('match_id', $lockedMatch->id)
                ->where('user_id', $user->id)
                ->firstOrFail();

            $newStatus = ($player->status === 'ready') ? 'joined' : 'ready';
            $player->update(['status' => $newStatus]);

            $allPlayers = MatchPlayer::where('match_id', $lockedMatch->id)->get();

            if ($allPlayers->count() === 2 && $allPlayers->every(fn($p) => $p->status === 'ready')) {
                $timeLimit = $lockedMatch->puzzle->time_limit_seconds ?? 180;
                $startedAt = now()->addSeconds(3);
                $endsAt = (clone $startedAt)->addSeconds($timeLimit);

                $lockedMatch->update([
                    'status' => 'playing',
                    'started_at' => $startedAt,
                    'ends_at' => $endsAt,
                ]);
            }

            $this->broadcastUpdate($lockedMatch);

            return $lockedMatch;
        });
    }

    /**
     * Complete match and declare winner.
     */
    public function finishMatch(GameMatch $match): GameMatch
    {
        if ($match->status === 'finished') {
            return $match;
        }

        $winnerId = ScoringService::determineWinner($match);

        $match->update([
            'status' => 'finished',
            'finished_at' => now(),
            'winner_id' => $winnerId,
        ]);

        $this->broadcastUpdate($match);

        return $match;
    }

    /**
     * Broadcast match state change if Reverb driver is active.
     */
    private function broadcastUpdate(GameMatch $match): void
    {
        if (config('game.driver') === 'reverb') {
            event(new MatchUpdatedEvent($match));
        }
    }
}
