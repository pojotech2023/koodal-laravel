<?php

namespace App\Http\Requests\Auth;

use App\Support\PhoneNumber;
use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
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

        if ($identifier !== '') {
            $this->merge(['identifier' => trim($identifier)]);
        }
    }

    public function rules(): array
    {
        return [
            // One field for both handles. Asking the user to pick "email or
            // mobile" before typing is friction with no upside.
            'identifier' => ['required', 'string', 'max:190'],
            'password' => ['required', 'string'],
            'device_name' => ['nullable', 'string', 'max:120'],
        ];
    }

    /** @return array{column: string, value: string} */
    public function credentials(): array
    {
        $identifier = trim($this->input('identifier'));

        if (str_contains($identifier, '@')) {
            return ['column' => 'email', 'value' => strtolower($identifier)];
        }

        return ['column' => 'mobile', 'value' => PhoneNumber::normalise($identifier) ?? $identifier];
    }

    public function deviceName(): string
    {
        return $this->input('device_name')
            ?: ($this->header('X-Client', 'web').' '.substr((string) $this->userAgent(), 0, 60));
    }
}
