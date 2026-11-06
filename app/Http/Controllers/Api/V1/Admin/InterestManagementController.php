<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\InterestQueryRequest;
use App\Http\Resources\Admin\AdminInterestResource;
use App\Models\Interest;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * Read-only, and that is the whole design.
 *
 * There is no admin endpoint that sends, accepts, declines or deletes an
 * interest, because an interest is a statement one member made about another
 * and staff editing it would forge consent. What staff need is the ability to
 * *see* the pattern: `InterestPolicy::viewAny` describes the case exactly —
 * fifty interests to fifty strangers in an hour is what harassment looks like
 * in this table, and it is invisible from any one member's inbox.
 *
 * Two endpoints, deliberately different in cost:
 *
 *   • `index` is the investigation view — filtered, paginated, per-row.
 *   • `stats` is the health view — aggregate only, cached, no member data.
 *
 * Keeping them apart is what stops the funnel numbers from being recomputed on
 * every page of a paged investigation.
 */
class InterestManagementController extends Controller
{
    /**
     * Long enough that a moderator refreshing the page does not re-run six
     * aggregates over the interests table; short enough that the acceptance
     * rate on the screen is the one from this shift.
     */
    public const STATS_TTL = 300;

    public function index(InterestQueryRequest $request): JsonResponse
    {
        $this->authorize('viewAny', Interest::class);

        $interests = Interest::query()
            ->with([
                'sender:id,profile_code,name,gender,status',
                'receiver:id,profile_code,name,gender,status',
            ])

            ->status($request->string('status')->toString() ?: null)

            // `sender_id` / `receiver_id` filter one direction; `user_id`
            // filters both, which is the question actually being asked when a
            // report names an account — "what has this person been doing" does
            // not mean "what have they sent".
            ->when($request->integer('sender_id'), fn ($q, $id) => $q->where('sender_id', $id))
            ->when($request->integer('receiver_id'), fn ($q, $id) => $q->where('receiver_id', $id))
            ->when($request->integer('user_id'), fn ($q, $id) => $q->where(
                fn ($w) => $w->where('sender_id', $id)->orWhere('receiver_id', $id)
            ))

            // Interests carrying a message are where abuse lives — 500
            // characters that reach a stranger before any consent.
            ->when($request->boolean('with_message'), fn ($q) => $q->whereNotNull('message'))

            ->when($request->date('from'), fn ($q, $v) => $q->where('created_at', '>=', $v))
            ->when($request->date('to'), fn ($q, $v) => $q->where('created_at', '<=', $v))

            ->latest('id')
            ->paginate($request->integer('per_page', 25));

        $this->hydrateVolume($interests->getCollection());

        return ApiResponse::paginated($interests, AdminInterestResource::class, 'Interests retrieved successfully');
    }

    /**
     * Platform-level interest health. No member appears in this payload.
     *
     * The acceptance rate is the number this exists for: it is the closest
     * single proxy for whether matching is working. A falling rate with rising
     * volume means the feed is serving worse candidates, and neither figure
     * shows it alone.
     */
    public function stats(): JsonResponse
    {
        $this->authorize('viewAny', Interest::class);

        return ApiResponse::success(
            Cache::remember('admin.interests.stats', static::STATS_TTL, fn () => $this->compute()),
            'Interest statistics retrieved successfully',
        );
    }

    // --- Internals ---------------------------------------------------------

    /**
     * One caveat that shapes every number below: `interests` carries a unique
     * index on (sender_id, receiver_id), so a row is a *pair*, not an event. A
     * total here is "how many people have ever reached out to how many others",
     * which is the figure the funnel wants, but it is not a count of taps.
     *
     * @return array<string, mixed>
     */
    protected function compute(): array
    {
        // One pass with conditional aggregates rather than six COUNT queries
        // over the same table — the same shape DashboardMetrics uses.
        //
        // `cancelled` is a withdrawal: the sender pulled the interest back
        // before it was answered. It is a status, not a soft delete — nothing
        // in the interest paths ever sets `deleted_at`, so the default scope is
        // the whole table.
        $totals = Interest::query()
            ->selectRaw('COUNT(*) as total')
            ->selectRaw("SUM(status = 'pending') as pending")
            ->selectRaw("SUM(status = 'accepted') as accepted")
            ->selectRaw("SUM(status = 'declined') as declined")
            ->selectRaw("SUM(status = 'cancelled') as cancelled")
            ->selectRaw('SUM(DATE(created_at) = ?) as today', [now()->toDateString()])
            ->selectRaw('SUM(created_at >= ?) as this_month', [now()->startOfMonth()])
            ->first();

        $responded = (int) $totals->accepted + (int) $totals->declined;

        return [
            'total' => (int) $totals->total,
            'pending' => (int) $totals->pending,
            'accepted' => (int) $totals->accepted,
            'declined' => (int) $totals->declined,
            'cancelled' => (int) $totals->cancelled,

            'today' => (int) $totals->today,
            'this_month' => (int) $totals->this_month,

            // Of the interests that got an answer — not of all of them. A
            // pending interest has not been declined, and counting it as one
            // makes the rate a function of how recently the report was run.
            'acceptance_rate' => $responded > 0
                ? round((int) $totals->accepted / $responded * 100, 1)
                : 0.0,

            'median_response_hours' => $this->medianResponseHours(),
            'daily' => $this->daily(),
            'top_senders' => $this->topSenders(),
        ];
    }

