<?php

namespace App\Http\Requests\Cms;

use App\Support\PhoneNumber;
use Illuminate\Foundation\Http\FormRequest;

/**
 * The public contact form. Unauthenticated, so it is a spam target — the route
 * carries `throttle:5,10` and the rules below do the rest.
 *
 * There is no captcha here and that is a decision, not an omission: a captcha
 * on a matrimonial support form turns away exactly the older, less
 * technical relatives who most often need to use it. The throttle plus a
 * minimum message length costs a spammer more than it costs them.
 */
class ContactEnquiryRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:2', 'max:120'],
            'email' => ['required', 'email', 'max:190'],
            'mobile' => ['nullable', 'string', 'max:16'],
            'subject' => ['nullable', 'string', 'max:255'],

            // A 20-character floor. Every real enquiry clears it and most
            // template spam does not, at zero cost to the person typing.
            'message' => ['required', 'string', 'min:20', 'max:5000'],
        ];
    }

    public function messages(): array
    {
        return [
            'message.min' => 'Tell us a little more so we can help — a sentence or two.',
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->filled('mobile')) {
            $this->merge(['mobile' => PhoneNumber::normalise($this->input('mobile')) ?? $this->input('mobile')]);
        }
    }
}
