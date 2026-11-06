<?php

namespace App\Actions\Subscription;

use App\Models\Subscription;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\Membership\FeatureGate;
use App\Services\Notification\NotificationDispatcher;
use Illuminate\Support\Facades\DB;

/**
 * Ending a membership from the admin side.
 *
 * This stops the entitlement **now**, not at the end of the paid period:
 * `Subscription::scopeActive()` requires `status = 'active'`, so the moment
 * this writes `cancelled` the member drops to the default plan. There is no
 * "cancel at period end" here, and adding one would be a column that lies —
 * with no recurring billing in the product, letting a subscription run to its
 * expiry is exactly what happens if nobody does anything.
 *
 * Which makes this the wrong tool for "they asked not to be charged again",
 * and the right one for the two cases it exists for: a membership bought by
 * mistake and refunded outside the app, and one that has to end because the
 * account is being suspended.
 *
 * Money is not touched. A cancellation that silently issued a refund would put
 * a financial movement behind a button labelled Cancel, and the refund path —
 * `RefundPayment` — cancels the subscription itself when the whole amount goes
 * back.
 */
class CancelSubscription
{
    public function __construct(
        protected AuditLogger $audit,
        protected FeatureGate $features,
        protected NotificationDispatcher $notifications,
    ) {}

    public function __invoke(Subscription $subscription, User $actor, string $reason): Subscription
    {
        abort_unless(
            in_array($subscription->status, ['active', 'pending'], true),
            409,
            'This membership is not active.',
        );

        // The free tier is how every member without a paid plan resolves their
        // features. Cancelling it leaves them with no subscription row at all,
        // which is the "user has no membership" state the default plan was
        // introduced to remove.
        abort_if(
            $subscription->plan?->is_default,
            422,
            'The default membership cannot be cancelled.',
        );

        $subscription = DB::transaction(function () use ($subscription, $actor, $reason) {
            $subscription->update([
                'status' => 'cancelled',
                'auto_renew' => false,
                'cancelled_at' => now(),
                'cancellation_reason' => $reason,
            ]);

            $this->audit->log(
                'subscription.cancelled',
                $subscription,
                newValues: ['status' => 'cancelled'],
                description: $reason,
                actor: $actor,
            );

            return $subscription->fresh();
        });

        if ($user = $subscription->user) {
            $this->features->flush($user);

            // Forced past the channel preference. Losing a paid capability
            // without being told is indistinguishable from the app breaking,
            // and the support ticket that follows is more expensive than the
            // notification.
            $this->notifications->send(
                user: $user,
                type: 'membership_expiring',
                title: 'Your membership has ended',
                body: 'Your membership has been cancelled. Contact support if this is unexpected.',
                force: true,
            );
        }

        return $subscription;
    }
}
