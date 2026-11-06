<?php

namespace App\Services\Payment;

use App\Models\MembershipPlan;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Razorpay\Api\Api;

/**
 * The only class that talks to Razorpay.
 *
 * Everything money-related that can be decided locally is decided locally: the
 * amount comes from the plan row, never from the request, and the signature is
 * recomputed with our secret rather than compared to anything the client sent
 * about itself.
 */
class RazorpayClient
{
    protected ?Api $api = null;

    protected function api(): Api
    {
        return $this->api ??= new Api(
            config('services.razorpay.key_id'),
            config('services.razorpay.key_secret'),
        );
    }

    /**
     * Razorpay works in the smallest currency unit. Converting once, here, is
     * what stops a ₹4,999 plan being charged as ₹49.99 somewhere downstream.
     */
    public function toPaise(float|string $amount): int
    {
        return (int) round(((float) $amount) * 100);
    }

    /**
     * @return array{id: string, amount: int, currency: string, receipt: string}
     */
    public function createOrder(User $user, MembershipPlan $plan, string $receipt): array
    {
        $order = $this->api()->order->create([
            'amount' => $this->toPaise($plan->price),
            'currency' => $plan->currency ?: config('services.razorpay.currency'),
            'receipt' => $receipt,
            'payment_capture' => 1,
            // Notes come back on the webhook, which is how an out-of-band
            // confirmation is matched to a user without trusting the payload.
            'notes' => [
                'user_id' => (string) $user->id,
                'plan_id' => (string) $plan->id,
                'profile_code' => (string) $user->profile_code,
            ],
        ]);

        Log::channel('payments')->info('Razorpay order created', [
            'order_id' => $order['id'],
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'amount' => $order['amount'],
        ]);

        return $order->toArray();
    }

    /**
     * hash_equals, not ===. String comparison short-circuits on the first
     * differing byte, which leaks the signature one character at a time to
     * anyone willing to measure response times.
     */
    public function verifySignature(string $orderId, string $paymentId, string $signature): bool
    {
        $expected = hash_hmac(
            'sha256',
            $orderId.'|'.$paymentId,
            (string) config('services.razorpay.key_secret'),
        );

        return hash_equals($expected, $signature);
    }

    /** Webhooks sign the raw body, so it must be hashed before any decoding. */
    public function verifyWebhookSignature(string $rawBody, string $signature): bool
    {
        $expected = hash_hmac(
            'sha256',
            $rawBody,
            (string) config('services.razorpay.webhook_secret'),
        );

        return hash_equals($expected, $signature);
    }

    /**
     * @return array{status: string, amount: int, method: ?string, email: ?string}
     */
    public function fetchPayment(string $paymentId): array
    {
        return $this->api()->payment->fetch($paymentId)->toArray();
    }

    public function refund(string $paymentId, ?int $amountInPaise = null, array $notes = []): array
    {
        $payload = array_filter([
            'amount' => $amountInPaise,
            'speed' => 'normal',
            'notes' => $notes ?: null,
        ], fn ($v) => $v !== null);

        $refund = $this->api()->payment->fetch($paymentId)->refund($payload);

        Log::channel('payments')->info('Razorpay refund issued', [
            'payment_id' => $paymentId,
            'refund_id' => $refund['id'],
            'amount' => $refund['amount'],
        ]);

        return $refund->toArray();
    }

    /** The public key only. The secret must never reach a client bundle. */
    public function publicKey(): string
    {
        return (string) config('services.razorpay.key_id');
    }
}
