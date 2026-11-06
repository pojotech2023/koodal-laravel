<?php

namespace App\Http\Requests\Profile;

class UpdateEducationRequest extends ProfileSectionRequest
{
    public function section(): string
    {
        return 'education';
    }

    public function rules(): array
    {
        return [
            'education_id' => ['sometimes', 'nullable', 'integer', 'exists:educations,id'],
            'education_detail' => ['sometimes', 'nullable', 'string', 'max:150'],
            'college' => ['sometimes', 'nullable', 'string', 'max:150'],
            'university' => ['sometimes', 'nullable', 'string', 'max:150'],
            'specialization' => ['sometimes', 'nullable', 'string', 'max:150'],
        ];
    }
}
