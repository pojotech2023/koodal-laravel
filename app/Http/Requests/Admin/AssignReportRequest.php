<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Claiming a report, for yourself or for someone else.
 *
 * `assignee_id` is optional and defaults to the caller — the overwhelmingly
 * common case is a moderator picking up the next item in their own queue, and
 * making them name themselves is friction on the highest-frequency action in
 * the module. Whether the named person can actually resolve reports is checked
 * in `AssignReport`, not here: a permission check belongs where the rule is,
 * not in a validation rule that a second caller would bypass.
 */
class AssignReportRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'assignee_id' => ['nullable', 'integer', 'exists:users,id'],
        ];
    }
}
