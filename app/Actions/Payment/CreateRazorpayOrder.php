<?php

namespace App\Actions\Payment;

use App\Models\MembershipPlan;
use App\Models\Payment;
use App\Models\User;
use App\Services\Payment\RazorpayClient;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Opens a Razorpay order and writes the `created` payment row it will later be
 * matched against.
 *
 * The price is read from `membership_plans` and never from the request. The
 * client supplies a plan id; that is the only price-relevant input it is
 * trusted with, and it is the whole reason a tampered checkout cannot buy Gold
 * for ₹1.
 *
 * An abandoned checkout is reused rather than duplicated. A user who opens the
 * sheet, backs out, and taps Subscribe again gets the same order — matched on
 * plan AND amount, so a repricing in between correctly forces a new one.
 * Without this, a hesitant user leaves a trail of orphan orders that the
 * reconciliation report then has to explain.
 */
class CreateRazorpayOrder
{
    /** Razorpay orders stay payable for 24h; past that we open a fresh one. */
    protected const REUSE_WINDOW_HOURS = 24;

    public function __construct(protected RazorpayClient $razorpay) {}

    public function __invoke(User $user, MembershipPlan $plan): Payment
    {
        abort_unless($plan->is_active, 422, 'This plan is no longer available.');

        // A free plan has nothing to charge for. Letting one through would
        // create a zero-amount Razorpay order, which the API rejects with a
        // message no user could act on.
        abort_if($plan->isFree(), 422, 'This plan does not require a payment.');

        $this->assertNotDowngrade($user, $plan);

        // Without both keys Razorpay refuses every order, and a payment could
        // never be verified anyway. The log names the cause; members only see
        // the generic 503 sentence, never that payments are misconfigured.
        if (blank(config('services.razorpay.key_id')) || blank(config('services.razorpay.key_secret'))) {
            Log::channel('payments')->critical('Razorpay keys are not configured (RAZORPAY_KEY_ID / RAZORPAY_KEY_SECRET)', [
                'user_id' => $user->id,
                'plan_id' => $plan->id,
            ]);

            abort(503);
        }

        if ($existing = $this->reusableOrder($user, $plan)) {
            return $existing;
        }

        try {
            $order = $this->razorpay->createOrder($user, $plan, $this->receipt($user));
        } catch (\Throwable $e) {
            Log::channel('payments')->error('Razorpay order creation failed', [
                'user_id' => $user->id,
                'plan_id' => $plan->id,
                'error' => $e->getMessage(),
            ]);

            // 503, not 500: nothing was written, the user has lost nothing, and
            // tapping Subscribe again is the correct next action. A 500 tells
            // them the app is broken.
            abort(503, 'Payments are temporarily unavailable. Please try again in a moment.');
        }

        return DB::transaction(fn () => Payment::create([
            'user_id' => $user->id,
            'membership_plan_id' => $plan->id,
            'razorpay_order_id' => $order['id'],
            // Stored in rupees with a currency column; paise exist only at the
            // Razorpay boundary.
            'amount' => $plan->price,
            'currency' => $plan->currency ?: config('services.razorpay.currency'),
            'status' => 'created',
            'notes' => ['plan' => $plan->name, 'receipt' => $order['receipt'] ?? null],
        ]));
    }

    /**
     * Mid-term downgrades are refused rather than silently accepted, per
     * config. Taking money for a cheaper plan that then *shortens* what someone
     * already has is a refund request with extra steps.
     */
    protected function assertNotDowngrade(User $user, MembershipPlan $plan): void
    {
        if (config('matrimony.membership.allow_mid_term_downgrade')) {
            return;
        }

        $current = $user->activeSubscription?->plan;

        if ($current && (float) $plan->price < (float) $current->price) {
            abort(
                422,
                "Your {$current->name} membership is still active. You can change to a lower plan when it expires.",
            );
        }
    }

    protected function reusableOrder(User $user, MembershipPlan $plan): ?Payment
    {
        return Payment::query()
            ->where('user_id', $user->id)
            ->where('membership_plan_id', $plan->id)
            ->where('status', 'created')
            // Amount, not just plan: if the plan was repriced since, the open
            // order is for the wrong money and must not be handed back.
            ->where('amount', $plan->price)
            ->where('created_at', '>=', now()->subHours(self::REUSE_WINDOW_HOURS))
            ->latest()
            ->first();
    }

    /** Razorpay caps the receipt at 40 characters. */
    protected function receipt(User $user): string
    {
        return substr("rcpt_{$user->id}_".now()->timestamp, 0, 40);
    }
}
