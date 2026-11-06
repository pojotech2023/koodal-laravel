<?php

namespace App\Services\Games;

use App\Models\KoodalVibeResult;
use App\Models\User;

/**
 * The game bars on the Vibe Meter page — one per game, from the pair's
 * recorded results — shared by the website and the API so the two cannot
 * disagree on a percentage.
 *
 * Rasi Porutham and Partner Preference used to be bars here too, computed
 * from a rough religion / community / preference check. They are reports of
 * their own now (CompatibilityReports), shown beside the Vibe Meter.
 */
class VibeReport
{
    /** @return array<int, array{label: string, score: int, played: bool}> */
    public function compatibilityBars(User $user, User $partner, KoodalVibeResult $vibeResult): array
    {
        $summary = $vibeResult->games_summary ?? [];
        $score = fn (string $key) => ! empty($summary[$key]['played']) ? (int) round($summary[$key]['score'] ?? 0) : null;

        // Memory is Memory Snap; the older solo Memory game counts if that's all they played.
        $memory = $score('memory_snap') ?? $score('memory');

        return [
            ['label' => 'Compatibility', 'score' => $score('sync') ?? 0, 'played' => $score('sync') !== null],
            ['label' => 'Memory',        'score' => $memory ?? 0,        'played' => $memory !== null],
            ['label' => 'Observation',   'score' => $score('find_it') ?? 0, 'played' => $score('find_it') !== null],
            ['label' => 'Team Work',     'score' => $score('flip') ?? 0, 'played' => $score('flip') !== null],
        ];
    }
}
