<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

/**
 * The body of `PATCH /admin/users/{id}/{action}`.
 *
 * `reason` is nullable here and required in `UpdateUserStatus` for the two
 * actions that need it. That split is deliberate: the rule is "a sanction
 * needs a written reason", which is a business rule about *which* action was
 * taken, and it has to hold on every path into the Action rather than only on
 * the one that happens to run this FormRequest.
 */
class UserActionRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'reason' => ['nullable', 'string', 'min:5', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'reason.min' => 'Write a reason someone can act on in three months, not one word.',
        ];
    }
}
