<?php

namespace App\Services\Analytics;

use App\Models\Interest;
use App\Models\Message;
use App\Models\Payment;
use App\Models\Subscription;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * The four analytics reports, as distinct from the dashboard.
 *
 * `DashboardMetrics` answers "what is happening right now" in numbers small
 * enough to poll. This answers "what happened over a period" — breakdowns and
 * funnels an analyst reads once and then leaves alone. The split matters
 * because the queries here are heavier and the cache TTL is correspondingly
 * longer: a funnel that is fifteen minutes stale is still a funnel, and
 * recomputing it per page load would put four GROUP BYs over the whole user
 * table behind every refresh.
 *
 * Every report takes an explicit window. A report with an implicit "last 30
 * days" baked in is one that two people read differently and argue about.
 */
class AnalyticsReports
{
    public const TTL = 900;

    /** @return array<string, mixed> */
    public function report(string $report, Carbon $from, Carbon $to): array
    {
        $key = sprintf('admin.analytics.%s.%s.%s', $report, $from->toDateString(), $to->toDateString());

        return Cache::remember($key, static::TTL, fn () => match ($report) {
            'users' => $this->users($from, $to),
            'revenue' => $this->revenue($from, $to),
            'engagement' => $this->engagement($from, $to),
            'funnel' => $this->funnel($from, $to),
            default => [],
        });
    }

    // --- Reports -----------------------------------------------------------

    /**
     * Who joined, and who they are.
     *
     * Registration source is the one column that decides where marketing spend
     * goes, so it leads. The gender split is second because a matrimonial
     * platform with a lopsided ratio has a product problem long before it has
     * a revenue problem, and the number is invisible in a single total.
     */
    protected function users(Carbon $from, Carbon $to): array
    {
        $window = User::query()->members()->whereBetween('created_at', [$from, $to]);

        $totals = (clone $window)
            ->selectRaw('COUNT(*) as registered')
            ->selectRaw("SUM(status = 'active') as activated")
            ->selectRaw('SUM(mobile_verified_at IS NOT NULL) as mobile_verified')
            ->first();

        return [
            'registered' => (int) $totals->registered,
            'activated' => (int) $totals->activated,
            'mobile_verified' => (int) $totals->mobile_verified,

            'by_source' => (clone $window)
                ->selectRaw('registration_source as label, COUNT(*) as value')
                ->groupBy('registration_source')
                ->orderByDesc('value')
                ->get()
                ->toArray(),

            'by_gender' => (clone $window)
                ->selectRaw('gender as label, COUNT(*) as value')
                ->groupBy('gender')
                ->get()
                ->toArray(),

            'by_status' => (clone $window)
                ->selectRaw('status as label, COUNT(*) as value')
                ->groupBy('status')
                ->orderByDesc('value')
                ->get()
                ->toArray(),
        ];
    }

    /**
     * Money, gross and by plan.
     *
     * Refunds are reported separately rather than netted off. A month with
     * ₹40 lakh of sales and ₹8 lakh refunded is a different month from one
     * with ₹32 lakh of sales, and netting hides exactly the signal — a plan
     * people buy and immediately regret — that the report exists to surface.
     */
    protected function revenue(Carbon $from, Carbon $to): array
    {
        $paid = Payment::query()->paid()->whereHas('user')->whereBetween('created_at', [$from, $to]);

        $totals = (clone $paid)
            ->selectRaw('COUNT(*) as transactions')
            ->selectRaw('SUM(amount) as gross')
            ->selectRaw('AVG(amount) as average_order_value')
            ->first();

        $refunds = Payment::query()
            ->whereHas('user')
            ->whereNotNull('refund_id')
            ->whereBetween('updated_at', [$from, $to])
            ->selectRaw('COUNT(*) as count')
            ->selectRaw('SUM(refunded_amount) as amount')
            ->first();

        return [
            'currency' => config('services.razorpay.currency'),
            'transactions' => (int) $totals->transactions,
            'gross' => (float) ($totals->gross ?? 0),
            'average_order_value' => round((float) ($totals->average_order_value ?? 0), 2),

            'refunds' => [
                'count' => (int) $refunds->count,
                'amount' => (float) ($refunds->amount ?? 0),
            ],

            'by_plan' => (clone $paid)
                ->join('membership_plans', 'membership_plans.id', '=', 'payments.membership_plan_id')
                ->selectRaw('membership_plans.name as label')
                ->selectRaw('COUNT(*) as value')
                ->selectRaw('SUM(payments.amount) as amount')
                ->groupBy('membership_plans.name')
                ->orderByDesc('amount')
                ->get()
                ->toArray(),

            'by_method' => (clone $paid)
                ->selectRaw('COALESCE(method, \'unknown\') as label, COUNT(*) as value')
                ->groupBy('label')
                ->orderByDesc('value')
                ->get()
                ->toArray(),
        ];
    }

