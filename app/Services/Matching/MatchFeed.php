<?php

namespace App\Services\Matching;

use App\Models\MatchScore;
use App\Models\User;
use App\Models\UserProfile;
use App\Services\Search\SearchFilters;
use App\Services\Search\SearchService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator as Paginator;

/**
 * The five `/matches` feeds.
 *
 * Four of them start from SearchService::baseQuery, so gender inversion, block
 * exclusion and the appear_in_search rule are written once and cannot drift
 * between tabs. What each feed adds is one predicate and an ordering — which is
 * more logic than a controller should carry, and less than an Action's worth of
 * state change.
 */
class MatchFeed
{
    /** "New" is the last week. Shorter and the tab is empty on a quiet day. */
    protected const NEW_PROFILE_DAYS = 7;

    public function __construct(
        protected SearchService $search,
        protected SearchFilters $filters,
    ) {}

    // --- Feeds -------------------------------------------------------------

    /**
     * The precomputed feed — an indexed read of match_scores, not a scoring
     * pass. An INNER JOIN, because a row there means the nightly job has an
     * opinion about this candidate.
     *
     * A viewer with no scores at all (registered since the last run) would
     * otherwise get a blank screen on their first session, which is the worst
     * first impression the product can make. They fall through to cold start.
     */
    public function recommended(User $viewer, int $perPage): LengthAwarePaginator
    {
        if (! MatchScore::where('user_id', $viewer->id)->exists()) {
            return $this->coldStart($viewer, $perPage);
        }

        $query = $this->search->baseQuery($viewer)
            ->join('match_scores', function ($join) use ($viewer) {
                $join->on('match_scores.candidate_id', '=', 'user_profiles.user_id')
                    ->where('match_scores.user_id', '=', $viewer->id);
            })
            ->addSelect(['match_scores.score as match_score', 'match_scores.breakdown as match_reasons'])
            ->orderByDesc('match_scores.score')
            ->orderBy('user_profiles.user_id');

        return $this->decodeReasons($query->paginate($perPage));
    }

    public function newProfiles(User $viewer, int $perPage): LengthAwarePaginator
    {
        return $this->preferenceMatched($viewer)
            ->where('users.created_at', '>=', now()->subDays(self::NEW_PROFILE_DAYS))
            ->orderByDesc('users.created_at')
            ->orderBy('user_profiles.user_id')
            ->paginate($perPage);
    }

    public function recentlyActive(User $viewer, int $perPage): LengthAwarePaginator
    {
        return $this->preferenceMatched($viewer)
            ->where('users.last_active_at', '>=', now()->subDay())
            ->orderByDesc('users.last_active_at')
            ->orderBy('user_profiles.user_id')
            ->paginate($perPage);
    }

    /**
     * The connections list — both people accepted.
     *
     * Built without baseQuery, and that is deliberate: baseQuery applies
     * `appear_in_search`, and someone you are already connected to must not
     * disappear from your connections because they left search. Blocks still
     * apply, which is why excludeBlocked is reused rather than reimplemented.
     */
    public function mutual(User $viewer, int $perPage): LengthAwarePaginator
    {
        return UserProfile::query()
            ->select('user_profiles.*')
            ->join('users', 'users.id', '=', 'user_profiles.user_id')
            ->join('matches', function ($join) use ($viewer) {
                $join->where('matches.status', 'active')
                    ->where(function ($nested) use ($viewer) {
                        $nested
                            ->where(fn ($q) => $q
                                ->where('matches.user_one_id', $viewer->id)
                                ->whereColumn('matches.user_two_id', 'user_profiles.user_id'))
                            ->orWhere(fn ($q) => $q
                                ->where('matches.user_two_id', $viewer->id)
                                ->whereColumn('matches.user_one_id', 'user_profiles.user_id'));
                    });
            })
            ->where('users.status', 'active')
            ->whereNull('users.deleted_at')
            ->whereNull('user_profiles.deleted_at')
            ->tap(fn (Builder $q) => $this->search->excludeBlocked($q, $viewer))
            ->addSelect('matches.matched_at')
            ->with($this->relations())
            ->orderByDesc('matches.matched_at')
            ->orderBy('user_profiles.user_id')
            ->paginate($perPage);
    }

