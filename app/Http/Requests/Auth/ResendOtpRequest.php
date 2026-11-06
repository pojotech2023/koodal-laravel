<?php

namespace App\Http\Requests\Auth;

use App\Support\PhoneNumber;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ResendOtpRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $identifier = (string) $this->input('identifier');

        $this->merge([
            'identifier' => str_contains($identifier, '@')
                ? strtolower(trim($identifier))
                : (PhoneNumber::normalise($identifier) ?? $identifier),
        ]);
    }

    public function rules(): array
    {
        return [
            'identifier' => ['required', 'string', 'max:190'],
            'purpose' => ['required', Rule::in(['registration', 'login', 'password_reset', 'mobile_change'])],
        ];
    }
}