    /**
     * What members did, and how much of it landed.
     *
     * The acceptance rate is the number worth watching: interests sent is a
     * vanity metric that rises when the product nags people, and it rises
     * fastest right before acceptance collapses.
     */
    protected function engagement(Carbon $from, Carbon $to): array
    {
        $interests = Interest::query()
            ->whereBetween('created_at', [$from, $to])
            ->selectRaw('COUNT(*) as sent')
            ->selectRaw("SUM(status = 'accepted') as accepted")
            ->selectRaw("SUM(status = 'declined') as declined")
            ->first();

        $sent = (int) $interests->sent;

        return [
            'interests' => [
                'sent' => $sent,
                'accepted' => (int) $interests->accepted,
                'declined' => (int) $interests->declined,
                // Guarded: a window with no interests must report 0.0, not a
                // division by zero that takes the whole report down.
                'acceptance_rate' => $sent > 0 ? round((int) $interests->accepted / $sent * 100, 1) : 0.0,
            ],

            'messages' => Message::whereBetween('created_at', [$from, $to])->count(),

            'profile_views' => DB::table('profile_views')
                ->whereBetween('created_at', [$from, $to])
                ->count(),

            'shortlists' => DB::table('shortlists')
                ->whereBetween('created_at', [$from, $to])
                ->count(),

            // Distinct members who did anything at all. `last_active_at` is
            // maintained by RecordUserActivity, so this is a real active count
            // rather than a login count — the two differ by everyone who stays
            // signed in, which on mobile is nearly everyone.
            'active_members' => User::query()
                ->members()
                ->whereBetween('last_active_at', [$from, $to])
                ->count(),
        ];
    }

    /**
     * Registration → paid, as a chain of counts on the *same cohort*.
     *
     * Every step counts users who registered inside the window, not users who
     * took the step inside it. Mixing those is the classic way to produce a
     * funnel where a later stage exceeds an earlier one, which then gets
     * explained away instead of fixed.
     */
    protected function funnel(Carbon $from, Carbon $to): array
    {
        $cohort = User::query()->members()->whereBetween('created_at', [$from, $to]);

        $registered = (clone $cohort)->count();
        $verified = (clone $cohort)->whereNotNull('mobile_verified_at')->count();

        $cohortIds = (clone $cohort)->select('id');

        $profileCompleted = UserProfile::query()
            ->whereIn('user_id', $cohortIds)
            ->where('completion_percentage', '>=', 60)
            ->count();

        $withPhoto = UserProfile::query()
            ->whereIn('user_id', $cohortIds)
            ->whereNotNull('primary_photo_id')
            ->count();

        $sentInterest = Interest::query()
            ->whereIn('sender_id', $cohortIds)
            ->distinct('sender_id')
            ->count('sender_id');

        $subscribed = Subscription::query()
            ->whereIn('user_id', $cohortIds)
            ->whereIn('status', ['active', 'expired', 'cancelled'])
            ->distinct('user_id')
            ->count('user_id');

        $steps = [
            ['step' => 'registered', 'label' => 'Registered', 'value' => $registered],
            ['step' => 'mobile_verified', 'label' => 'Verified mobile', 'value' => $verified],
            ['step' => 'profile_completed', 'label' => 'Profile 60%+ complete', 'value' => $profileCompleted],
            ['step' => 'photo_uploaded', 'label' => 'Uploaded a photo', 'value' => $withPhoto],
            ['step' => 'sent_interest', 'label' => 'Sent an interest', 'value' => $sentInterest],
            ['step' => 'subscribed', 'label' => 'Bought a membership', 'value' => $subscribed],
        ];

        // Conversion is expressed against the top of the funnel, not against
        // the previous step. Step-to-step percentages read well and are almost
        // always misquoted as overall conversion.
        foreach ($steps as $i => $step) {
            $steps[$i]['percentage'] = $registered > 0 ? round($step['value'] / $registered * 100, 1) : 0.0;
        }

        return ['steps' => $steps];
    }
}
