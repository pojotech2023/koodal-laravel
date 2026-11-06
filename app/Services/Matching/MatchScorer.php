<?php

namespace App\Services\Matching;

use App\Models\UserProfile;

/**
 * Compatibility scoring. Lives here and nowhere else — weights are retuned
 * often, and a client-side implementation would need an App Store release
 * every time product changed its mind.
 *
 * Two decisions worth knowing about:
 *
 *   • The score is computed in BOTH directions and averaged. A candidate who
 *     fits you perfectly but for whom you fail their stated preferences is a
 *     bad recommendation — it produces an interest that gets declined, which
 *     is worse for both people than never having seen the profile.
 *
 *   • An empty preference scores 1.0, not 0.0. Most users fill in three or
 *     four preferences and leave the rest blank; treating blank as a mismatch
 *     would flatten every score toward zero and make ranking meaningless.
 */
class MatchScorer
{
    /** @var Criterion[] */
    protected array $criteria;

    public function __construct(CriterionRegistry $registry)
    {
        $this->criteria = $registry->all();
    }

    public function score(UserProfile $viewer, UserProfile $candidate): MatchScore
    {
        $forward = $this->directional($viewer, $candidate);
        $reverse = $this->directional($candidate, $viewer);

        $base = ($forward['total'] + $reverse['total']) / 2;
        $value = (int) round(min(100, $base * $this->qualityMultiplier($candidate)));

        return new MatchScore(
            value: max(0, $value),
            // Only the forward breakdown is exposed: it answers "why am I being
            // shown this person", which is what the profile card renders.
            breakdown: $this->labels($forward['scores']),
        );
    }

    /**
     * @return array{total: float, scores: array<string, float>}
     */
    protected function directional(UserProfile $viewer, UserProfile $candidate): array
    {
        $total = 0.0;
        $scores = [];

        foreach ($this->criteria as $key => $criterion) {
            $weight = (float) config("matrimony.matching.weights.{$key}", 0);

            if ($weight <= 0) {
                continue;
            }

            $score = $criterion->score($viewer, $candidate);   // 0.0 – 1.0
            $scores[$key] = $score;
            $total += $score * $weight;
        }

        return ['total' => $total, 'scores' => $scores];
    }

    /**
     * A perfect match who abandoned the account is not a match. Verification
     * and recent activity are worth more to the user than another two points
     * of community alignment.
     */
    protected function qualityMultiplier(UserProfile $candidate): float
    {
        $m = config('matrimony.matching.multipliers');
        $multiplier = 1.0;

        if ($candidate->is_verified) {
            $multiplier *= $m['verified'];
        }

        $lastActive = $candidate->user?->last_active_at;

        if ($lastActive?->gt(now()->subDays(7))) {
            $multiplier *= $m['active_within_7_days'];
        } elseif ($lastActive?->lt(now()->subDays(90))) {
            $multiplier *= $m['dormant_over_90_days'];
        }

        // Completeness scales smoothly between the configured bounds rather
        // than stepping, so finishing a profile always helps a little.
        $completeness = $candidate->completion_percentage / 100;
        $multiplier *= $m['completeness_min'] + ($m['completeness_max'] - $m['completeness_min']) * $completeness;

        return $multiplier;
    }

    /**
     * Human-readable reasons. "Same community · Same city" measurably beats a
     * bare percentage at getting people to send an interest.
     *
     * @param  array<string, float>  $scores
     * @return array<int, string>
     */
    protected function labels(array $scores): array
    {
        return collect($scores)
            ->filter(fn (float $score) => $score >= 0.9)
            ->keys()
            ->map(fn (string $key) => $this->criteria[$key]->label())
            ->values()
            ->take(3)
            ->all();
    }
}
