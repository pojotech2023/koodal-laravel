<?php

namespace App\Http\Requests\Auth;

use App\Support\Age;
use App\Support\PhoneNumber;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\Validator;

class RegisterRequest extends FormRequest
{
    /**
     * Normalised before validation, so `unique:users,mobile` compares the same
     * string a later login will produce. Without this, +91 98765 43210 and
     * 09876543210 register as two accounts.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'mobile' => PhoneNumber::normalise($this->input('mobile')),
            'email' => $this->filled('email') ? strtolower(trim($this->input('email'))) : null,
        ]);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:2', 'max:120'],
            'mobile' => ['required', 'string', 'max:16', 'unique:users,mobile'],
            'email' => ['nullable', 'email:rfc', 'max:190', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::min(8)->letters()->mixedCase()->numbers()->symbols()],
            'gender' => ['required', Rule::in(['male', 'female'])],
            'date_of_birth' => ['required', 'date', 'before:today'],
            'profile_created_by' => ['required', Rule::in(['self', 'parent', 'sibling', 'relative', 'friend'])],
            'referral_code' => ['nullable', 'string', 'max:20'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if (! PhoneNumber::isValid($this->input('mobile'))) {
                $validator->errors()->add('mobile', 'Enter a valid 10-digit Indian mobile number.');
            }

            // The legal minimum marriage age differs by gender in India, so
            // this cannot be a single `before:-21 years` rule.
            if ($this->filled(['date_of_birth', 'gender'])
                && ! Age::isEligible($this->date('date_of_birth'), $this->input('gender'))) {
                $minimum = config("matrimony.profile.min_age.{$this->input('gender')}");

                $validator->errors()->add('date_of_birth', "You must be at least {$minimum} years old to register.");
            }
        });
    }

    public function messages(): array
    {
        return [
            'aadhaar_number.required' => 'Aadhaar card number is mandatory.',
            'aadhaar_number.size' => 'Aadhaar card number must be exactly 12 digits.',
            'aadhaar_number.unique' => 'An account is already registered with this Aadhaar number.',
            'aadhaar_verified.required' => 'Aadhaar OTP verification is mandatory.',
            'mobile.unique' => 'An account already exists with this mobile number.',
            'email.unique' => 'An account already exists with this email address.',
            'password.uncompromised' => 'This password has appeared in a data breach. Please choose another.',
        ];
    }
}