    /**
     * "Reference" — displays profiles matching the viewer's saved partner
     * preferences from Step 8 (`user_preferences`).
     */
    public function referenceMatch(User $viewer, int $perPage): LengthAwarePaginator
    {
        return $this->preferenceMatched($viewer)
            ->orderByDesc('user_profiles.is_verified')
            ->orderByDesc('user_profiles.completion_percentage')
            ->orderByDesc('users.last_active_at')
            ->orderBy('user_profiles.user_id')
            ->paginate($perPage);
    }

    /**
     * "Profile matched" — everyone who satisfies every criterion in the
     * viewer's saved partner preferences, with no extra time constraint
     * (unlike `newProfiles`/`recentlyActive`, which layer one on top of this
     * same base). Ranked by verification and completeness, same as cold
     * start, since there is no match_scores ordering to fall back on here.
     *
     * `raasis`/`stars` are an on-page refinement, not a saved preference —
     * UserPreference has no astrology columns, so these arrive as plain
     * request filters and are applied as a `whereIn` directly against
     * UserProfile::raasi / ::star (both free-text, like caste/gothra).
     */
    public function preferenceMatch(User $viewer, int $perPage, array $raasis = [], array $stars = []): LengthAwarePaginator
    {
        $query = $this->preferenceMatched($viewer);

        if ($raasis !== []) {
            $query->whereIn('user_profiles.raasi', $raasis);
        }

        if ($stars !== []) {
            $query->whereIn('user_profiles.star', $stars);
        }

        return $query
            ->orderByDesc('user_profiles.is_verified')
            ->orderByDesc('user_profiles.completion_percentage')
            ->orderByDesc('users.last_active_at')
            ->orderBy('user_profiles.user_id')
            ->paginate($perPage);
    }

    /**
     * "Caste matches" — same base as `preferenceMatch`, refined by community,
     * sub-community, caste and gothra instead of astrology. Community/
     * sub-community are FK ids (`user_profiles.community_id` /
     * `sub_community_id`); caste/gothra stay free-text `whereIn`, same as
     * raasi/star above.
     */
    public function casteMatch(User $viewer, int $perPage, array $communityIds = [], array $subCommunityIds = [], array $castes = [], array $gothras = []): LengthAwarePaginator
    {
        $query = $this->preferenceMatched($viewer);

        if ($communityIds !== []) {
            $query->whereIn('user_profiles.community_id', $communityIds);
        }

        if ($subCommunityIds !== []) {
            $query->whereIn('user_profiles.sub_community_id', $subCommunityIds);
        }

        if ($castes !== []) {
            $query->whereIn('user_profiles.caste', $castes);
        }

        if ($gothras !== []) {
            $query->whereIn('user_profiles.gothra', $gothras);
        }

        return $query
            ->orderByDesc('user_profiles.is_verified')
            ->orderByDesc('user_profiles.completion_percentage')
            ->orderByDesc('users.last_active_at')
            ->orderBy('user_profiles.user_id')
            ->paginate($perPage);
    }

    public function nearMe(User $viewer, int $perPage): LengthAwarePaginator
    {
        $profile = $viewer->profile;
        $cityId = $profile?->city_id;
        $stateId = $profile?->state_id;

        // Nothing to be near. An empty page is honest; the alternative is the
        // whole database in an arbitrary order under a "near me" heading.
        if (! $cityId && ! $stateId) {
            return new Paginator([], 0, $perPage, 1);
        }

        return $this->search->baseQuery($viewer)
            ->where(function (Builder $query) use ($cityId, $stateId) {
                if ($cityId) {
                    $query->orWhere('user_profiles.city_id', $cityId);
                }

                if ($stateId) {
                    $query->orWhere('user_profiles.state_id', $stateId);
                }
            })
            // Same city before same state. A "near me" list that opens with
            // someone 600 km away reads as broken.
            ->orderByRaw('CASE WHEN user_profiles.city_id = ? THEN 0 ELSE 1 END', [$cityId])
            ->orderByDesc('users.last_active_at')
            ->orderBy('user_profiles.user_id')
            ->paginate($perPage);
    }

