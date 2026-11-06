<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * A rejection reason, picked from the configured list rather than typed.
 *
 * The reason is sent verbatim to the member in the rejection notification, so
 * free text would put four moderators' wording — and four moderators' tone —
 * in front of members for the same complaint. The list lives in
 * `config('matrimony.reports.rejection_reasons')` so the picker in the panel
 * and the validation here cannot drift apart: both read the same array.
 *
 * Subclasses name their subject and nothing else.
 */
abstract class RejectionRequest extends FormRequest
{
    /** Key under `matrimony.reports.rejection_reasons`. */
    protected const SUBJECT = '';

    public function rules(): array
    {
        return [
            'reason' => ['required', 'string', Rule::in(config('matrimony.reports.rejection_reasons.'.static::SUBJECT, []))],
        ];
    }

    public function messages(): array
    {
        return [
            'reason.in' => 'Choose a rejection reason from the list.',
        ];
    }

    public function reason(): string
    {
        return $this->string('reason')->toString();
    }
}
