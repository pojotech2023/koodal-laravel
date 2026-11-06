<?php

namespace App\Actions\Search;

use App\Exceptions\FeatureLimitException;
use App\Models\User;
use App\Services\Membership\FeatureGate;
use App\Services\Profile\ViewerState;
use App\Services\Search\SearchService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Search with the plan gate applied.
 *
 * The gate lives in an Action rather than the controller because that is where
 * the membership spec puts every gate — the website, the app and any future
 * client then get the same rule without each re-implementing it.
 *
 * Advanced filters are REJECTED, not silently dropped. A client renders locked
 * filters as disabled and never sends them, so a request carrying one is either
 * an old build or a bypass attempt; quietly returning unfiltered results would
 * look exactly like a broken filter and generate a support ticket instead of an
 * upgrade prompt.
 */
class RunSearch
{
    /**
     * What a free member may filter on.
     *
     * Age, height, faith, community, mother tongue, marital status and location
     * are the fields a first-time user opens the panel for. Paywalling those
     * makes search feel broken rather than limited, which costs more in
     * abandoned signups than it earns in upgrades.
     */
    protected const BASIC_FILTERS = [
        'q', 'sort', 'page', 'per_page',
        'age_min', 'age_max', 'height_min', 'height_max',
        'religion_id', 'religion_ids',
        'community_id', 'community_ids', 'sub_community_ids',
        'mother_tongue_id', 'mother_tongue_ids',
        'marital_status_id', 'marital_status_ids',
        'country_id', 'country_ids', 'state_id', 'state_ids', 'city_id', 'city_ids',
        'has_photo',
    ];

    public function __construct(
        protected SearchService $search,
        protected ViewerState $viewerState,
        protected FeatureGate $features,
    ) {}

    public function __invoke(User $viewer, array $criteria): LengthAwarePaginator
    {
        $this->assertFiltersAllowed($viewer, $criteria);

        $results = $this->search->search($viewer, $criteria);

        $this->viewerState->hydrate($results->getCollection(), $viewer);

        return $results;
    }

    /**
     * @throws FeatureLimitException rendered as the standard upgrade envelope
     */
    protected function assertFiltersAllowed(User $viewer, array $criteria): void
    {
        $used = array_keys(array_filter(
            $criteria,
            // An empty multi-select is not a filter. A client sending
            // `education_ids[]=` must not be told to upgrade for nothing.
            fn ($value) => $value !== null && $value !== '' && $value !== [],
        ));

        $advanced = array_filter(
            array_diff($used, self::BASIC_FILTERS),
            fn (string $key) => ! str_starts_with($key, '_'),
        );

        if ($advanced !== [] && ! $this->features->allows($viewer, 'advanced_search')) {
            throw FeatureLimitException::capability('advanced_search');
        }
    }
}
