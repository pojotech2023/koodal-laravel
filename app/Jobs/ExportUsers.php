<?php

namespace App\Jobs;

use App\Jobs\Concerns\WritesCsvExport;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * The member CSV.
 *
 * This is the single most damaging artefact the platform can emit — names,
 * ages, mobile numbers and locations of every member in one file — which is
 * why it is gated on its own `users.export` permission rather than riding on
 * `users.view`, why it lands on the private disk, and why completing it writes
 * an audit row naming the staff member who asked.
 *
 * Deliberately not the same query as `UserManagementController::index`, which
 * is a paginated read with eager-loaded relations. `chunkById` with explicit
 * joins keeps memory flat across 128,000 rows; hydrating that many models with
 * four relations each does not finish.
 */
class ExportUsers implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, WritesCsvExport;

    public int $tries = 2;

    public int $timeout = 900;

    /** @param  array<string, mixed>  $filters */
    public function __construct(
        public int $requesterId,
        public array $filters = [],
    ) {
        // `reports`, alongside the invoice renderer: a long export must not sit
        // in front of a chat push.
        $this->onQueue(config('queue.queues.reports'));
    }

    public function handle(): void
    {
        if (! $requester = User::find($this->requesterId)) {
            return;
        }

        $result = $this->writeCsv(
            filename: 'users-'.now()->format('Ymd-His').'-'.$this->requesterId.'.csv',
            headings: [
                'Profile code', 'Name', 'Gender', 'Date of birth', 'Age', 'Email', 'Mobile',
                'Status', 'Approval', 'Verified', 'Completion %', 'Plan', 'City', 'State',
                'Source', 'Registered at', 'Last active at',
            ],
            writeRows: function (callable $row) {
                $count = 0;

                $this->query()->chunkById(1000, function ($rows) use ($row, &$count) {
                    foreach ($rows as $user) {
                        $row([
                            $user->profile_code,
                            $user->name,
                            $user->gender,
                            $user->date_of_birth?->toDateString(),
                            $user->age(),
                            $user->email,
                            $user->mobile,
                            $user->status,
                            $user->approval_status,
                            $user->is_verified ? 'yes' : 'no',
                            $user->completion_percentage,
                            $user->plan_name,
                            $user->city_name,
                            $user->state_name,
                            $user->registration_source,
                            $user->created_at?->toDateTimeString(),
                            $user->last_active_at?->toDateTimeString(),
                        ]);

                        $count++;
                    }
                }, 'users.id', 'id');

                return $count;
            },
        );

        $this->announceExport($requester, 'members', $result);
    }

    /**
     * LEFT JOINs throughout, so a member with no profile, no city or no
     * subscription still appears. An INNER JOIN here would silently drop
     * exactly the incomplete accounts an export is usually run to find.
     */
    protected function query()
    {
        return User::query()
            ->members()
            ->leftJoin('user_profiles', 'user_profiles.user_id', '=', 'users.id')
            ->leftJoin('cities', 'cities.id', '=', 'user_profiles.city_id')
            ->leftJoin('states', 'states.id', '=', 'user_profiles.state_id')
            ->leftJoin('subscriptions', function ($join) {
                $join->on('subscriptions.user_id', '=', 'users.id')
                    ->where('subscriptions.status', '=', 'active');
            })
            ->leftJoin('membership_plans', 'membership_plans.id', '=', 'subscriptions.membership_plan_id')
            ->select([
                'users.id',
                'users.profile_code', 'users.name', 'users.gender', 'users.date_of_birth',
                'users.email', 'users.mobile', 'users.status', 'users.registration_source',
                'users.created_at', 'users.last_active_at',
                'user_profiles.approval_status', 'user_profiles.is_verified',
                'user_profiles.completion_percentage',
                'cities.name as city_name', 'states.name as state_name',
                'membership_plans.name as plan_name',
            ])
            ->when($this->filters['status'] ?? null, fn ($q, $v) => $q->where('users.status', $v))
            ->when($this->filters['gender'] ?? null, fn ($q, $v) => $q->where('users.gender', $v))
            ->when($this->filters['approval_status'] ?? null, fn ($q, $v) => $q->where('user_profiles.approval_status', $v))
            ->when(isset($this->filters['verified']), fn ($q) => $q->where('user_profiles.is_verified', filter_var($this->filters['verified'], FILTER_VALIDATE_BOOLEAN)))
            ->when($this->filters['plan_id'] ?? null, fn ($q, $v) => $q->where('subscriptions.membership_plan_id', $v))
            ->when($this->filters['from'] ?? null, fn ($q, $v) => $q->where('users.created_at', '>=', $v))
            ->when($this->filters['to'] ?? null, fn ($q, $v) => $q->where('users.created_at', '<=', $v));
    }
}
