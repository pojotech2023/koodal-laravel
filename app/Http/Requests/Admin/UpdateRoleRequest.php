<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

/**
 * The role permission matrix — the whole set, every time.
 *
 * Deliberately not a diff. The panel renders a grid of checkboxes and submits
 * its state; sending "these were added, these removed" makes two clients
 * disagree about what was on screen when the last save happened, and the loser
 * silently re-grants a permission somebody had just revoked.
 */
class UpdateRoleRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'label' => ['sometimes', 'string', 'max:80'],
            'description' => ['nullable', 'string', 'max:1000'],
            'permissions' => ['required', 'array'],
            'permissions.*' => ['integer', 'exists:permissions,id'],
        ];
    }
}
