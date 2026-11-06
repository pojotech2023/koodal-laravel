<?php

namespace App\Http\Requests\Payment;

use Illuminate\Foundation\Http\FormRequest;

/**
 * `plan_id` is the only price-relevant field a client is ever trusted with.
 * There is deliberately no amount, no currency and no coupon here — every one
 * of those is read or applied server-side.
 */
class CreateOrderRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'plan_id' => ['required', 'integer', 'exists:membership_plans,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'plan_id.exists' => 'This plan is no longer available.',
        ];
    }
}
