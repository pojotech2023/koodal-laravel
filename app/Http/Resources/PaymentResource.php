<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A transaction on the user's own billing history.
 *
 * The invoice is exposed as a *flag*, never a URL. Invoices live on the private
 * disk and are reached through `GET /payments/{id}/invoice`, which mints a
 * short-lived signed URL at request time — a URL embedded in a list response
 * would either be long-lived (a leak) or expired by the time it was tapped.
 */
class PaymentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'amount' => (float) $this->amount,
            'currency' => $this->currency,
            'status' => $this->status,
            'method' => $this->method,

            'plan' => $this->whenLoaded('plan', fn () => [
                'name' => $this->plan->name,
                'slug' => $this->plan->slug,
                'duration_days' => $this->plan->duration_days,
            ]),

            'invoice_number' => $this->invoice_number,
            'invoice_available' => $this->invoice_path !== null,

            // Present only when the money came back, so a client can render the
            // refund line without a second lookup.
            'refunded_amount' => $this->when($this->refund_id !== null, fn () => (float) $this->refunded_amount),

            'paid_at' => $this->verified_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
