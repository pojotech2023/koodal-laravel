<?php

namespace App\Http\Requests\Admin;

use App\Actions\Report\ResolveReport;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * The end of a report.
 *
 * `action_taken` is validated against the Action's own constant rather than a
 * literal list, so the schema enum, the Action and this request cannot drift
 * into three different opinions about what a moderation outcome is.
 *
 * The note is required whenever a sanction is applied, and the same rule is
 * enforced again inside `ResolveReport` — this one produces the good 422 with
 * a field name the form can highlight, that one holds for every other caller.
 */
class ResolveReportRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'action_taken' => ['required', Rule::in(ResolveReport::ACTIONS)],
            'note' => [
                Rule::requiredIf(fn () => $this->input('action_taken') !== 'none'),
                'nullable', 'string', 'min:10', 'max:1000',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'note.required' => 'Write down why this action was taken. Someone will read it on appeal.',
            'note.min' => 'Write down why this action was taken. Someone will read it on appeal.',
        ];
    }
}
