<?php

namespace App\Jobs;

use App\Models\Subscription;
use App\Services\Notification\NotificationDispatcher;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * "Your Gold membership expires in 3 days."
 *
 * The reminder days come from `config('matrimony.notifications.expiry_reminder_days')`
 * — 7, 3 and 1 — and each one fires **at most once per subscription**, tracked
 * in `subscriptions.reminders_sent`. That column is the whole design: the job
 * runs daily and a naive "expires within 3 days" filter matches on day 3, day 2
 * and day 1, so the same member is nagged three times about the same deadline
 * and the renewal email starts reading as spam.
 *
 * Longest window first. A subscription two days out matches both the 7-day and
 * the 3-day bucket; taking the tightest *unsent* one means the copy says "2
 * days", not "a week".
 */
class SendExpiryReminders implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected const CHUNK = 200;

    public function __construct()
    {
        $this->onQueue(config('queue.queues.default'));
    }

    public function handle(NotificationDispatcher $notifications): void
    {
        $days = collect(config('matrimony.notifications.expiry_reminder_days'))
            ->sort()
            ->values();

        $sent = 0;

        Subscription::query()
            ->with(['user', 'plan'])
            ->where('status', 'active')
            ->whereNotNull('expires_at')
            ->whereBetween('expires_at', [now(), now()->addDays((int) $days->max())])
            ->chunkById(self::CHUNK, function ($subscriptions) use ($notifications, $days, &$sent) {
                foreach ($subscriptions as $subscription) {
                    if ($this->remind($notifications, $subscription, $days->all())) {
                        $sent++;
                    }
                }
            });

        Log::info('Expiry reminders sent', ['count' => $sent]);
    }

    /**
     * @param  array<int, int>  $days
     */
    protected function remind(NotificationDispatcher $notifications, Subscription $subscription, array $days): bool
    {
        if (! $subscription->user || ! $subscription->plan) {
            return false;
        }

        // Rounded up: a membership with 2.4 days left is "3 days", because a
        // user told "2 days" who renews on day 3 has been told the wrong thing.
        $remaining = (int) ceil(now()->diffInHours($subscription->expires_at, false) / 24);

        $bucket = $this->bucketFor($remaining, $days, $subscription);

        if ($bucket === null) {
            return false;
        }

        $notifications->send(
            user: $subscription->user,
            type: 'membership_expiring',
            title: $remaining <= 1
                ? "Your {$subscription->plan->name} membership expires tomorrow"
                : "Your {$subscription->plan->name} membership expires in {$remaining} days",
            body: 'Renew now to keep your matches, contact views and chat without a break.',
            data: [
                'subscription_id' => $subscription->id,
                'plan_id' => $subscription->plan->id,
                'expires_at' => $subscription->expires_at?->toIso8601String(),
                'days_remaining' => $remaining,
            ],
        );

        // Written after the send, and unconditionally: a dispatcher that
        // returned null because the user muted membership notifications has
        // still had its chance at this bucket. Retrying it tomorrow would mean
        // one muted member is re-evaluated every single day forever.
        $subscription->update([
            'reminders_sent' => array_values(array_unique(
                array_merge((array) ($subscription->reminders_sent ?? []), [$bucket]),
            )),
        ]);

        return true;
    }

    /**
     * The tightest bucket this subscription has reached and not yet been told
     * about.
     *
     * @param  array<int, int>  $days  ascending
     */
    protected function bucketFor(int $remaining, array $days, Subscription $subscription): ?int
    {
        foreach ($days as $bucket) {
            if ($remaining <= $bucket && ! $subscription->hasSentReminder($bucket)) {
                return $bucket;
            }
        }

        return null;
    }
}
