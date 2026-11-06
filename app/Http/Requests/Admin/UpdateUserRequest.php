<?php

namespace App\Http\Requests\Admin;

use App\Support\PhoneNumber;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Support correcting a member's identity fields — a mistyped mobile number is
 * the single most common support ticket on a platform where the mobile number
 * is the login handle.
 *
 * `status` is deliberately absent: it moves through
 * `PATCH /admin/users/{id}/{action}`, which revokes tokens, updates search
 * visibility and notifies. A status column editable from a generic PUT is a
 * suspension that leaves the member logged in.
 *
 * `role_id` is absent for the same kind of reason — promoting a member to
 * staff belongs to the Staff module under `staff.manage`, where the audit
 * trail and the Super Admin gate are.
 */
class UpdateUserRequest extends FormRequest
{
    public function rules(): array
    {
        $userId = $this->route('user')?->id;

        return [
            'name' => ['sometimes', 'string', 'min:2', 'max:120'],
            'email' => ['sometimes', 'nullable', 'email', 'max:190', Rule::unique('users', 'email')->ignore($userId)],
            'mobile' => ['sometimes', 'string', 'max:16', Rule::unique('users', 'mobile')->ignore($userId)],

            // Both are corrections, not edits: a member cannot change either
            // themselves, because every search inverts on gender and every age
            // filter reads date of birth. Getting them wrong at registration is
            // exactly why a staff path exists.
            'gender' => ['sometimes', Rule::in(['male', 'female'])],
            'date_of_birth' => ['sometimes', 'date', 'before:today'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->filled('mobile')) {
            $this->merge([
                'mobile' => PhoneNumber::normalise($this->input('mobile')) ?? $this->input('mobile'),
            ]);
        }
    }
}
