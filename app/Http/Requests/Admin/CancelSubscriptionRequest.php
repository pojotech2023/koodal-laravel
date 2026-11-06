<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

/**
 * A cancellation ends a paid entitlement immediately, so the reason is
 * mandatory — this is the row support reads when the member calls to ask what
 * happened, and "cancelled by admin" with no reason has no answer in it.
 */
class CancelSubscriptionRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'reason' => ['required', 'string', 'min:5', 'max:255'],
        ];
    }
}
