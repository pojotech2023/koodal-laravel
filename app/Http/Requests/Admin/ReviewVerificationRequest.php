<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Remarks on a verification decision.
 *
 * Unlike a photo or profile rejection, these are not restricted to the
 * configured list. The list — "Document unreadable", "Name mismatch" — covers
 * the common cases and the panel offers it as a picker, but a reviewer looking
 * at a government ID needs to be able to say which name mismatched. The
 * trade-off runs the other way here: an unusably vague remark on an ID review
 * costs more than inconsistent tone, because the member cannot guess what to
 * re-upload.
 */
class ReviewVerificationRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'remarks' => [
                Rule::requiredIf(fn () => $this->route('action') !== 'approve'),
                'nullable', 'string', 'min:5', 'max:255',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'remarks.required' => 'Tell the member what was wrong with the document.',
        ];
    }
}
