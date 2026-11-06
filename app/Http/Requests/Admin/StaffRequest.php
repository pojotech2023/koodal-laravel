<?php

namespace App\Http\Requests\Admin;

use App\Support\PhoneNumber;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

/**
 * Creating and editing staff accounts.
 *
 * The password rule is stricter than the member one on purpose: a member
 * account compromise costs that member, a staff account compromise costs the
 * member base. `uncompromised()` checks the k-anonymity range API at
 * haveibeenpwned — a network call on a form nobody submits more than a few
 * times a month, in exchange for refusing the passwords that are actually
 * used in credential-stuffing runs.
 *
 * `role_id` is constrained to staff roles by `StaffController`, not here: it is
 * a rule about who may hold which role, and it belongs where the Super Admin
 * guard is.
 */
class StaffRequest extends FormRequest
{
    public function rules(): array
    {
        $staffId = $this->route('staff')?->id ?? $this->route('staff');
        $creating = $this->isMethod('POST');

        return [
            'name' => [$creating ? 'required' : 'sometimes', 'string', 'min:2', 'max:120'],
            'email' => [
                $creating ? 'required' : 'sometimes', 'email', 'max:190',
                Rule::unique('users', 'email')->ignore($staffId),
            ],
            'mobile' => [
                $creating ? 'required' : 'sometimes', 'string', 'max:16',
                Rule::unique('users', 'mobile')->ignore($staffId),
            ],
            'password' => [
                $creating ? 'required' : 'nullable',
                Password::min(12)->mixedCase()->numbers()->symbols()->uncompromised(),
            ],
            'role_id' => [$creating ? 'required' : 'sometimes', 'integer', 'exists:roles,id'],

            // Present on the users table and not null, so a staff row has to
            // carry them. They mean nothing for a staff account and are not
            // asked for in the panel — hence the defaults in the controller.
            'gender' => ['sometimes', Rule::in(['male', 'female'])],
            'date_of_birth' => ['sometimes', 'date', 'before:today'],

            'status' => ['sometimes', Rule::in(['active', 'suspended'])],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->filled('mobile')) {
            $this->merge(['mobile' => PhoneNumber::normalise($this->input('mobile')) ?? $this->input('mobile')]);
        }
    }
}
