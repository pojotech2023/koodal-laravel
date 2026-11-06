<?php

namespace App\Console\Commands;

use App\Jobs\QueuePing;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * `php artisan matrimony:queue-ping` — dispatch one job and wait for a worker
 * to run it.
 *
 * The post-deploy smoke test's "a queued job completes" check
 * (docs/15-deployment.md §9). Exits 0 only when a worker actually executed the
 * job; every other outcome — no worker, a worker still on the old release, a
 * connection the web process can write to but no worker reads — exits non-zero,
 * which is what makes it usable as the last gate in `deploy.sh` before the
 * release is declared good.
 *
 * Polls rather than blocks because there is nothing to block on: the worker is
 * a different process on a different host, and the only shared signal is the
 * cache key the job writes.
 */
class QueuePingCommand extends Command
{
    protected $signature = 'matrimony:queue-ping
                            {--timeout=30 : Seconds to wait for a worker to run the job}';

    protected $description = 'Dispatch a no-op job and verify a queue worker executes it';

    public function handle(): int
    {
        $timeout = max(1, (int) $this->option('timeout'));

        // A fresh token per run. Reusing a fixed key would pass forever off the
        // first successful run's leftover value.
        $token = (string) Str::uuid();

        QueuePing::dispatch($token);

        $this->components->info("Dispatched ping {$token}; waiting up to {$timeout}s.");

        $deadline = microtime(true) + $timeout;
        $key = QueuePing::CACHE_PREFIX.$token;

        while (microtime(true) < $deadline) {
            if ($ranAt = Cache::get($key)) {
                Cache::forget($key);

                $elapsed = round($timeout - ($deadline - microtime(true)), 2);
                $this->components->info("Worker ran the job at {$ranAt} ({$elapsed}s).");

                return self::SUCCESS;
            }

            // 250ms: fast enough that a healthy queue reports in well under a
            // second, slow enough not to hammer Redis while a slow one drains.
            usleep(250_000);
        }

        $this->components->error(
            "No worker ran the ping within {$timeout}s. Check that the queue workers ".
            'are running, are on the current release, and are consuming the '.
            config('queue.queues.default').' queue.'
        );

        return self::FAILURE;
    }
}
