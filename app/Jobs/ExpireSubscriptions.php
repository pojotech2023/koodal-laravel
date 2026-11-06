<?php

namespace App\Jobs;

use App\Models\Payment;
use App\Models\Subscription;
use App\Services\Membership\FeatureGate;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Retires subscriptions whose term has ended, hourly.
 *
 * Hourly rather than nightly because `FeatureGate` reads
 * `User::activeSubscription`, which is a status filter — a membership that
 * ended at 09:00 keeps working until the job runs, and "until midnight" is
 * eight free hours per member. The `active()` scope already excludes expired
 * rows by date, so this is belt-and-braces for the read path and the *record*
 * for everything that reports on it.
 *
 * Two things happen per row, and the second is the one that matters: the
 * user's cached feature map is flushed. Without that, a lapsed member keeps
 * premium behaviour for up to five more minutes per cache entry — and, worse,
 * every request re-warms it from a subscription that is now `expired`, which
 * resolves to the free plan anyway. Flushing makes the transition immediate
 * rather than eventually-correct.
 *
 * Also sweeps abandoned checkouts, per the payments spec: an order nobody paid
 * within its window is marked `expired` so the reconciliation report is not a
 * list of things that never happened.
 */
class ExpireSubscriptions implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected const CHUNK = 500;

    public function __construct()
    {
        $this->onQueue(config('queue.queues.default'));
    }

    public function handle(FeatureGate $features): void
    {
        $expired = 0;

        Subscription::query()
            ->with('user')
            ->where('status', 'active')
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now())
            ->chunkById(self::CHUNK, function ($subscriptions) use ($features, &$expired) {
                foreach ($subscriptions as $subscription) {
                    $subscription->update(['status' => 'expired']);

                    if ($subscription->user) {
                        $features->flush($subscription->user);
                    }

                    $expired++;
                }
            });

        $abandoned = $this->expireStaleOrders();

        Log::channel('payments')->info('Subscription sweep complete', [
            'expired' => $expired,
            'abandoned_orders' => $abandoned,
        ]);
    }

    /**
     * A single UPDATE. These rows have no side effects to run — nobody was
     * charged, no entitlement was granted — so iterating them would be work for
     * its own sake.
     */
    protected function expireStaleOrders(): int
    {
        return Payment::query()
            ->where('status', 'created')
            ->where('created_at', '<', now()->subDays(config('matrimony.retention.unpaid_orders')))
            ->update(['status' => 'expired', 'failure_reason' => 'checkout_abandoned']);
    }
}
