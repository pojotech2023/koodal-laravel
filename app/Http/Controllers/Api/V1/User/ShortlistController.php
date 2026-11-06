<?php

namespace App\Http\Controllers\Api\V1\User;

use App\Actions\Shortlist\RemoveShortlist;
use App\Actions\Shortlist\ShortlistProfile;
use App\Http\Controllers\Controller;
use App\Http\Requests\Shortlist\ShortlistRequest;
use App\Http\Resources\ShortlistResource;
use App\Models\Shortlist;
use App\Models\User;
use App\Models\UserProfile;
use App\Services\Membership\FeatureGate;
use App\Services\Profile\ViewerState;
use App\Support\ApiResponse;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ShortlistController extends Controller
{
    public function __construct(
        protected ViewerState $viewerState,
        protected FeatureGate $features,
    ) {}

    /** The viewer's own shortlist. Their list, so no gate. */
    public function index(Request $request): JsonResponse
    {
        $shortlists = $request->user()->shortlists()
            ->with($this->counterpartRelations('shortlistedUser'))
            ->latest()
            ->paginate($request->integer('per_page', 20));

        return $this->respond($shortlists, $request, 'shortlistedUser', 'Shortlist retrieved successfully');
    }

    /**
     * Who shortlisted me — the paid half, per the membership spec's gating
     * table. Free members are told a count exists without being shown who,
     * which is the upgrade prompt doing its job rather than a dead end.
     */
    public function whoShortlistedMe(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $this->features->allows($user, 'can_see_profile_viewers')) {
            return ApiResponse::upgradeRequired(
                'can_see_profile_viewers',
                'Upgrade your membership to see who shortlisted you.',
                ['total' => (int) ($user->profile?->shortlisted_count ?? 0)],
            );
        }

        $shortlists = Shortlist::query()
            ->where('shortlisted_user_id', $user->id)
            // A suspended or deleted account must not surface here just
            // because it once tapped the heart.
            ->whereHas('user', fn ($query) => $query->where('status', 'active'))
            ->with($this->counterpartRelations('user'))
            ->latest()
            ->paginate($request->integer('per_page', 20));

        return $this->respond($shortlists, $request, 'user', 'Received shortlists retrieved successfully');
    }

    public function store(ShortlistRequest $request, ShortlistProfile $shortlist): JsonResponse
    {
        $target = User::findOrFail($request->integer('user_id'));

        $row = $shortlist($request->user(), $target, $request->input('note'));

        return ApiResponse::created(
            new ShortlistResource($row->load($this->counterpartRelations('shortlistedUser'))),
            'Profile added to your shortlist',
        );
    }

    /**
     * Keyed on the shortlisted user, not the row id — that is what the heart
     * knows. `$identifier` is deliberately not an implicit `User $user`
     * binding: that would require the numeric id, and `ProfileResource` never
     * hands the client one for anybody but themselves. Accepting either the
     * (rare, direct) numeric id or the `profile_code` every other client call
     * actually has keeps this endpoint reachable from `ProfileCard` and the
     * shortlist screen without adding an id to a payload built to omit it.
     */
    public function destroy(string $identifier, Request $request, RemoveShortlist $remove): JsonResponse
    {
        $target = ctype_digit($identifier)
            ? User::findOrFail((int) $identifier)
            : User::where('profile_code', strtoupper($identifier))->firstOrFail();

        $remove($request->user(), $target);

        return ApiResponse::noContent();
    }

    // --- Internals ---------------------------------------------------------

    /**
     * Everything ProfileResource reads, eager-loaded here rather than in the
     * resource. A Resource that queries is an N+1, and this list renders one
     * profile card per row.
     */
    protected function counterpartRelations(string $relation): array
    {
        return array_merge(
            [
                $relation.'.profile',
                $relation.'.profile.primaryPhoto',
                // ProfileResource reads the profile's own `user` and that
                // user's privacy row for every field it gates.
                $relation.'.profile.user',
                $relation.'.profile.user.privacySettings',
            ],
            array_map(fn (string $lookup) => $relation.'.profile.'.$lookup, UserProfile::WITH_LOOKUPS),
        );
    }

    protected function respond(LengthAwarePaginator $page, Request $request, string $relation, string $message): JsonResponse
    {
        $profiles = collect($page->items())
            ->map(fn (Shortlist $row) => $row->{$relation}?->profile)
            ->filter();

        $this->viewerState->hydrate($profiles, $request->user());

        return ApiResponse::paginated($page, ShortlistResource::class, $message);
    }
}
