<?php

namespace App\Actions\Subscription;

use App\Models\Subscription;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\Membership\FeatureGate;
use App\Services\Notification\NotificationDispatcher;
use Illuminate\Support\Facades\DB;

/**
 * Goodwill days. Support grants these when an outage, a botched migration or a
 * mistaken suspension cost a member time they had paid for.
 *
 * Two things here are not obvious and both would produce a silent bug:
 *
 *   • The extension is measured from `expires_at` **or from now, whichever is
 *     later**. Adding seven days to a subscription that lapsed a month ago
 *     produces an expiry still in the past — a compensation that changes
 *     nothing, granted by a support agent who watched the success toast.
 *   • `reminders_sent` is cleared. `SendExpiryReminders` records which buckets
 *     it has fired so one deadline cannot nag three times; leaving the old
 *     buckets in place after an extension means the *new* expiry passes with
 *     no warning at all, because every bucket is already marked as sent.
 */
class ExtendSubscription
{
    public function __construct(
        protected AuditLogger $audit,
        protected FeatureGate $features,
        protected NotificationDispatcher $notifications,
    ) {}

    public function __invoke(Subscription $subscription, int $days, User $actor, ?string $reason = null): Subscription
    {
        abort_if($days < 1 || $days > 365, 422, 'Extend by between 1 and 365 days.');

        // The free tier has no expiry to extend, and writing one onto it would
        // give the default plan an end date — after which the member has no
        // membership at all, which is the state the default plan exists to
        // make impossible.
        abort_if($subscription->expires_at === null, 422, 'This membership does not expire.');

        $subscription = DB::transaction(function () use ($subscription, $days, $actor, $reason) {
            $from = $subscription->expires_at->isFuture() ? $subscription->expires_at : now();

            $subscription->update([
                'expires_at' => $from->copy()->addDays($days),
                'status' => 'active',
                'cancelled_at' => null,
                'cancellation_reason' => null,
                'reminders_sent' => [],
            ]);

            $this->audit->log(
                'subscription.extended',
                $subscription,
                newValues: ['days' => $days, 'expires_at' => $subscription->expires_at?->toIso8601String()],
                description: $reason,
                actor: $actor,
            );

            return $subscription->fresh();
        });

        // A reactivated subscription whose cached feature map still says
        // "free" is a member who paid and cannot use what they paid for, for
        // as long as the cache lives.
        if ($user = $subscription->user) {
            $this->features->flush($user);

            $this->notifications->send(
                user: $user,
                type: 'membership_purchased',
                title: 'Your membership has been extended',
                body: "We have added {$days} days to your membership. It now runs until "
                    .$subscription->expires_at->toFormattedDateString().'.',
            );
        }

        return $subscription;
    }
}
