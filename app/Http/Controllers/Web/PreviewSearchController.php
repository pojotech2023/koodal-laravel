<?php

namespace App\Http\Controllers\Web;

use App\Actions\Search\RunPreviewSearch;
use App\Http\Controllers\Controller;
use App\Http\Requests\Search\PreviewSearchRequest;
use App\Services\MasterData\MasterDataCache;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * `/browse` — the signed-out preview docs/05-website-guide.md §3 lists as
 * "search (limited results for guests)".
 *
 * **Why `/browse` and not `/search`.** The guide names the page "search", and
 * a single URL serving both audiences was the first thing tried. It does not
 * work: `/search` is registered inside the `auth:web` group, Laravel matches
 * routes in registration order, and a second `/search` outside that group
 * either shadows the member page (registered first) or is unreachable
 * (registered second). The alternatives were a viewer branch inside
 * Web\SearchController — every line of which would then be an `if ($viewer)` —
 * or two URLs. Two URLs also turn out to be the better answer on their own
 * merits: the pages have different canonical URLs and different titles for
 * SEO, and a signed-in member landing here is redirected to the real search
 * rather than shown a degraded copy of it. PROGRESS.md §6 records the
 * deviation.
 *
 * The page runs no query at all until the visitor says who they are looking
 * for. There is no authenticated gender to invert, and picking a default would
 * mean the product guessing — so the first render is the form.
 */
class PreviewSearchController extends Controller
{
    public function __construct(protected MasterDataCache $masterData) {}

    public function index(PreviewSearchRequest $request, RunPreviewSearch $runPreview): View|RedirectResponse
    {
        // A member has a better version of this page. Their filters come with
        // them: every key PreviewSearchRequest accepts is also in
        // RunSearch::BASIC_FILTERS under the same name, so the criteria
        // transfer untranslated. `looking_for` is deliberately dropped —
        // SearchService inverts gender from the account, and a member must not
        // be able to hand it one.
        if (Auth::guard('web')->check()) {
            return redirect()->route('search', $request->criteria());
        }

        $lookingFor = $request->lookingFor();

        return view('search.preview', [
            'lookingFor' => $lookingFor,
            'preview' => $lookingFor ? $runPreview($lookingFor, $request->criteria()) : null,
            'criteria' => $request->criteria(),
            'religions' => $this->masterData->list('religions'),
            'maritalStatuses' => $this->masterData->list('marital-statuses'),
            'motherTongues' => $this->masterData->list('mother-tongues'),
        ]);
    }
}
