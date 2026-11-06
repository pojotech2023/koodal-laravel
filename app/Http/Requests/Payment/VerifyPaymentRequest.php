<?php

namespace App\Http\Requests\Payment;

use Illuminate\Foundation\Http\FormRequest;

/**
 * The three fields Razorpay Checkout hands back.
 *
 * Note what is NOT validated against the database: nothing. `exists` on the
 * order id would leak whether an order exists to anyone who can post here, and
 * the action looks it up anyway. Shape only — authenticity is the signature's
 * job, not the validator's.
 */
class VerifyPaymentRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'payment_id' => ['nullable', 'integer'],
            'plan_id' => ['nullable', 'integer'],
            'razorpay_order_id' => ['nullable', 'string', 'max:64'],
            'razorpay_payment_id' => ['required', 'string', 'max:64'],
            'razorpay_signature' => ['nullable', 'string', 'max:255'],
        ];
    }
}
