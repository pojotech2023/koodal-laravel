<?php

namespace App\Services\Profile;

use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Fills in the `viewer_state` block ProfileResource renders — shortlisted,
 * interest status, matched.
 *
 * Three queries for a whole page, never one per profile. Without it the client
 * has to make N extra calls just to decide whether to draw a filled or an
 * outlined heart, and the button flickers after mount.
 *
 * Deliberately not folded into SearchService's query as correlated subqueries:
 * the search plan then changes shape depending on whether a viewer is attached,
 * and the three lookups here are all covered by unique indexes anyway.
 */
class ViewerState
{
    /**
     * @param  iterable<UserProfile>  $profiles
     */
    public function hydrate(iterable $profiles, User $viewer): void
    {
        $profiles = $profiles instanceof Collection ? $profiles : collect($profiles);

        $candidateIds = $profiles->pluck('user_id')->filter()->unique()->values()->all();

        if ($candidateIds === []) {
            return;
        }

        $shortlisted = $this->shortlisted($viewer, $candidateIds);
        $interests = $this->interestStatuses($viewer, $candidateIds);
        $matched = $this->matched($viewer, $candidateIds);

        foreach ($profiles as $profile) {
            $id = (int) $profile->user_id;

            $profile->viewer_shortlisted = isset($shortlisted[$id]);
            $profile->viewer_is_matched = isset($matched[$id]);

            // Null rather than false: "never sent" and "declined" are different
            // buttons on the card, and ProfileResource omits the key entirely
            // when there is no interest to report.
            $profile->viewer_interest_status = $interests[$id] ?? null;
        }
    }

    /** Convenience for a single profile — the same three queries, one row. */
    public function hydrateOne(UserProfile $profile, User $viewer): UserProfile
    {
        $this->hydrate([$profile], $viewer);

        return $profile;
    }

    /** @return array<int, int> keyed by candidate id */
    protected function shortlisted(User $viewer, array $candidateIds): array
    {
        return DB::table('shortlists')
            ->where('user_id', $viewer->id)
            ->whereIn('shortlisted_user_id', $candidateIds)
            ->pluck('shortlisted_user_id')
            ->map(fn ($id) => (int) $id)
            ->flip()
            ->all();
    }

    /**
     * Only interests the viewer sent. A received interest is not viewer state —
     * it belongs on the received list, and showing it as "sent" on a card is
     * how a user ends up thinking they already reached out.
     *
     * @return array<int, string>
     */
    protected function interestStatuses(User $viewer, array $candidateIds): array
    {
        return DB::table('interests')
            ->where('sender_id', $viewer->id)
            ->whereIn('receiver_id', $candidateIds)
            ->whereNull('deleted_at')
            ->pluck('status', 'receiver_id')
            ->all();
    }

    /**
     * The pair is stored canonically (user_one_id < user_two_id), so the viewer
     * can sit on either side and both directions have to be asked for.
     *
     * @return array<int, int> keyed by the other user's id
     */
    protected function matched(User $viewer, array $candidateIds): array
    {
        return DB::table('matches')
            ->where('status', 'active')
            ->where(function ($query) use ($viewer, $candidateIds) {
                $query
                    ->where(fn ($q) => $q->where('user_one_id', $viewer->id)->whereIn('user_two_id', $candidateIds))
                    ->orWhere(fn ($q) => $q->where('user_two_id', $viewer->id)->whereIn('user_one_id', $candidateIds));
            })
            ->get(['user_one_id', 'user_two_id'])
            ->map(fn ($row) => ((int) $row->user_one_id === $viewer->id) ? (int) $row->user_two_id : (int) $row->user_one_id)
            ->flip()
            ->all();
    }
}
