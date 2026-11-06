<?php

namespace App\Jobs;

use App\Models\User;
use App\Models\UserProfile;
use App\Services\Matching\MatchScorer;
use App\Services\Search\SearchService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use SplMinHeap;

/**
 * Scores one member against the candidate pool and stores the top N.
 *
 * Also queued on its own when something invalidates a user's ranking —
 * preferences changed, verification approved, a move to another city — which is
 * why it is unique per user: a member who edits three preference fields in
 * quick succession should cause one recompute, not three.
 *
 * The shape of the work:
 *
 *   1. Hard filters in SQL produce the pool. Only the constraints that are
 *      genuinely binary live here (gender, active, approved, not blocked).
 *      Soft criteria stay soft — hard-filtering on religion or income is how a
 *      user ends up with an empty Matches tab and concludes nobody is here.
 *
 *   2. Scoring runs in PHP over chunks, keeping only the best N in a bounded
 *      min-heap rather than an array of every score. O(n log N) time and
 *      O(N) memory instead of holding 5,000 rows to sort and throw away.
 *
 *   3. Upsert the survivors, then prune anything this run did not touch.
 */
class RecomputeMatchScoresForUser implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The pool cap. The spec expects 500–5,000 candidates after hard filters;
     * this is the ceiling that keeps one member on a very common demographic
     * from turning into an hour of scoring.
     */
    protected const POOL_LIMIT = 5000;

    /** Rows held in memory at once while scoring. */
    protected const CHUNK = 500;

    /** Rows per upsert. Larger packets start hitting max_allowed_packet. */
    protected const WRITE_CHUNK = 500;

    public int $tries = 2;

    /** A stale lock must never block tomorrow's run. */
    public int $uniqueFor = 3600;

    public function __construct(public int $userId)
    {
        $this->onQueue(config('queue.queues.matching'));
    }

    public function uniqueId(): string
    {
        return (string) $this->userId;
    }

    public function handle(MatchScorer $scorer, SearchService $search): void
    {
        $user = User::with(['profile', 'preferences'])->find($this->userId);

        if (! $user || $user->status !== 'active' || ! $user->profile?->isApproved()) {
            return;
        }

        $viewer = $user->profile;

        // Every Criterion reaches through $viewer->user->preferences. Wiring
        // both relations by hand means that walk is free instead of two queries
        // per candidate.
        $viewer->setRelation('user', $user);

        $startedAt = now();
        $best = $this->scorePool($scorer, $search, $user, $viewer);

        $this->persist($user, $best, $startedAt);
    }

    /**
     * @return SplMinHeap<array{candidate_id: int, score: int, breakdown: array}>
     */
    protected function scorePool(MatchScorer $scorer, SearchService $search, User $user, UserProfile $viewer): SplMinHeap
    {
        $keep = (int) config('matrimony.matching.store_top_n');

        // Min-heap: the root is the weakest candidate currently kept, so
        // deciding whether a new score belongs in the top N is one comparison.
        $best = new class extends SplMinHeap
        {
            protected function compare($value1, $value2): int
            {
                return $value2['score'] <=> $value1['score'];
            }
        };

        $seen = 0;

        $search->baseQuery($user)
            // baseQuery loads every lookup relation for rendering profile
            // cards. Scoring reads ids and two columns off the user, so the
            // eager loads are dropped and replaced with what is actually used.
            ->setEagerLoads([])
            ->with(['user:id,date_of_birth,last_active_at'])
            ->chunkById(self::CHUNK, function ($candidates) use ($scorer, $viewer, $best, $keep, &$seen) {
                foreach ($candidates as $candidate) {
                    $score = $scorer->score($viewer, $candidate);

                    if ($best->count() < $keep) {
                        $best->insert($this->entry($candidate, $score));
                    } elseif ($score->value > $best->top()['score']) {
                        $best->extract();
                        $best->insert($this->entry($candidate, $score));
                    }
                }

                $seen += $candidates->count();

                // Returning false stops the walk. The pool is ordered by id, so
                // the cap is arbitrary rather than biased — which is the honest
                // trade for bounding a job that must finish before morning.
                return $seen < self::POOL_LIMIT;
            }, 'user_profiles.user_id', 'user_id');

        return $best;
    }

    /** @return array{candidate_id: int, score: int, breakdown: array} */
    protected function entry(UserProfile $candidate, $score): array
    {
        return [
            'candidate_id' => (int) $candidate->user_id,
            'score' => $score->value,
            'breakdown' => $score->breakdown,
        ];
    }

    /**
     * Upsert, then prune what this run did not write.
     *
     * Not delete-then-insert: that leaves a window — brief, but during it the
     * user's Matches tab is empty — and the composite primary key already does
     * the deduplication an upsert needs.
     */
    protected function persist(User $user, SplMinHeap $best, \DateTimeInterface $startedAt): void
    {
        $rows = [];

        // Iterating an SplHeap consumes it, which is exactly what is wanted
        // here: the heap is not needed after this.
        foreach ($best as $entry) {
            $rows[] = [
                'user_id' => $user->id,
                'candidate_id' => $entry['candidate_id'],
                'score' => $entry['score'],
                // Written through the query builder, so the model's array cast
                // does not apply and the encode has to be explicit.
                'breakdown' => json_encode($entry['breakdown']),
                'computed_at' => $startedAt,
            ];
        }

        DB::transaction(function () use ($user, $rows, $startedAt) {
            foreach (array_chunk($rows, self::WRITE_CHUNK) as $chunk) {
                DB::table('match_scores')->upsert(
                    $chunk,
                    ['user_id', 'candidate_id'],
                    ['score', 'breakdown', 'computed_at'],
                );
            }

            // Anything older than this run is a candidate who dropped out of
            // the top N — or out of the pool entirely.
            DB::table('match_scores')
                ->where('user_id', $user->id)
                ->where('computed_at', '<', $startedAt)
                ->delete();
        });
    }
}
