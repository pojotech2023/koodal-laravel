<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

/**
 * `amount` is optional and omitting it refunds the whole payment — the common
 * case, and the one that must not require the operator to retype a figure they
 * could get wrong. A partial refund has to be stated explicitly, in rupees;
 * paise exist only at the Razorpay boundary and `RefundPayment` converts there.
 */
class RefundPaymentRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'amount' => ['nullable', 'numeric', 'min:0.01'],
            'reason' => ['required', 'string', 'min:5', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'reason.required' => 'A refund needs a reason on the record.',
        ];
    }
}
