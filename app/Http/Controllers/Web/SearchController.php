<?php

namespace App\Http\Controllers\Web;

use App\Actions\Search\RunSearch;
use App\Http\Controllers\Controller;
use App\Http\Requests\Search\SearchRequest;
use App\Services\MasterData\MasterDataCache;
use Illuminate\View\View;

/**
 * The `/search` screen. A plain `<form method="GET">` that reloads with
 * results — no Alpine required for the golden path
 * (docs/05-website-guide.md §1, "Interactivity ... applied sparingly").
 *
 * Only the basic filter set is offered here (age, marital status, religion,
 * mother tongue, has-photo, sort) — the same set `RunSearch::BASIC_FILTERS`
 * lets a free member use. RunSearch's `FeatureLimitException` — self-rendering
 * per PROGRESS.md §6 — exists for the advanced set a future filter panel would
 * add; this form never sends a key outside the basic set, so that path is
 * unreachable from here today, not silently swallowed.
 */
class SearchController extends Controller
{
    public function __construct(protected MasterDataCache $masterData) {}

    public function index(SearchRequest $request, RunSearch $runSearch): View
    {
        $results = $runSearch($request->user(), $request->criteria());

        return view('search.index', [
            'results' => $results,
            'query' => $request->query(),
            'viewer' => $request->user(),
            'sorts' => config('matrimony.search.sorts'),
            'religions' => $this->masterData->list('religions'),
            'maritalStatuses' => $this->masterData->list('marital-statuses'),
            'motherTongues' => $this->masterData->list('mother-tongues'),
        ]);
    }
}