    // --- Internals ---------------------------------------------------------

    /**
     * Preferences applied as filters rather than as scoring. These feeds answer
     * "who matches what I asked for"; the ranking is whatever ordering the feed
     * puts on top. A user with no preferences saved gets no constraint at all,
     * never an empty list.
     *
     * When a user saves many preference fields at once and no single candidate
     * satisfies 100% of them simultaneously, falls back to matching candidates
     * who satisfy at least one preference criterion, ranked by the number of
     * matched criteria descending.
     */
    protected function preferenceMatched(User $viewer): Builder
    {
        $prefs = $this->search->fromPreferences($viewer);
        $query = $this->search->baseQuery($viewer);

        if ($prefs === []) {
            return $query;
        }

        $strictQuery = clone $query;
        $this->filters->apply($strictQuery, $prefs);

        if ((clone $strictQuery)->exists()) {
            return $strictQuery;
        }

        return $this->applyFlexiblePreferences($query, $prefs);
    }

    protected function applyFlexiblePreferences(Builder $query, array $prefs): Builder
    {
        $flexQuery = clone $query;
        $scoreParts = [];
        $scoreBindings = [];

        $flexQuery->where(function (Builder $q) use ($prefs, &$scoreParts, &$scoreBindings) {
            [$earliest, $latest] = $this->search->ageWindow(
                isset($prefs['age_min']) ? (int) $prefs['age_min'] : null,
                isset($prefs['age_max']) ? (int) $prefs['age_max'] : null,
            );

            if ($earliest && $latest) {
                $q->orWhereBetween('users.date_of_birth', [$earliest, $latest]);
                $scoreParts[] = 'CASE WHEN users.date_of_birth BETWEEN ? AND ? THEN 2 ELSE 0 END';
                $scoreBindings[] = $earliest;
                $scoreBindings[] = $latest;
            } elseif ($earliest) {
                $q->orWhere('users.date_of_birth', '>=', $earliest);
                $scoreParts[] = 'CASE WHEN users.date_of_birth >= ? THEN 2 ELSE 0 END';
                $scoreBindings[] = $earliest;
            } elseif ($latest) {
                $q->orWhere('users.date_of_birth', '<=', $latest);
                $scoreParts[] = 'CASE WHEN users.date_of_birth <= ? THEN 2 ELSE 0 END';
                $scoreBindings[] = $latest;
            }

            if (isset($prefs['height_min']) && isset($prefs['height_max'])) {
                $q->orWhereBetween('user_profiles.height_cm', [(int) $prefs['height_min'], (int) $prefs['height_max']]);
                $scoreParts[] = 'CASE WHEN user_profiles.height_cm BETWEEN ? AND ? THEN 2 ELSE 0 END';
                $scoreBindings[] = (int) $prefs['height_min'];
                $scoreBindings[] = (int) $prefs['height_max'];
            } elseif (isset($prefs['height_min'])) {
                $q->orWhere('user_profiles.height_cm', '>=', (int) $prefs['height_min']);
                $scoreParts[] = 'CASE WHEN user_profiles.height_cm >= ? THEN 2 ELSE 0 END';
                $scoreBindings[] = (int) $prefs['height_min'];
            } elseif (isset($prefs['height_max'])) {
                $q->orWhere('user_profiles.height_cm', '<=', (int) $prefs['height_max']);
                $scoreParts[] = 'CASE WHEN user_profiles.height_cm <= ? THEN 2 ELSE 0 END';
                $scoreBindings[] = (int) $prefs['height_max'];
            }

            if (isset($prefs['weight_min']) && isset($prefs['weight_max'])) {
                $q->orWhereBetween('user_profiles.weight_kg', [(int) $prefs['weight_min'], (int) $prefs['weight_max']]);
                $scoreParts[] = 'CASE WHEN user_profiles.weight_kg BETWEEN ? AND ? THEN 1 ELSE 0 END';
                $scoreBindings[] = (int) $prefs['weight_min'];
                $scoreBindings[] = (int) $prefs['weight_max'];
            } elseif (isset($prefs['weight_min'])) {
                $q->orWhere('user_profiles.weight_kg', '>=', (int) $prefs['weight_min']);
                $scoreParts[] = 'CASE WHEN user_profiles.weight_kg >= ? THEN 1 ELSE 0 END';
                $scoreBindings[] = (int) $prefs['weight_min'];
            } elseif (isset($prefs['weight_max'])) {
                $q->orWhere('user_profiles.weight_kg', '<=', (int) $prefs['weight_max']);
                $scoreParts[] = 'CASE WHEN user_profiles.weight_kg <= ? THEN 1 ELSE 0 END';
                $scoreBindings[] = (int) $prefs['weight_max'];
            }

            $inColumns = [
                'marital_status_ids' => 'marital_status_id',
                'occupation_ids' => 'occupation_id',
                'income_range_ids' => 'income_range_id',
                'mother_tongue_ids' => 'mother_tongue_id',
                'state_ids' => 'state_id',
                'religion_ids' => 'religion_id',
                'community_ids' => 'community_id',
                'education_ids' => 'education_id',
                'complexions' => 'complexion',
                'raasis' => 'raasi',
                'stars' => 'star',
                'castes' => 'caste',
            ];

            foreach ($inColumns as $prefKey => $col) {
                $vals = array_values(array_filter((array) ($prefs[$prefKey] ?? []), fn ($v) => $v !== null && $v !== ''));
                if ($vals !== []) {
                    $q->orWhereIn("user_profiles.{$col}", $vals);
                    $placeholders = implode(',', array_fill(0, count($vals), '?'));
                    $scoreParts[] = "CASE WHEN user_profiles.{$col} IN ({$placeholders}) THEN 2 ELSE 0 END";
                    foreach ($vals as $v) {
                        $scoreBindings[] = $v;
                    }
                }
            }
        });

        if ($scoreParts !== []) {
            $scoreExpr = '(' . implode(' + ', $scoreParts) . ')';
            $flexQuery->orderByRaw("{$scoreExpr} DESC", $scoreBindings);
        }

        if ((clone $flexQuery)->exists()) {
            return $flexQuery;
        }

        return $query;
    }

