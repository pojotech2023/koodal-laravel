<?php

namespace App\Jobs;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * The nightly fan-out. Enumerates who needs scoring and queues one job each;
 * the scoring itself is in RecomputeMatchScoresForUser.
 *
 * Split in two for a practical reason: one job per user means a single bad
 * profile fails its own job and retries, rather than taking the whole night's
 * run down at user 40,000 of 200,000. It also lets the work spread across every
 * worker on the `matching` queue instead of pinning one.
 *
 * Dormant accounts are skipped. Scoring someone who has not opened the app in
 * three months costs the same as scoring an active member and helps nobody —
 * and the moment they come back, LoginUser's triggered recompute catches them.
 */
class RecomputeMatchScores implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** Beyond this the account is dormant and is scored on return instead. */
    protected const ACTIVE_WITHIN_DAYS = 60;

    /** Big enough that the id scan is cheap, small enough to bound memory. */
    protected const CHUNK = 500;

    public function __construct()
    {
        $this->onQueue(config('queue.queues.matching'));
    }

    public function handle(): void
    {
        $queued = 0;

        $this->eligible()->chunkById(self::CHUNK, function ($users) use (&$queued) {
            foreach ($users as $user) {
                RecomputeMatchScoresForUser::dispatch($user->id);
                $queued++;
            }
        });

        // The one line that makes a silent night's run auditable. A sudden drop
        // here is the first sign the eligibility filter has gone wrong.
        Log::info('Match score recompute queued', ['users' => $queued]);
    }

    protected function eligible(): Builder
    {
        return User::query()
            ->select('users.id')
            ->members()
            ->where('users.status', 'active')
            ->where('users.last_active_at', '>=', now()->subDays(self::ACTIVE_WITHIN_DAYS))
            // No approved profile means nothing to score against and nothing
            // that could be shown in a result set anyway.
            ->whereHas('profile', fn (Builder $q) => $q->where('approval_status', 'approved'));
    }
}
