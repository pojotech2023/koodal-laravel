<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Goodwill days. Capped at a year: anything longer is a plan change, not a
 * compensation, and it should go through a purchase so the revenue and the
 * `features_snapshot` line up with what the member actually holds.
 */
class ExtendSubscriptionRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'days' => ['required', 'integer', 'min:1', 'max:365'],
            'reason' => ['nullable', 'string', 'max:255'],
        ];
    }
}
