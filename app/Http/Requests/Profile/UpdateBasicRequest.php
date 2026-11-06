<?php

namespace App\Http\Requests\Profile;

use Illuminate\Validation\Rule;

class UpdateBasicRequest extends ProfileSectionRequest
{
    public function section(): string
    {
        return 'basic';
    }

    public function rules(): array
    {
        return [
            'profile_created_by' => ['sometimes', Rule::in(['self', 'parent', 'sibling', 'relative', 'friend'])],
            'height_cm' => ['sometimes', 'nullable', 'integer', 'between:120,220'],
            'weight_kg' => ['sometimes', 'nullable', 'integer', 'between:30,200'],
            'marital_status_id' => ['sometimes', 'nullable', 'integer', 'exists:marital_statuses,id'],
            'body_type' => ['sometimes', 'nullable', 'string', 'max:30'],
            'complexion' => ['sometimes', 'nullable', 'string', 'max:30'],
            'physical_status' => ['sometimes', 'nullable', 'string', 'max:30'],
            'children_count' => ['sometimes', 'nullable', 'integer', 'between:0,20'],
            'children_living_status' => ['sometimes', 'nullable', Rule::in(['together', 'not_together', 'none'])],
        ];
    }
}
