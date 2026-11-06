<?php

namespace App\Http\Requests\Auth;

use App\Support\PhoneNumber;
use Illuminate\Foundation\Http\FormRequest;

class ForgotPasswordRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $identifier = (string) (
            $this->input('identifier')
            ?? $this->input('mobile')
            ?? $this->input('phone')
            ?? $this->input('phone_number')
            ?? $this->input('username')
            ?? $this->input('email')
            ?? $this->input('password')
        );

        $this->merge([
            'identifier' => str_contains($identifier, '@')
                ? strtolower(trim($identifier))
                : (PhoneNumber::normalise($identifier) ?? $identifier),
        ]);
    }

    public function rules(): array
    {
        // Deliberately no `exists` rule. Validating that the account exists
        // turns this endpoint into an account-enumeration oracle; the
        // controller always responds as though the code was sent.
        return [
            'identifier' => ['required', 'string', 'max:190'],
        ];
    }
}