    /**
     * No scores yet. Rank by the viewer's own background — same faith, same
     * mother tongue, same state — then by the signals that make any profile
     * worth showing. Weighted as a single expression so the ordering degrades
     * gracefully when the viewer has filled in only some of the three.
     */
    protected function coldStart(User $viewer, int $perPage): LengthAwarePaginator
    {
        $profile = $viewer->profile;

        return $this->search->baseQuery($viewer)
            ->orderByRaw(
                '(CASE WHEN user_profiles.religion_id = ? THEN 3 ELSE 0 END'
                .' + CASE WHEN user_profiles.mother_tongue_id = ? THEN 2 ELSE 0 END'
                .' + CASE WHEN user_profiles.state_id = ? THEN 1 ELSE 0 END) DESC',
                [$profile?->religion_id, $profile?->mother_tongue_id, $profile?->state_id],
            )
            ->orderByDesc('user_profiles.is_verified')
            ->orderByDesc('user_profiles.completion_percentage')
            ->orderByDesc('users.last_active_at')
            ->orderBy('user_profiles.user_id')
            ->paginate($perPage);
    }

    /**
     * `breakdown` is a JSON column on match_scores. Selected through a join it
     * arrives as a raw string, because the cast lives on the MatchScore model
     * and this query hydrates UserProfile.
     */
    protected function decodeReasons(LengthAwarePaginator $page): LengthAwarePaginator
    {
        foreach ($page->items() as $profile) {
            if (is_string($profile->match_reasons)) {
                $profile->match_reasons = json_decode($profile->match_reasons, true) ?: [];
            }
        }

        return $page;
    }

    /** Same relation set as search, so the mutual feed renders identically. */
    protected function relations(): array
    {
        return array_merge(UserProfile::WITH_LOOKUPS, [
            'user:id,name,profile_code,gender,date_of_birth,last_active_at',
            'user.privacySettings',
            'primaryPhoto',
        ]);
    }
}
