<?php

namespace App\Http\Controllers\Web;

use App\Actions\Shortlist\RemoveShortlist;
use App\Actions\Shortlist\ShortlistProfile;
use App\Http\Controllers\Controller;
use App\Http\Requests\Shortlist\ShortlistRequest;
use App\Models\Shortlist;
use App\Models\User;
use App\Models\UserProfile;
use App\Services\Membership\FeatureGate;
use App\Services\Profile\ViewerState;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * `Web\*` sibling of `Api\V1\User\ShortlistController`. `store`/`destroy` are
 * plain `<form>` posts from `<x-profile.card>` and the profile detail page;
 * `ShortlistProfile`'s duplicate-guard `abort(409, …)` and this controller's
 * own gate check both fall through to Laravel's default HTML error handling
 * on a double-submit — an accepted edge, not the golden path (PROGRESS.md §6).
 */
class ShortlistController extends Controller
{
    public function __construct(
        protected ViewerState $viewerState,
        protected FeatureGate $features,
    ) {}

    public function index(Request $request): View
    {
        $shortlists = $request->user()->shortlists()
            ->with($this->counterpartRelations('shortlistedUser'))
            ->latest()
            ->paginate(20);

        return $this->render('mine', $shortlists, 'shortlistedUser', $request);
    }

    /**
     * Who shortlisted me — the paid half. A free member sees the count and an
     * upgrade prompt instead of the list, same as the API's envelope.
     */
    public function whoShortlistedMe(Request $request): View
    {
        $user = $request->user();

        $shortlists = Shortlist::query()
            ->where('shortlisted_user_id', $user->id)
            ->whereHas('user', fn ($query) => $query->where('status', 'active'))
            ->with($this->counterpartRelations('user'))
            ->latest()
            ->paginate(20);

        return $this->render('received', $shortlists, 'user', $request);
    }

    public function store(ShortlistRequest $request, ShortlistProfile $shortlist): RedirectResponse
    {
        $shortlist($request->user(), User::findOrFail($request->integer('user_id')), $request->input('note'));

        return back()->with('status', 'Profile added to your shortlist.');
    }

    /** Keyed on profile_code, same as the API — see its docblock. */
    public function destroy(string $identifier, Request $request, RemoveShortlist $remove): RedirectResponse
    {
        $target = ctype_digit($identifier)
            ? User::findOrFail((int) $identifier)
            : User::where('profile_code', strtoupper($identifier))->firstOrFail();

        $remove($request->user(), $target);

        return back()->with('status', 'Removed from your shortlist.');
    }

    // --- Internals ---------------------------------------------------------

    protected function counterpartRelations(string $relation): array
    {
        return array_merge(
            [
                $relation.'.profile',
                $relation.'.profile.primaryPhoto',
                $relation.'.profile.user',
                $relation.'.profile.user.privacySettings',
            ],
            array_map(fn (string $lookup) => $relation.'.profile.'.$lookup, UserProfile::WITH_LOOKUPS),
        );
    }

    protected function render(string $tab, LengthAwarePaginator $page, string $relation, Request $request): View
    {
        $profiles = collect($page->items())
            ->map(fn (Shortlist $row) => $row->{$relation}?->profile)
            ->filter();

        $this->viewerState->hydrate($profiles, $request->user());

        return view('shortlist.index', [
            'tab' => $tab,
            'locked' => false,
            'shortlists' => $page,
            'relation' => $relation,
            'viewer' => $request->user(),
        ]);
    }
}
