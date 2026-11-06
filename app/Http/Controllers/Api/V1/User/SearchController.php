<?php

namespace App\Http\Controllers\Api\V1\User;

use App\Actions\Search\RunSearch;
use App\Http\Controllers\Controller;
use App\Http\Requests\Search\SearchRequest;
use App\Http\Resources\ProfileResource;
use App\Services\Membership\FeatureGate;
use App\Services\Profile\ViewerState;
use App\Services\Search\SearchFilters;
use App\Services\Search\SearchService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Invokable for the main search, with two siblings hanging off it: `quick` is a
 * different intent (someone pasting a profile code wants that profile, not the
 * best twenty matches for it) and `filters` describes the panel.
 *
 * The quota and the filter gate are in RunSearch — this class only translates
 * HTTP to it and back.
 */
class SearchController extends Controller
{
    /**
     * Definition keys the client should render dimmed on a free plan. Mirrors
     * the inverse of RunSearch::BASIC_FILTERS, expressed in the vocabulary the
     * filter panel uses rather than the query string's.
     */
    protected const LOCKED_WITHOUT_ADVANCED = [
        'education_ids', 'occupation_ids', 'income', 'diet', 'smoking', 'drinking', 'is_verified',
    ];

    public function __construct(
        protected SearchService $search,
        protected SearchFilters $filters,
        protected ViewerState $viewerState,
    ) {}

    public function __invoke(SearchRequest $request, RunSearch $runSearch): JsonResponse
    {
        $results = $runSearch($request->user(), $request->criteria());

        return ApiResponse::paginated($results, ProfileResource::class, 'Profiles retrieved successfully');
    }

    /**
     * Profile-code or name lookup. Ungated: finding the profile a relative sent
     * you over WhatsApp is the single most common first action on the site.
     */
    public function quick(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'q' => ['required', 'string', 'min:2', 'max:100'],
            'per_page' => ['sometimes', 'integer', 'between:1,'.config('matrimony.search.max_per_page')],
        ]);

        $viewer = $request->user();

        $results = $this->search->quick($viewer, $validated['q'], (int) ($validated['per_page'] ?? 20));

        $this->viewerState->hydrate($results->getCollection(), $viewer);

        return ApiResponse::paginated($results, ProfileResource::class, 'Profiles retrieved successfully');
    }

    /**
     * The filter panel, built from the same definitions the WHERE clause
     * enforces. `locked` tells the client which ones to dim rather than hide —
     * a filter the user cannot see is a feature they will never upgrade for.
     */
    public function filters(Request $request, FeatureGate $features): JsonResponse
    {
        $advanced = $features->allows($request->user(), 'advanced_search');

        return ApiResponse::success([
            'filters' => $this->filters->definitions(),
            'sorts' => config('matrimony.search.sorts'),
            'locked' => $advanced ? [] : self::LOCKED_WITHOUT_ADVANCED,
        ], 'Search filters retrieved successfully');
    }
}
