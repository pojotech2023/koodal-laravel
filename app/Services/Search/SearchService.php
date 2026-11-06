<?php

namespace App\Services\Search;

use App\Models\User;
use App\Models\UserProfile;
use App\Support\Age;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/**
 * The query that decides whether this product feels fast.
 *
 * Three rules it is built around:
 *
 *   • Selectivity first. Gender inversion + active + approved eliminates
 *     roughly 95% of rows and is always applied before anything else, so every
 *     later predicate runs against a small set.
 *
 *   • Filtering happens in SQL, never in PHP. A "fetch 5,000 then filter" shape
 *     looks identical on a seeded dev database and falls over at 100k rows.
 *
 *   • Exclusions (blocks, already-actioned profiles) are NOT EXISTS subqueries
 *     rather than whereNotIn with a materialised id list. A user with 4,000
 *     shortlists would otherwise ship 4,000 integers into the query text.
 *
 * Gender is never accepted as input on the member path. It is inverted from
 * the authenticated user, so a client cannot search its own gender no matter
 * what it sends. previewQuery() is the single, deliberate exception — a
 * signed-out visitor has no gender to invert, and no own-gender profile to
 * search towards either; see its docblock.
 */
class SearchService
{
    public function __construct(protected SearchFilters $filters) {}

    public function search(User $viewer, array $input): LengthAwarePaginator
    {
        $query = $this->baseQuery($viewer);

        $this->filters->apply($query, $input);

        $this->applySort($query, $viewer, $input['sort'] ?? 'recommended');

        $perPage = min(
            (int) ($input['per_page'] ?? config('matrimony.search.per_page')),
            config('matrimony.search.max_per_page'),
        );

        $page = max(1, (int) ($input['page'] ?? 1));

        // Deep offsets are an enumeration pattern, not a person browsing. The
        // cap is enforced server-side because the client cannot be trusted to
        // stop asking.
        $maxPage = (int) ceil(config('matrimony.search.max_offset') / $perPage);

        return $query->paginate($perPage, ['user_profiles.*'], 'page', min($page, $maxPage));
    }

    /**
     * Profile-code or name lookup. Kept separate from the filtered search
     * because it is a different intent — a user pasting a code wants that one
     * profile, not the best 20 matches for it.
     */
    public function quick(User $viewer, string $term, int $limit = 20): LengthAwarePaginator
    {
        $query = $this->baseQuery($viewer);

        $term = trim($term);

        $query->where(function (Builder $q) use ($term) {
            $q->whereHas('user', fn (Builder $u) => $u
                ->where('profile_code', $term)
                ->orWhere('name', 'like', $term.'%'));
        });

        return $query->paginate($limit);
    }

    /**
     * Everything selective, applied first: the viewer-independent predicates
     * from discoverable(), then the two that need a viewer — self-exclusion
     * and blocks in both directions.
     */
    public function baseQuery(User $viewer): Builder
    {
        return $this->discoverable($viewer->oppositeGender())
            ->where('users.id', '!=', $viewer->id)
            ->tap(fn (Builder $q) => $this->excludeBlocked($q, $viewer))
            ->with(array_merge(UserProfile::WITH_LOOKUPS, [
                'user:id,name,profile_code,gender,date_of_birth,last_active_at',

                // ProfileResource asks ProfileVisibility about the owner's
                // privacy settings for every row. Without this it lazy-loads
                // per profile, which preventLazyLoading turns into an exception
                // outside production — and into a 20-query search page in it.
                // (The owner's own profile relation is wired by the Resource.)
                'user.privacySettings',

                'primaryPhoto',
            ]));
    }

