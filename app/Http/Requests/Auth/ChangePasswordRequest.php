<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\Validator;

class ChangePasswordRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            // `current_password` proves possession of the account, not just of
            // an unexpired token — the difference that matters when a phone is
            // left unlocked on a table.
            'current_password' => ['required', 'current_password:sanctum'],
            'password' => ['required', 'confirmed', 'different:current_password', Password::min(8)->letters()->numbers()->uncompromised()],
            'logout_other_devices' => ['nullable', 'boolean'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($this->input('password') === $this->input('current_password')) {
                $validator->errors()->add('password', 'Choose a password you have not used before.');
            }
        });
    }

    public function messages(): array
    {
        return ['current_password.current_password' => 'Your current password is incorrect.'];
    }
}
