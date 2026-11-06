<?php

namespace App\Http\Requests\Profile;

use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateFamilyRequest extends ProfileSectionRequest
{
    public function section(): string
    {
        return 'family';
    }

    public function rules(): array
    {
        return [
            'father_occupation' => ['sometimes', 'nullable', 'string', 'max:120'],
            'mother_occupation' => ['sometimes', 'nullable', 'string', 'max:120'],
            'brothers_count' => ['sometimes', 'nullable', 'integer', 'between:0,20'],
            'brothers_married' => ['sometimes', 'nullable', 'integer', 'between:0,20'],
            'sisters_count' => ['sometimes', 'nullable', 'integer', 'between:0,20'],
            'sisters_married' => ['sometimes', 'nullable', 'integer', 'between:0,20'],
            'family_type' => ['sometimes', 'nullable', Rule::in(['joint', 'nuclear'])],
            'family_status' => ['sometimes', 'nullable', Rule::in(['middle_class', 'upper_middle', 'rich', 'affluent'])],
            'family_values' => ['sometimes', 'nullable', Rule::in(['traditional', 'moderate', 'liberal'])],
            'family_location' => ['sometimes', 'nullable', 'string', 'max:120'],
            'family_description' => ['sometimes', 'nullable', 'string', 'max:2000'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            foreach (['brothers', 'sisters'] as $sibling) {
                $total = $this->input("{$sibling}_count");
                $married = $this->input("{$sibling}_married");

                if ($total !== null && $married !== null && $married > $total) {
                    $validator->errors()->add("{$sibling}_married", "Married {$sibling} cannot exceed the total.");
                }
            }
        });
    }
}
