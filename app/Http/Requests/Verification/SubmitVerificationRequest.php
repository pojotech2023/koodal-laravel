<?php

namespace App\Http\Requests\Verification;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SubmitVerificationRequest extends FormRequest
{
    public function rules(): array
    {
        $document = config('matrimony.media.document');

        return [
            'type' => ['required', Rule::in(array_keys(config('matrimony.verification.types')))],

            'document' => [
                'required', 'file',
                'mimes:'.implode(',', $document['mimes']),
                'max:'.$document['max_size_kb'],
            ],

            // Optional, and the endpoint works without it — it exists only so
            // the same ID cannot be claimed by two accounts. It is HMAC'd on
            // arrival and never stored, logged or returned in readable form.
            'document_number' => ['nullable', 'string', 'max:40'],
        ];
    }

    public function messages(): array
    {
        return [
            'document.mimes' => 'Upload a photo or a PDF of the document.',
            'document.max' => 'The document is too large. Keep it under '
                .round(config('matrimony.media.document.max_size_kb') / 1024).' MB.',
        ];
    }
}
