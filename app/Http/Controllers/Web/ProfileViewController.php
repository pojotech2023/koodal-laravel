<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\ProfileView;
use App\Models\UserProfile;
use App\Services\Profile\ViewerState;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ProfileViewController extends Controller
{
    public function __construct(
        protected ViewerState $viewerState,
    ) {}

    public function index(Request $request): View
    {
        $user = $request->user();

        $views = $this->grouped(ProfileView::query()->where('viewed_id', $user->id), 'viewer_id')
            ->leftJoin('privacy_settings', 'privacy_settings.user_id', '=', 'profile_views.viewer_id')
            ->where(fn (Builder $q) => $q
                ->whereNull('privacy_settings.show_profile_views')
                ->orWhere('privacy_settings.show_profile_views', true))
            ->with($this->counterpartRelations('viewer'))
            ->paginate(20);

        return $this->render('received', $views, 'viewer', $request);
    }

    public function sent(Request $request): View
    {
        $views = $this->grouped(ProfileView::query()->where('viewer_id', $request->user()->id), 'viewed_id')
            ->with($this->counterpartRelations('viewed'))
            ->paginate(20);

        return $this->render('sent', $views, 'viewed', $request);
    }

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

    protected function render(string $tab, LengthAwarePaginator $page, string $relation, Request $request): View
    {
        $profiles = collect($page->items())
            ->map(fn (ProfileView $row) => $row->{$relation}?->profile)
            ->filter();

        $this->viewerState->hydrate($profiles, $request->user());

        return view('profile-views.index', [
            'tab' => $tab,
            'views' => $page,
            'relation' => $relation,
            'viewer' => $request->user(),
        ]);
    }
}
