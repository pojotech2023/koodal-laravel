<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * The keyboard-driven photo queue's batch submit.
 *
 * Capped at 100 ids per call. Each one costs a transaction, an audit row and a
 * notification — that is the point of the endpoint, not an inefficiency to
 * optimise away — so an uncapped batch is a request that runs for minutes
 * behind an HTTP timeout the moderator will retry.
 */
class BulkPhotoModerationRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'action' => ['required', Rule::in(['approve', 'reject'])],
            'ids' => ['required', 'array', 'min:1', 'max:100'],
            'ids.*' => ['integer'],

            // Required on reject, and validated against the same configured
            // list as a single rejection — a batch is not a way around the
            // picker.
            'reason' => [
                Rule::requiredIf(fn () => $this->input('action') === 'reject'),
                'nullable', 'string',
                Rule::in(config('matrimony.reports.rejection_reasons.photo', [])),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'ids.max' => 'Moderate at most 100 photos at a time.',
            'reason.in' => 'Choose a rejection reason from the list.',
        ];
    }
}
