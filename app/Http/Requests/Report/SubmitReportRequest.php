<?php

namespace App\Http\Requests\Report;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SubmitReportRequest extends FormRequest
{
    /** Same `profile_code` fallback as `SendInterestRequest` — see its docblock. */
    protected function prepareForValidation(): void
    {
        if (! $this->filled('reported_user_id') && $this->filled('profile_code')) {
            $this->merge([
                'reported_user_id' => User::where('profile_code', $this->string('profile_code'))->value('id'),
            ]);
        }
    }

    public function rules(): array
    {
        $media = config('matrimony.media.photo');

        return [
            'reported_user_id' => [
                'required', 'integer', 'exists:users,id',
                'different:'.$this->user()->id,
            ],

            // Validated against config, not a hardcoded list: the same array
            // drives GET /reports/categories, so the two can never disagree.
            'category' => ['required', Rule::in(array_keys(config('matrimony.reports.categories')))],

            // Required for `other`, because a report with no category and no
            // words is one a moderator cannot action.
            'description' => ['nullable', 'required_if:category,other', 'string', 'max:2000'],

            'evidence' => ['sometimes', 'array', 'max:5'],
            'evidence.*' => [
                'file',
                'mimes:'.implode(',', $media['mimes']),
                'max:'.$media['max_size_kb'],
            ],

            // Snapshotted onto the report. SubmitReport discards any id that is
            // not from a conversation between these two people.
            'message_ids' => ['sometimes', 'array', 'max:50'],
            'message_ids.*' => ['integer'],

            // The "Report and block" checkbox. Opt-in rather than automatic:
            // reporting a fake profile you never spoke to does not mean you
            // want it hidden from your search results forever.
            'block' => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'reported_user_id.different' => 'You cannot report your own profile.',
            'description.required_if' => 'Please tell us what happened.',
        ];
    }
}