    /**
     * The predicates that decide whether a profile is discoverable *at all*,
     * independent of who is looking — shared by the member search above and by
     * the signed-out preview below.
     *
     * Extracted rather than duplicated on purpose: this is the set that grows
     * (a shadow-ban flag, a soft-suspend state), and a predicate added to one
     * copy and not the other is a privacy bug that no test on the member path
     * would catch.
     *
     * `appear_in_search` is checked with a LEFT JOIN rather than whereHas so a
     * user who never opened privacy settings — and therefore has no row —
     * still appears. whereHas would silently hide every such account.
     */
    protected function discoverable(string $gender): Builder
    {
        return UserProfile::query()
            ->select('user_profiles.*')
            ->join('users', 'users.id', '=', 'user_profiles.user_id')
            ->leftJoin('privacy_settings', 'privacy_settings.user_id', '=', 'user_profiles.user_id')
            ->where('users.gender', $gender)
            ->where('users.status', 'active')
            ->whereNull('users.deleted_at')
            ->where('user_profiles.approval_status', 'approved')
            ->whereNull('user_profiles.deleted_at')
            ->where(fn (Builder $q) => $q
                ->whereNull('privacy_settings.appear_in_search')
                ->orWhere('privacy_settings.appear_in_search', true));
    }

    /**
     * The signed-out preview's query — `/browse`, not `/search`.
     *
     * Three things separate it from baseQuery, and none of them is a
     * relaxation:
     *
     *   • Gender is a parameter, not an inversion. There is no viewer to
     *     invert from, so the guest states it ("looking for a bride"). This is
     *     the ONLY place in the codebase where gender arrives as input, and it
     *     is safe here for exactly the reason it is unsafe on the member path:
     *     a guest has no own-gender profile to search towards.
     *
     *   • `profile_visibility = 'all'`, compared strictly. ProfileVisibility::
     *     satisfiesAudience() already answers "may a null viewer see this?"
     *     with `$audience === 'all'`, and this is that same rule pushed into
     *     SQL so the filtering happens in the database rather than in PHP over
     *     a broad result set. The comparison is deliberately NOT NULL-tolerant
     *     the way `appear_in_search` above is: a missing privacy_settings row
     *     means `'members'` (see the `?? 'members'` in ProfileVisibility and
     *     the column default), so a member who never opened the settings screen
     *     must NOT appear to the public. The two columns pull in opposite
     *     directions on NULL and that asymmetry is the point of both defaults.
     *
     *   • No block exclusion and no self-exclusion, because there is nobody to
     *     exclude. A guest has blocked no one.
     *
     * `user.privacySettings` is eager-loaded for the same reason baseQuery
     * loads it — <x-profile.preview-card> asks canViewPhotos(null, $owner)
     * per row, and preventLazyLoading turns a missed relation into an
     * exception outside production.
     */
    public function previewQuery(string $gender): Builder
    {
        return $this->discoverable($gender)
            ->where('privacy_settings.profile_visibility', 'all')
            ->with(array_merge(UserProfile::WITH_LOOKUPS, [
                'user:id,name,profile_code,gender,date_of_birth,last_active_at',
                'user.privacySettings',
                'primaryPhoto',
            ]));
    }

    /**
     * The same discoverable set the preview draws from, but WITHOUT the
     * `profile_visibility = 'all'` restriction — the population a guest is
     * being told about rather than shown.
     *
     * Counting a wider set than is displayed is intentional and is the whole
     * value of the preview page: "1,000+ brides match, here are the four who
     * publish publicly" converts, where four cards alone read as an empty
     * product. It discloses nothing about any individual — RunPreviewSearch
     * rounds the result down to a bucket before it ever reaches a view, for
     * the enumeration reason set out in that class.
     */
    public function previewCountQuery(string $gender): Builder
    {
        return $this->discoverable($gender);
    }

    /**
     * Blocks in both directions. NOT EXISTS keeps the plan stable regardless of
     * how many people the viewer has blocked; a whereNotIn(subquery) degrades
     * badly in MySQL once the inner set is large.
     *
     * Public because the mutual-matches feed reuses it: that query cannot start
     * from baseQuery (see MatchFeed::mutual) but must not lose the block rule.
     */
    public function excludeBlocked(Builder $query, User $viewer): void
    {
        $query->whereNotExists(fn ($q) => $q
            ->select(DB::raw(1))
            ->from('blocked_users')
            ->whereColumn('blocked_users.blocked_user_id', 'user_profiles.user_id')
            ->where('blocked_users.user_id', $viewer->id));

        $query->whereNotExists(fn ($q) => $q
            ->select(DB::raw(1))
            ->from('blocked_users')
            ->whereColumn('blocked_users.user_id', 'user_profiles.user_id')
            ->where('blocked_users.blocked_user_id', $viewer->id));
    }

