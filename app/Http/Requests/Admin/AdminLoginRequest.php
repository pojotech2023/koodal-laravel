<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Email only, unlike the member login.
 *
 * Members log in with a mobile number because that is the handle they know;
 * staff accounts are created by another human in the Staff module and always
 * carry an email. Accepting both here would mean a member's mobile number
 * reaching the staff login path, and the only thing that could produce is a
 * confusing error.
 */
class AdminLoginRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'email' => ['required', 'email', 'max:190'],
            'password' => ['required', 'string'],
            'device_name' => ['nullable', 'string', 'max:120'],
        ];
    }

    public function deviceName(): string
    {
        return $this->input('device_name')
            ?: 'admin '.substr((string) $this->userAgent(), 0, 60);
    }
}
