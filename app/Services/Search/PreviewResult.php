<?php

namespace App\Services\Search;

use App\Models\UserProfile;
use Illuminate\Support\Collection;

/**
 * What the signed-out `/browse` page has to render, in one object.
 *
 * A three-key array would have done the job and would have drifted: the view
 * needs the profiles, the rounded population figure and the distinction
 * between "we found nobody" and "we found people but will not put a number on
 * it", and those three only make sense read together. The rounding itself
 * happens in RunPreviewSearch — by the time a value reaches here it is already
 * safe to print.
 *
 * @property-read Collection<int, UserProfile> $profiles
 */
class PreviewResult
{
    public function __construct(
        /** The publicly visible profiles — at most `search.preview.limit` of them. */
        public readonly Collection $profiles,

        /**
         * Matching members, rounded DOWN to a bucket, or null when the exact
         * figure fell below the smallest bucket. Null is not zero: see
         * `hasMatches()`.
         */
        public readonly ?int $approxTotal,

        /** Whether anything matched at all — the one bit `approxTotal` cannot carry. */
        public readonly bool $hasMatches,

        /** Echoed back so the form re-renders with what the visitor chose. */
        public readonly array $criteria,
    ) {}

    /** "1,000+", or null when the count is deliberately not stated. */
    public function approxLabel(): ?string
    {
        return $this->approxTotal === null ? null : number_format($this->approxTotal).'+';
    }
}
