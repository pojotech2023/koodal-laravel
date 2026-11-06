<?php

namespace App\Http\Requests\Auth;

use App\Support\PhoneNumber;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class ResetPasswordRequest extends FormRequest
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

        $password = (string) (
            $this->input('password')
            ?? $this->input('new_password')
        );

        $passwordConfirmation = (string) (
            $this->input('password_confirmation')
            ?? $this->input('confirm_password')
            ?? $this->input('password_confirm')
            ?? $this->input('new_password_confirmation')
            ?? $password
        );

        $this->merge([
            'identifier' => str_contains($identifier, '@')
                ? strtolower(trim($identifier))
                : (PhoneNumber::normalise($identifier) ?? $identifier),
            'code' => trim($code),
            'password' => $password,
            'password_confirmation' => $passwordConfirmation,
        ]);
    }

    public function rules(): array
    {
        return [
            'identifier' => ['required', 'string', 'max:190'],
            'code' => ['required', 'string', 'digits:6'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
        ];
    }
}