    /**
     * How long a member waits for an answer.
     *
     * Deliberately the median and not the mean: the distribution has a long
     * tail of interests answered three weeks later, and an average pulled up by
     * those describes nobody's experience. Computed in PHP over the last 5,000
     * answered interests rather than in SQL, because MySQL 8 has no median
     * aggregate and the window-function workaround costs more than sorting
     * 5,000 integers.
     *
     * `toBase()` keeps the model's scopes but skips hydration — 5,000 Interest
     * models to read one computed column each is work with no consumer.
     */
    protected function medianResponseHours(): ?float
    {
        $minutesList = Interest::query()
            ->whereNotNull('responded_at')
            ->selectRaw('TIMESTAMPDIFF(MINUTE, created_at, responded_at) as minutes')
            ->orderByDesc('id')
            ->limit(5000)
            ->toBase()
            ->get()
            ->map(fn ($row) => (int) $row->minutes)
            ->sort()
            ->values();

        if ($minutesList->isEmpty()) {
            return null;
        }

        $mid = intdiv($minutesList->count(), 2);

        $minutes = $minutesList->count() % 2 === 1
            ? $minutesList[$mid]
            : ($minutesList[$mid - 1] + $minutesList[$mid]) / 2;

        return round($minutes / 60, 1);
    }

    /**
     * Sent and accepted per day for the trailing fortnight, in one grouped
     * query. Two series so the panel can draw them together — volume without
     * acceptance is a vanity chart.
     *
     * @return array<int, array<string, mixed>>
     */
    protected function daily(): array
    {
        return Interest::query()
            ->where('created_at', '>=', now()->subDays(14)->startOfDay())
            ->selectRaw('DATE(created_at) as date')
            ->selectRaw('COUNT(*) as sent')
            ->selectRaw("SUM(status = 'accepted') as accepted")
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->map(fn ($row) => [
                'date' => (string) $row->date,
                'sent' => (int) $row->sent,
                'accepted' => (int) $row->accepted,
            ])
            ->all();
    }

    /**
     * The ten accounts sending the most interests this week, with how many
     * were accepted.
     *
     * This is the abuse signal, and the acceptance count is what makes it one.
     * High volume alone is a keen member; high volume with near-zero acceptance
     * is someone messaging everybody. Profile codes rather than names — this
     * list is a starting point for an investigation, not a leaderboard.
     *
     * @return array<int, array<string, mixed>>
     */
    protected function topSenders(): array
    {
        return Interest::query()
            ->join('users', 'users.id', '=', 'interests.sender_id')
            ->where('interests.created_at', '>=', now()->subDays(7))
            ->selectRaw('users.id, users.profile_code')
            ->selectRaw('COUNT(*) as sent')
            ->selectRaw("SUM(interests.status = 'accepted') as accepted")
            ->groupBy('users.id', 'users.profile_code')
            ->orderByDesc('sent')
            ->limit(10)
            ->get()
            ->map(fn ($row) => [
                'user_id' => (int) $row->id,
                'profile_code' => $row->profile_code,
                'sent' => (int) $row->sent,
                'accepted' => (int) $row->accepted,
            ])
            ->all();
    }

    /**
     * How many interests each sender on this page has ever sent.
     *
     * The one number a moderator cannot get from the row in front of them, and
     * it changes the reading of the row completely. One grouped query per page
     * — the pattern `ReportManagementController::hydrateHistory` established —
     * rather than a correlated subquery, which would make the query plan depend
     * on the page size.
     *
     * @param  Collection<int, Interest>  $interests
     */
    protected function hydrateVolume($interests): void
    {
        $senderIds = $interests->pluck('sender_id')->filter()->unique();

        if ($senderIds->isEmpty()) {
            return;
        }

        $counts = Interest::query()
            ->whereIn('sender_id', $senderIds)
            ->selectRaw('sender_id, COUNT(*) as total')
            ->groupBy('sender_id')
            ->pluck('total', 'sender_id');

        foreach ($interests as $interest) {
            $interest->sender_sent_count = (int) ($counts[$interest->sender_id] ?? 0);
        }
    }
}
