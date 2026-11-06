<?php

namespace App\Services\Matching;

use App\Models\UserProfile;

/**
 * One scoring dimension. Adding a criterion is a new class plus a weight in
 * config/matrimony.php — MatchScorer never changes.
 *
 * This is the seam the brief asks for: the matching service must be
 * improvable without touching the mobile app or the website.
 */
interface Criterion
{
    /** 0.0 (no fit) to 1.0 (perfect fit). Never outside that range. */
    public function score(UserProfile $viewer, UserProfile $candidate): float;

    /** Shown on the profile card when this criterion scores highly. */
    public function label(): string;
}