    /**
     * `recommended` reads the precomputed match_scores table. Scoring live
     * against a million candidates is not a slow query, it is an impossible
     * one — so the nightly job pays that cost and search pays an index read.
     *
     * The LEFT JOIN matters: a viewer whose scores have not been computed yet
     * (registered an hour ago) must still get results, ordered by the fallback,
     * rather than an empty page.
     */
    protected function applySort(Builder $query, User $viewer, string $sort): void
    {
        if (! in_array($sort, config('matrimony.search.sorts'), true)) {
            $sort = 'recommended';
        }

        match ($sort) {
            'recommended' => $query
                ->leftJoin('match_scores', function ($join) use ($viewer) {
                    $join->on('match_scores.candidate_id', '=', 'user_profiles.user_id')
                        ->where('match_scores.user_id', '=', $viewer->id);
                })
                ->addSelect('match_scores.score as match_score')
                ->orderByRaw('match_scores.score IS NULL')
                ->orderByDesc('match_scores.score')
                ->orderByDesc('user_profiles.completion_percentage')
                ->orderByDesc('users.last_active_at'),

            'recently_joined' => $query->orderByDesc('users.created_at'),
            'recently_active' => $query->orderByDesc('users.last_active_at'),
            'profile_completeness' => $query->orderByDesc('user_profiles.completion_percentage'),

            // Age ascending means date of birth descending — the youngest were
            // born most recently. Getting this backwards is a classic.
            'age_asc' => $query->orderByDesc('users.date_of_birth'),
            'age_desc' => $query->orderBy('users.date_of_birth'),

            default => $query->orderByDesc('users.last_active_at'),
        };

        // A stable tiebreak. Without it, two rows with equal sort keys can swap
        // between pages and the user sees the same profile twice.
        $query->orderBy('user_profiles.user_id');
    }

    /**
     * Seeds a search from the viewer's saved partner preferences — what the
     * "Matches" tab runs.
     */
    public function fromPreferences(User $viewer): array
    {
        $preferences = $viewer->preferences;

        if (! $preferences || $preferences->isEmpty()) {
            return [];
        }

        return array_filter([
            'age_min' => $preferences->age_min,
            'age_max' => $preferences->age_max,
            'height_min' => $preferences->height_min_cm,
            'height_max' => $preferences->height_max_cm,
            'weight_min' => $preferences->weight_min_kg,
            'weight_max' => $preferences->weight_max_kg,
            'marital_status_ids' => $preferences->ids('marital_status_ids'),
            'religion_ids' => $preferences->ids('religion_ids'),
            'community_ids' => $preferences->ids('community_ids'),
            'mother_tongue_ids' => $preferences->ids('mother_tongue_ids'),
            'education_ids' => $preferences->ids('education_ids'),
            'occupation_ids' => $preferences->ids('occupation_ids'),
            'income_range_ids' => $preferences->ids('income_range_ids'),
            'country_ids' => $preferences->ids('country_ids'),
            'state_ids' => $preferences->ids('state_ids'),
            'city_ids' => $preferences->ids('city_ids'),
            'complexions' => array_values(array_filter((array) ($preferences->complexions ?? []))),
            'raasis' => array_values(array_filter((array) ($preferences->raasis ?? []))),
            'stars' => array_values(array_filter((array) ($preferences->stars ?? []))),
            'castes' => array_values(array_filter((array) ($preferences->castes ?? []))),
            'income_min' => $preferences->income_min,
            'income_max' => $preferences->income_max,
            'diet' => $preferences->diet,
            'smoking' => $preferences->smoking,
            'drinking' => $preferences->drinking,
        ], fn ($value) => $value !== null && $value !== [] && $value !== '');
    }

    /** Age bounds are converted to date-of-birth bounds so the index is used. */
    public function ageWindow(?int $minAge, ?int $maxAge): array
    {
        return Age::birthdateRange($minAge, $maxAge);
    }
}
