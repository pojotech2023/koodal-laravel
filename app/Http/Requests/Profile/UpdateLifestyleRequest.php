<?php

namespace App\Http\Requests\Profile;

class UpdateLifestyleRequest extends ProfileSectionRequest
{
    public function section(): string
    {
        return 'lifestyle';
    }

    /**
     * The API sends `hobbies`/`interests` as real JSON arrays; a plain HTML
     * form has no native multi-value control for an open-ended tag list, so
     * the website posts one comma-separated string instead. Normalising it
     * here — rather than in the web controller — keeps the array-of-strings
     * contract in one place and is a no-op for the JSON caller, which never
     * sends a string for these fields.
     */
    protected function prepareForValidation(): void
    {
        foreach (['hobbies', 'interests'] as $field) {
            if (is_string($this->input($field))) {
                $this->merge([
                    $field => array_values(array_filter(array_map('trim', explode(',', $this->input($field))))),
                ]);
            }
        }
    }

    public function rules(): array
    {
        return [
            'diet' => ['sometimes', 'nullable', 'string', 'max:30'],
            'smoking' => ['sometimes', 'nullable', 'string', 'max:20'],
            'drinking' => ['sometimes', 'nullable', 'string', 'max:20'],
            // Display-only multi-selects, capped so a client cannot post a
            // megabyte of "hobbies" into a JSON column.
            'hobbies' => ['sometimes', 'nullable', 'array', 'max:20'],
            'hobbies.*' => ['string', 'max:40'],
            'interests' => ['sometimes', 'nullable', 'array', 'max:20'],
            'interests.*' => ['string', 'max:40'],
        ];
    }
}
