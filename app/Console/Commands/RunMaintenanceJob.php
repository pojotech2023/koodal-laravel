<?php

namespace App\Console\Commands;

use App\Jobs\ExpireSubscriptions;
use App\Jobs\PruneStaleRecords;
use App\Jobs\RecomputeMatchScores;
use App\Jobs\RecomputeMatchScoresForUser;
use App\Jobs\SendExpiryReminders;
use App\Jobs\SendNotificationDigest;
use App\Models\User;
use Illuminate\Console\Command;

/**
 * `php artisan matrimony:run <job>` — dispatch a scheduled job by hand.
 *
 * `routes/console.php` schedules six jobs and until now none of them could be
 * triggered manually. That matters on the days it matters most: a member says
 * their membership expired and still works, and the answer is to run
 * `ExpireSubscriptions` and look — not to wait for the top of the hour, and
 * certainly not to open Tinker on production.
 *
 * One command with a job argument rather than six commands. They share their
 * whole surface — pick a job, decide sync or queued — and six classes differing
 * by one line each is five files of copied boilerplate.
 *
 * `--sync` is the reason this is useful rather than decorative: run on the
 * caller's terminal, exceptions land on the screen instead of in
 * `failed_jobs`, and the output is the answer to the question being asked.
 * Queued is still the default, because on a large table the sync path is a
 * command that appears to hang.
 */
class RunMaintenanceJob extends Command
{
    protected $signature = 'matrimony:run
                            {job : expire-subscriptions|expiry-reminders|match-scores|digest|prune}
                            {--user= : Narrow to one member by id or profile code (match-scores, digest)}
                            {--sync : Run now on this process instead of pushing to the queue}';

    protected $description = 'Dispatch one of the scheduled maintenance jobs on demand';

    /** Argument value → the job it names. Keys are what an operator types. */
    protected const JOBS = [
        'expire-subscriptions' => ExpireSubscriptions::class,
        'expiry-reminders' => SendExpiryReminders::class,
        'match-scores' => RecomputeMatchScores::class,
        'digest' => SendNotificationDigest::class,
        'prune' => PruneStaleRecords::class,
    ];

    public function handle(): int
    {
        $name = (string) $this->argument('job');

        if (! isset(static::JOBS[$name])) {
            $this->components->error(
                "Unknown job [{$name}]. One of: ".implode(', ', array_keys(static::JOBS)).'.'
            );

            return self::FAILURE;
        }

        if (! $job = $this->build($name)) {
            return self::FAILURE;
        }

        // `PruneStaleRecords` deletes photographs and hard-deletes accounts, and
        // it takes no arguments to narrow that down — so it is the one job here
        // that gets asked twice. Not a `--force` flag: a flag in a runbook stops
        // being read after the second time somebody follows it.
        if ($name === 'prune' && ! $this->confirmDestructive()) {
            $this->components->warn('Cancelled.');

            return self::SUCCESS;
        }

        if ($this->option('sync')) {
            $this->components->info("Running {$name} on this process…");

            dispatch_sync($job);

            $this->components->info('Done. Check the log for what it did.');

            return self::SUCCESS;
        }

        dispatch($job);

        $this->components->info("Queued {$name}. It runs when a worker picks it up.");

        return self::SUCCESS;
    }

    // --- Internals ---------------------------------------------------------

    /** @return object|null the job, or null when the options do not resolve */
    protected function build(string $name): ?object
    {
        $class = static::JOBS[$name];

        if (! $identifier = $this->option('user')) {
            return new $class;
        }

        // Only two of the five can be narrowed to one member, and they express
        // it differently — `match-scores` has a whole separate class for the
        // single-user path (different queue, different timeout, `ShouldBeUnique`
        // keyed on the id), while the digest takes a nullable constructor
        // argument. From an operator's point of view both are "the same job, for
        // this one person", so both take `--user`.
        if (! in_array($name, ['match-scores', 'digest'], true)) {
            $this->components->error("--user does not apply to {$name}.");

            return null;
        }

        if (! $user = $this->findUser((string) $identifier)) {
            $this->components->error("No member matched [{$identifier}].");

            return null;
        }

        $this->components->info("Narrowed to {$user->profile_code} ({$user->name}).");

        return $name === 'match-scores'
            ? new RecomputeMatchScoresForUser($user->id)
            : new SendNotificationDigest($user->id);
    }

    /** Accepts either a numeric id or a profile code, because operators have both. */
    protected function findUser(string $identifier): ?User
    {
        return is_numeric($identifier)
            ? User::find((int) $identifier)
            : User::where('profile_code', strtoupper($identifier))->first();
    }

    protected function confirmDestructive(): bool
    {
        $retention = config('matrimony.retention');

        $this->components->warn(sprintf(
            'PruneStaleRecords permanently deletes: profile views older than %d days, '
            .'audit logs older than %d, export CSVs older than %d, and accounts soft-deleted '
            .'more than %d days ago — including their photographs and ID documents.',
            $retention['profile_views'],
            $retention['audit_logs'],
            $retention['exports'],
            $retention['soft_deleted_accounts'],
        ));

        return $this->confirm('Run it?', false);
    }
}
