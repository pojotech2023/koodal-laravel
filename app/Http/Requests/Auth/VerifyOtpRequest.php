<?php

namespace App\Http\Requests\Auth;

use App\Support\PhoneNumber;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class VerifyOtpRequest extends FormRequest
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
        );

        $code = (string) (
            $this->input('code')
            ?? $this->input('otp')
            ?? $this->input('verification_code')
        );

        $this->merge([
            'identifier' => str_contains($identifier, '@')
                ? strtolower(trim($identifier))
                : (PhoneNumber::normalise($identifier) ?? $identifier),
            'code' => trim($code),
        ]);
    }

    public function rules(): array
    {
        return [
            'identifier' => ['required', 'string', 'max:190'],
            'code' => ['required', 'string', 'digits:6'],
            'purpose' => ['required', Rule::in(['registration', 'login', 'password_reset', 'mobile_change'])],
            'device_name' => ['nullable', 'string', 'max:120'],
        ];
    }
}
