<?php

namespace App\Services\Analytics;

use App\Models\Interest;
use App\Models\Message;
use App\Models\Payment;
use App\Models\ProfilePhoto;
use App\Models\Report;
use App\Models\Subscription;
use App\Models\User;
use App\Models\UserProfile;
use App\Models\VerificationDocument;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Every dashboard number, aggregated server-side.
 *
 * The admin client never aggregates raw rows — shipping 128,000 users to a
 * browser to count them is the kind of thing that works in staging and takes
 * the panel down on launch day.
 *
 * Counts are cached briefly. A dashboard that is sixty seconds stale is fine;
 * a dashboard that runs twelve COUNT(*) queries against a million-row table on
 * every poll is not.
 */
class DashboardMetrics
{
    public const TTL = 60;

    public function stats(): array
    {
        return Cache::remember('admin.dashboard.stats', static::TTL, fn () => [
            'users' => $this->userStats(),
            'pending' => $this->pendingStats(),
            'engagement' => $this->engagementStats(),
            'revenue' => $this->revenueStats(),
        ]);
    }

    protected function userStats(): array
    {
        // One pass over users with conditional aggregates rather than eight
        // separate COUNT queries against the same table.
        $row = User::query()
            ->members()
            ->selectRaw('COUNT(*) as total')
            ->selectRaw("SUM(status = 'active') as active")
            ->selectRaw('SUM(DATE(created_at) = ?) as new_today', [now()->toDateString()])
            ->selectRaw('SUM(created_at >= ?) as new_this_month', [now()->startOfMonth()])
            ->selectRaw("SUM(gender = 'male') as male")
            ->selectRaw("SUM(gender = 'female') as female")
            ->first();

        return [
            'total' => (int) $row->total,
            'active' => (int) $row->active,
            'new_today' => (int) $row->new_today,
            'new_this_month' => (int) $row->new_this_month,
            'male' => (int) $row->male,
            'female' => (int) $row->female,
            'verified' => UserProfile::where('is_verified', true)->count(),
            'premium' => Subscription::active()->distinct('user_id')->count('user_id'),
        ];
    }

    protected function pendingStats(): array
    {
        return [
            'profile_approvals' => UserProfile::where('approval_status', 'pending')->count(),
            'photo_moderation' => ProfilePhoto::pending()->count(),
            'verifications' => VerificationDocument::whereIn('status', ['pending', 'resubmit'])->count(),
            'reports' => Report::open()->count(),
        ];
    }

    protected function engagementStats(): array
    {
        $today = now()->startOfDay();

        return [
            'interests_today' => Interest::where('created_at', '>=', $today)->count(),
            'messages_today' => Message::where('created_at', '>=', $today)->count(),
            'logins_today' => User::where('last_login_at', '>=', $today)->count(),
        ];
    }

    protected function revenueStats(): array
    {
        $row = Payment::query()
            ->paid()
            ->whereHas('user')
            ->selectRaw('SUM(CASE WHEN DATE(created_at) = ? THEN amount ELSE 0 END) as today', [now()->toDateString()])
            ->selectRaw('SUM(CASE WHEN created_at >= ? THEN amount ELSE 0 END) as this_month', [now()->startOfMonth()])
            ->first();

        return [
            'today' => (float) ($row->today ?? 0),
            'this_month' => (float) ($row->this_month ?? 0),
            'currency' => config('services.razorpay.currency'),
            'active_subscriptions' => Subscription::active()->whereHas('user')->count(),
            'expiring_in_7_days' => Subscription::expiring(7)->whereHas('user')->count(),
        ];
    }

    /** Time series for the dashboard charts. */
    public function chart(string $chart, int $days = 30): array
    {
        return Cache::remember("admin.dashboard.chart.{$chart}.{$days}", static::TTL, fn () => match ($chart) {
            'registrations' => $this->daily(User::query()->members(), 'created_at', $days),
            'revenue' => $this->daily(Payment::query()->paid()->whereHas('user'), 'created_at', $days, 'amount'),
            'subscriptions' => $this->daily(Subscription::query()->whereHas('user'), 'created_at', $days),
            'active-users' => $this->daily(User::query()->members(), 'last_active_at', $days),
            'demographics' => $this->demographics(),
            'locations' => $this->topLocations(),
            default => [],
        });
    }

    /**
     * Zero-filled: a chart that skips days with no data draws a misleading
     * line, because the gap renders as a straight segment rather than a dip.
     */
    protected function daily($query, string $column, int $days, ?string $sumColumn = null): array
    {
        $from = now()->subDays($days - 1)->startOfDay();

        $aggregate = $sumColumn ? "SUM({$sumColumn})" : 'COUNT(*)';

        $rows = $query
            ->where($column, '>=', $from)
            ->selectRaw("DATE({$column}) as day, {$aggregate} as value")
            ->groupBy('day')
            ->pluck('value', 'day');

        $series = [];

        for ($i = 0; $i < $days; $i++) {
            $day = $from->copy()->addDays($i)->toDateString();
            $series[] = ['date' => $day, 'value' => (float) ($rows[$day] ?? 0)];
        }

        return $series;
    }

    protected function demographics(): array
    {
        return [
            'by_religion' => DB::table('user_profiles')
                ->join('religions', 'religions.id', '=', 'user_profiles.religion_id')
                ->join('users', 'users.id', '=', 'user_profiles.user_id')
                ->whereNull('user_profiles.deleted_at')
                ->whereNull('users.deleted_at')
                ->selectRaw('religions.name as label, COUNT(*) as value')
                ->groupBy('religions.name')
                ->orderByDesc('value')
                ->limit(10)
                ->get(),

            // Eloquent, not DB::table: `members()` is a model scope, and the
            // query builder has no idea what a role is.
            'by_age_band' => User::query()
                ->members()
                ->selectRaw("CASE
                    WHEN TIMESTAMPDIFF(YEAR, date_of_birth, CURDATE()) < 25 THEN '18-24'
                    WHEN TIMESTAMPDIFF(YEAR, date_of_birth, CURDATE()) < 30 THEN '25-29'
                    WHEN TIMESTAMPDIFF(YEAR, date_of_birth, CURDATE()) < 35 THEN '30-34'
                    ELSE '35+' END as label, COUNT(*) as value")
                ->groupBy('label')
                ->orderBy('label')
                ->get(),
        ];
    }

    protected function topLocations(): array
    {
        return DB::table('user_profiles')
            ->join('cities', 'cities.id', '=', 'user_profiles.city_id')
            ->join('users', 'users.id', '=', 'user_profiles.user_id')
            ->whereNull('user_profiles.deleted_at')
            ->whereNull('users.deleted_at')
            ->selectRaw('cities.name as label, COUNT(*) as value')
            ->groupBy('cities.name')
            ->orderByDesc('value')
            ->limit(15)
            ->get()
            ->toArray();
    }
}
