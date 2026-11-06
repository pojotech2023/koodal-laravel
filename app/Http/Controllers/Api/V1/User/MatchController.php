<?php

namespace App\Http\Controllers\Api\V1\User;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProfileResource;
use App\Services\MasterData\MasterDataCache;
use App\Services\Matching\MatchFeed;
use App\Services\Profile\ViewerState;
use App\Support\ApiResponse;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Five tabs, five orderings, one payload shape. The queries are in MatchFeed;
 * what is left here is the per-page cap and the viewer-state hydration every
 * profile list needs.
 */
class MatchController extends Controller
{
    public function __construct(
        protected MatchFeed $feed,
        protected ViewerState $viewerState,
        protected MasterDataCache $masterData,
    ) {}

    public function index(Request $request): JsonResponse
    {
        return $this->respond(
            $this->feed->recommended($request->user(), $this->perPage($request)),
            $request,
            'Matches retrieved successfully',
        );
    }

    public function newProfiles(Request $request): JsonResponse
    {
        return $this->respond(
            $this->feed->newProfiles($request->user(), $this->perPage($request)),
            $request,
            'New profiles retrieved successfully',
        );
    }

    public function recentlyActive(Request $request): JsonResponse
    {
        return $this->respond(
            $this->feed->recentlyActive($request->user(), $this->perPage($request)),
            $request,
            'Recently active profiles retrieved successfully',
        );
    }

    public function mutual(Request $request): JsonResponse
    {
        return $this->respond(
            $this->feed->mutual($request->user(), $this->perPage($request)),
            $request,
            'Your connections retrieved successfully',
        );
    }

    /**
     * The values a client can send to `preference-match` and `caste-match`,
     * in one call — so a filter sheet never hardcodes a raasi or a community.
     *
     * `raasis` / `nakshatras` are the fixed astrology system from config
     * (plain strings; send them back as `?raasi[]=` / `?star[]=`). The rest
     * are admin-managed master data, cached and versioned like `/master-data`;
     * communities and sub-communities are sent back by `id`, castes and
     * gothras by `name` (they are free-text columns on the profile).
     */
    public function filterOptions(): JsonResponse
    {
        return ApiResponse::success([
            'version' => $this->masterData->version(),
            'horoscope' => [
                'raasis' => config('matrimony.astrology.raasis'),
                'nakshatras' => config('matrimony.astrology.nakshatras'),
            ],
            'caste' => [
                'communities' => $this->masterData->list('communities'),
                'sub_communities' => $this->masterData->list('sub-communities'),
                'castes' => $this->masterData->list('castes'),
                'gothras' => $this->masterData->list('gothras'),
            ],
        ], 'Match filter options retrieved successfully');
    }

    /**
     * "Horoscope matches" — the `Web\MatchController::preferenceMatch` sibling.
     * `raasi`/`star` arrive as `?raasi[]=`/`?star[]=` — plain free-text values
     * from `config('matrimony.astrology')`, not ids, same as the web filter.
     */
    public function preferenceMatch(Request $request): JsonResponse
    {
        $raasis = array_filter((array) $request->query('raasi', []));
        $stars = array_filter((array) $request->query('star', []));

        return $this->respond(
            $this->feed->preferenceMatch($request->user(), $this->perPage($request), $raasis, $stars),
            $request,
            'Horoscope matches retrieved successfully',
        );
    }

    /**
     * "Caste matches" — the `Web\MatchController::casteMatch` sibling.
     * `community_id`/`sub_community_id` are ids; `caste`/`gothra` stay
     * free-text, same split as the columns themselves.
     */
    public function casteMatch(Request $request): JsonResponse
    {
        $communityIds = array_filter((array) $request->query('community_id', []));
        $subCommunityIds = array_filter((array) $request->query('sub_community_id', []));
        $castes = array_filter((array) $request->query('caste', []));
        $gothras = array_filter((array) $request->query('gothra', []));

        return $this->respond(
            $this->feed->casteMatch($request->user(), $this->perPage($request), $communityIds, $subCommunityIds, $castes, $gothras),
            $request,
            'Caste matches retrieved successfully',
        );
    }

    public function nearMe(Request $request): JsonResponse
    {
        $profile = $request->user()->profile;

        // An empty "near me" with no explanation looks like there is nobody in
        // the city. Say what is actually missing.
        $message = ($profile?->city_id || $profile?->state_id)
            ? 'Profiles near you retrieved successfully'
            : 'Add your city to your profile to see members near you.';

        return $this->respond(
            $this->feed->nearMe($request->user(), $this->perPage($request)),
            $request,
            $message,
        );
    }

    protected function respond(LengthAwarePaginator $results, Request $request, string $message): JsonResponse
    {
        $this->viewerState->hydrate($results->getCollection(), $request->user());

        return ApiResponse::paginated($results, ProfileResource::class, $message);
    }

    protected function perPage(Request $request): int
    {
        return min(
            $request->integer('per_page', config('matrimony.search.per_page')),
            config('matrimony.search.max_per_page'),
        );
    }
}
