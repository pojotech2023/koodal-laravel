<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A transaction as finance and support see it.
 *
 * The gateway identifiers are here and absent from the member-facing
 * `PaymentResource`, because they are the join key against the Razorpay
 * dashboard and the settlement report — every "the money left my account and
 * nothing happened" ticket is resolved by pasting `razorpay_payment_id` into
 * the gateway's search.
 *
 * `razorpay_signature` is deliberately not exposed. It is a credential-shaped
 * value with no diagnostic use once verification has happened, and `AuditLogger`
 * already redacts anything named `signature` on the way in.
 *
 * The invoice remains a flag, exactly as on the member side: invoices live on
 * the private disk and the URL is minted per request.
 */
class AdminPaymentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'amount' => (float) $this->amount,
            'currency' => $this->currency,
            'status' => $this->status,
            'method' => $this->method,
            'failure_reason' => $this->failure_reason,

            'user' => $this->whenLoaded('user', fn () => $this->user ? [
                'id' => $this->user->id,
                'profile_code' => $this->user->profile_code,
                'name' => $this->user->name,
                'email' => $this->user->email,
                'mobile' => $this->user->mobile,
            ] : null),

            'plan' => $this->whenLoaded('plan', fn () => $this->plan ? [
                'id' => $this->plan->id,
                'name' => $this->plan->name,
                'duration_days' => $this->plan->duration_days,
            ] : null),

            'gateway' => [
                'order_id' => $this->razorpay_order_id,
                'payment_id' => $this->razorpay_payment_id,
            ],

            'refund' => $this->refund_id ? [
                'id' => $this->refund_id,
                'amount' => (float) $this->refunded_amount,
                'reason' => $this->refund_reason,
                // A payment still marked `paid` with a refund attached is a
                // partial one. Saying so explicitly stops the reader inferring
                // it from two fields that look contradictory.
                'is_partial' => (float) $this->refunded_amount < (float) $this->amount,
            ] : null,

            'invoice_number' => $this->invoice_number,
            'invoice_available' => $this->invoice_path !== null,

            'subscription_id' => $this->subscription_id,
            'paid_at' => $this->verified_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
