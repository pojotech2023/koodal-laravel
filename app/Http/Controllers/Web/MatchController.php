<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Services\Matching\MatchFeed;
use App\Services\MasterData\MasterDataCache;
use App\Services\Profile\ViewerState;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * The five `/matches` tabs — `Web\*` sibling of `Api\V1\User\MatchController`,
 * same `MatchFeed` queries, one view rendering whichever tab's results it was
 * handed. Five routes rather than one `?tab=` query param, so pagination
 * (`?page=`) never collides with which tab is active.
 */
class MatchController extends Controller
{
    public function __construct(
        protected MatchFeed $feed,
        protected ViewerState $viewerState,
        protected MasterDataCache $masterData,
    ) {}

    public function recommended(Request $request): View
    {
        return $this->render('recommended', $this->feed->recommended($request->user(), $this->perPage($request)), $request);
    }

    public function newProfiles(Request $request): View
    {
        return $this->render('new', $this->feed->newProfiles($request->user(), $this->perPage($request)), $request);
    }

    public function recentlyActive(Request $request): View
    {
        return $this->render('recently-active', $this->feed->recentlyActive($request->user(), $this->perPage($request)), $request);
    }

    public function mutual(Request $request): View
    {
        return $this->render('mutual', $this->feed->mutual($request->user(), $this->perPage($request)), $request);
    }

    public function referenceMatch(Request $request): View
    {
        $user = $request->user()->loadMissing('preferences');
        $prefs = $user->preferences;

        $notice = (! $prefs || $prefs->isEmpty())
            ? 'You have not saved specific partner preferences yet. Click "Edit Preference" to set your preferences.'
            : null;

        return $this->render('reference', $this->feed->referenceMatch($user, $this->perPage($request)), $request, $notice, [
            'userPreferences' => $prefs,
            'prefMasterData' => [
                'marital_statuses' => collect($this->masterData->list('marital-statuses'))->pluck('name', 'id')->all(),
                'occupations' => collect($this->masterData->list('occupations'))->pluck('name', 'id')->all(),
                'income_ranges' => collect($this->masterData->list('income-ranges'))->pluck('name', 'id')->all(),
                'mother_tongues' => collect($this->masterData->list('mother-tongues'))->pluck('name', 'id')->all(),
                'states' => collect($this->masterData->states(null))->pluck('name', 'id')->all(),
            ],
        ]);
    }

    public function preferenceMatch(Request $request): View
    {
        $raasis = array_filter((array) $request->query('raasi', []));
        $stars = array_filter((array) $request->query('star', []));

        $results = $this->feed->preferenceMatch($request->user(), $this->perPage($request), $raasis, $stars);

        return $this->render('preference-match', $results, $request, null, [
            'astrologyFilters' => [
                'raasi' => $raasis,
                'star' => $stars,
            ],
            'paginationAppends' => array_filter(['raasi' => $raasis, 'star' => $stars]),
        ]);
    }

    public function casteMatch(Request $request): View
    {
        $communityIds = array_filter((array) $request->query('community_id', []));
        $subCommunityIds = array_filter((array) $request->query('sub_community_id', []));
        $castes = array_filter((array) $request->query('caste', []));
        $gothras = array_filter((array) $request->query('gothra', []));

        $results = $this->feed->casteMatch($request->user(), $this->perPage($request), $communityIds, $subCommunityIds, $castes, $gothras);

        return $this->render('caste-match', $results, $request, null, [
            'casteFilters' => [
                'community_id' => $communityIds,
                'sub_community_id' => $subCommunityIds,
                'caste' => $castes,
                'gothra' => $gothras,
            ],
            'communityOptions' => $this->masterData->list('communities'),
            'subCommunityOptions' => $this->masterData->list('sub-communities'),
            'casteOptions' => $this->masterData->list('castes'),
            'gothraOptions' => $this->masterData->list('gothras'),
            'paginationAppends' => array_filter([
                'community_id' => $communityIds,
                'sub_community_id' => $subCommunityIds,
                'caste' => $castes,
                'gothra' => $gothras,
            ]),
        ]);
    }

    public function nearMe(Request $request): View
    {
        $profile = $request->user()->profile;

        // An empty "near me" with no explanation looks like there is nobody in
        // the city. Say what is actually missing — same rule as the API.
        $notice = ($profile?->city_id || $profile?->state_id)
            ? null
            : 'Add your city to your profile to see members near you.';

        return $this->render('near-me', $this->feed->nearMe($request->user(), $this->perPage($request)), $request, $notice);
    }

    protected function render(string $tab, LengthAwarePaginator $results, Request $request, ?string $notice = null, array $extra = []): View
    {
        $this->viewerState->hydrate($results->getCollection(), $request->user());

        return view('matches.index', array_merge([
            'tab' => $tab,
            'results' => $results,
            'viewer' => $request->user(),
            'notice' => $notice,
        ], $extra));
    }

    protected function perPage(Request $request): int
    {
        return min(
            $request->integer('per_page', config('matrimony.search.per_page')),
            config('matrimony.search.max_per_page'),
        );
    }
}
