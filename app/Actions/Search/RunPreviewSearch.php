<?php

namespace App\Actions\Search;

use App\Services\Search\PreviewResult;
use App\Services\Search\SearchFilters;
use App\Services\Search\SearchService;
use Illuminate\Support\Facades\Cache;

/**
 * The signed-out search — `/browse`. RunSearch's counterpart for a visitor who
 * has no account, and therefore no gender to invert, no blocks to honour, no
 * plan to gate against and no viewer state to hydrate.
 *
 * It is a separate Action rather than a `?User $viewer` parameter on RunSearch
 * for a reason worth stating: every guard RunSearch applies is defined in terms
 * of the viewer, so a nullable viewer would turn each one into a branch and the
 * *absence* of a guard would become the default in a class whose whole job is
 * applying them. Two Actions with two explicit rule sets is the safer shape
 * when one of them serves the public internet.
 *
 * --- Three deliberate narrowings --------------------------------------------
 *
 * **Filters are stripped, not rejected.** RunSearch throws
 * FeatureLimitException at a member who sends a filter their plan does not
 * include, because that member can upgrade. A guest cannot, so the same
 * response would be a dead end — and an unrecognised key here is far more
 * likely to be a crawler appending tracking parameters than a bypass attempt.
 *
 * **No paging.** One page of `search.preview.limit`, and no `page` parameter
 * exists to send. Deep offsets by an anonymous client are the enumeration
 * pattern SearchService already caps for members; there is no reason to open
 * the door at all for someone who has not signed up.
 *
 * **The count is rounded down and cached.** See countMatches() below.
 */
class RunPreviewSearch
{
    /**
     * What a signed-out visitor may filter on.
     *
     * Single-value keys only — the guest form is a landing-page form, not the
     * member filter panel, and the `*_ids` array variants exist to serve a
     * saved search that a guest cannot have. Keeping arrays out also keeps the
     * cache key below bounded, which matters on a page bots will hit.
     */
    protected const PREVIEW_FILTERS = [
        'age_min', 'age_max',
        'religion_id', 'mother_tongue_id', 'marital_status_id',
        'country_id', 'state_id', 'city_id',
        'has_photo',
    ];

    public function __construct(
        protected SearchService $search,
        protected SearchFilters $filters,
    ) {}

    /**
     * @param  string  $lookingFor  'male' or 'female' — the gender being searched
     *                              FOR, already validated by PreviewSearchRequest.
     *                              SearchService::previewQuery() documents why this
     *                              is the one place gender is accepted as input.
     */
    public function __invoke(string $lookingFor, array $criteria): PreviewResult
    {
        $criteria = array_intersect_key($criteria, array_flip(self::PREVIEW_FILTERS));

        $query = $this->search->previewQuery($lookingFor);
        $this->filters->apply($query, $criteria);

        $profiles = $query
            // Fixed order, and no sort control on the page. "Recommended" needs
            // a match_scores row keyed to a viewer that does not exist, and the
            // remaining sorts would only give an anonymous client a second axis
            // to page through the member base with. Completeness first is also
            // simply the best shop window: full profiles show well.
            ->orderByDesc('user_profiles.completion_percentage')
            ->orderByDesc('users.last_active_at')
            ->orderBy('user_profiles.user_id')
            ->limit(config('matrimony.search.preview.limit'))
            ->get();

        [$approxTotal, $hasMatches] = $this->countMatches($lookingFor, $criteria);

        return new PreviewResult($profiles, $approxTotal, $hasMatches, $criteria);
    }

    // --- Internals ---------------------------------------------------------

    /**
     * How many members match — rounded down to a bucket, never exact.
     *
     * The count spans the whole discoverable set, not just the publicly
     * visible profiles above, and that is the page's entire value: four cards
     * read as an empty product, "1,000+ match, here are the four who publish
     * publicly" reads as a busy one. It discloses nothing about an individual
     * *provided* it is not exact — narrow the filters far enough and a precise
     * "1" identifies a person, which is why config's `count_buckets` exists and
     * why anything below the smallest bucket is returned as null (the view
     * prints no number at all) rather than as a small honest integer.
     *
     * Cached because this is a public URL that crawlers and bots will hit with
     * repeated filter combinations, and because a bucketed figure is stable by
     * construction — a five-minute-old "1,000+" is not stale in any sense a
     * visitor could notice.
     *
     * @return array{0: int|null, 1: bool} [bucketed total, anything matched at all]
     */
    protected function countMatches(string $lookingFor, array $criteria): array
    {
        ksort($criteria);

        $key = 'search:preview:count:'.md5($lookingFor.'|'.json_encode($criteria));

        $exact = Cache::remember(
            $key,
            config('matrimony.search.preview.cache_ttl'),
            function () use ($lookingFor, $criteria) {
                $query = $this->search->previewCountQuery($lookingFor);
                $this->filters->apply($query, $criteria);

                // previewCountQuery carries no eager loads and no ORDER BY —
                // it exists only to be counted — so this is a single
                // COUNT(*) over the join, not a hydrated result set.
                return $query->count();
            },
        );

        return [$this->bucket($exact), $exact > 0];
    }

    /** Largest bucket not exceeding $exact, or null when even the first is too big. */
    protected function bucket(int $exact): ?int
    {
        $bucket = null;

        foreach (config('matrimony.search.preview.count_buckets') as $candidate) {
            if ($exact < $candidate) {
                break;
            }

            $bucket = $candidate;
        }

        return $bucket;
    }
}
