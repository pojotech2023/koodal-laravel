<?php

namespace App\Http\Controllers\Api\V1\User;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProfileViewResource;
use App\Models\ProfileView;
use App\Models\UserProfile;
use App\Services\Membership\FeatureGate;
use App\Services\Profile\ViewerState;
use App\Support\ApiResponse;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Who looked at whom.
 *
 * Both lists are grouped by (person, day) in SQL. The table deliberately keeps
 * every raw view — that is how an enumeration pattern stays visible — but a
 * user who refreshed a profile four times must appear once, not four times.
 */
class ProfileViewController extends Controller
{
    public function __construct(
        protected FeatureGate $features,
        protected ViewerState $viewerState,
    ) {}

    /**
     * Gated: seeing who viewed you is the headline paid feature. The count is
     * still returned, because "you have 14 profile views" is the upgrade
     * prompt, and hiding it makes the paywall pointless.
     */
    public function received(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $this->features->allows($user, 'can_see_profile_viewers')) {
            return ApiResponse::upgradeRequired(
                'can_see_profile_viewers',
                'Upgrade your membership to see who viewed your profile.',
                ['total' => (int) ($user->profile?->profile_views_count ?? 0)],
            );
        }

        $views = $this->grouped(ProfileView::query()->where('viewed_id', $user->id), 'viewer_id')
            // A viewer who turned off `show_profile_views` browses anonymously.
            // LEFT JOIN, not whereHas: a user who never opened privacy settings
            // has no row at all, and whereHas would hide every such visitor.
            ->leftJoin('privacy_settings', 'privacy_settings.user_id', '=', 'profile_views.viewer_id')
            ->where(fn (Builder $q) => $q
                ->whereNull('privacy_settings.show_profile_views')
                ->orWhere('privacy_settings.show_profile_views', true))
            ->with($this->counterpartRelations('viewer'))
            ->paginate($request->integer('per_page', 20));

        return $this->respond($views, $request, 'viewer', 'Profile viewers retrieved successfully');
    }

    /** The viewer's own browsing history. Their data, so no gate. */
    public function sent(Request $request): JsonResponse
    {
        $views = $this->grouped(ProfileView::query()->where('viewer_id', $request->user()->id), 'viewed_id')
            ->with($this->counterpartRelations('viewed'))
            ->paginate($request->integer('per_page', 20));

        return $this->respond($views, $request, 'viewed', 'Viewed profiles retrieved successfully');
    }

    /**
     * The numbers behind the tab, in two queries: one aggregate for the totals
     * and one grouped scan for the daily series the dashboard charts.
     */
    public function stats(Request $request): JsonResponse
    {
        $user = $request->user();
        $since = now()->subDays(7)->startOfDay();

        $totals = ProfileView::query()
            ->where('viewed_id', $user->id)
            ->selectRaw('COUNT(*) as total')
            ->selectRaw('COUNT(DISTINCT viewer_id) as unique_viewers')
            ->selectRaw('SUM(viewed_at >= ?) as today', [now()->startOfDay()])
            ->selectRaw('SUM(viewed_at >= ?) as this_week', [$since])
            ->first();

        $daily = ProfileView::query()
            ->where('viewed_id', $user->id)
            ->where('viewed_at', '>=', $since)
            ->selectRaw('DATE(viewed_at) as day, COUNT(DISTINCT viewer_id) as viewers')
            ->groupBy(DB::raw('DATE(viewed_at)'))
            ->orderBy('day')
            ->get();

        return ApiResponse::success([
            'total' => (int) $totals->total,
            'unique_viewers' => (int) $totals->unique_viewers,
            'today' => (int) $totals->today,
            'this_week' => (int) $totals->this_week,
            'daily' => $daily->map(fn ($row) => ['date' => $row->day, 'count' => (int) $row->viewers])->values(),
        ], 'Profile view statistics retrieved successfully');
    }

    // --- Internals ---------------------------------------------------------

    /**
     * One row per (counterpart, day).
     *
     * Every selected column is either grouped or aggregated — MySQL 8 runs with
     * ONLY_FULL_GROUP_BY on by default, and a bare column here is a query that
     * works in development and fails in production.
     */
    protected function grouped(Builder $query, string $counterpartColumn): Builder
    {
        return $query
            ->select("profile_views.{$counterpartColumn}")
            ->selectRaw('DATE(profile_views.viewed_at) as viewed_on')
            ->selectRaw('MAX(profile_views.viewed_at) as last_viewed_at')
            ->selectRaw('COUNT(*) as view_count')
            ->groupBy("profile_views.{$counterpartColumn}", DB::raw('DATE(profile_views.viewed_at)'))
            ->orderByDesc('last_viewed_at');
    }

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

    protected function respond(LengthAwarePaginator $page, Request $request, string $relation, string $message): JsonResponse
    {
        $profiles = collect($page->items())
            ->map(fn (ProfileView $row) => $row->{$relation}?->profile)
            ->filter();

        $this->viewerState->hydrate($profiles, $request->user());

        return ApiResponse::paginated($page, ProfileViewResource::class, $message);
    }
}
