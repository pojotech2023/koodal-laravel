<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * The body of `PATCH /admin/enquiries/{enquiry}/{action}`.
 *
 * `response` is required only for `respond` — the same route-parameter-driven
 * conditional `ReviewVerificationRequest` uses, for the same reason: one
 * endpoint covers three transitions and only one of them carries a payload.
 *
 * The 10-character floor is the mirror image of the 20-character floor the
 * public form imposes on the enquirer. Somebody who wrote three paragraphs
 * about a payment they cannot see is owed more than "done".
 */
class HandleEnquiryRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'response' => [
                Rule::requiredIf(fn () => $this->route('action') === 'respond'),
                'nullable', 'string', 'min:10', 'max:5000',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'response.required' => 'Record what you told them — this column is the only record of the answer.',
            'response.min' => 'Write the reply as it was sent, not a one-word note.',
        ];
    }
}
