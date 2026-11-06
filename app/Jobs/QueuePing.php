<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;

/**
 * Writes one cache key and nothing else. Dispatched by `matrimony:queue-ping`
 * during the post-deploy smoke test (docs/15-deployment.md §9).
 *
 * The check this exists for is not "is Redis reachable" — the health endpoint
 * already answers that from the web process. It is "did a **worker** pick this
 * up", which is a different question with a different failure mode: workers
 * that died, workers still running the previous release because `queue:restart`
 * did not reach them, or a supervisor that never came back after a reboot. All
 * three leave the site serving pages perfectly while every OTP, push and
 * invoice silently queues up forever.
 *
 * On the default queue deliberately. `high` would jump the line and pass while
 * a backlog on `default` is exactly what a smoke test should catch.
 */
class QueuePing implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public const CACHE_PREFIX = 'queue-ping:';

    /**
     * Long enough for the poller in the command to give up first, short enough
     * that a pile of stale ping keys never accumulates.
     */
    public const TTL = 120;

    /**
     * One retry only, and no backoff: a ping that needs a second attempt has
     * already failed the thing it was measuring.
     */
    public int $tries = 1;

    public function __construct(public readonly string $token)
    {
        $this->onQueue(config('queue.queues.default'));
    }

    public function handle(): void
    {
        Cache::put(self::CACHE_PREFIX.$this->token, now()->toIso8601String(), self::TTL);
    }
}
