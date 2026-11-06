<?php

namespace App\Http\Requests\Profile;

use Illuminate\Validation\Rule;

class UpdateProfessionalRequest extends ProfileSectionRequest
{
    public function section(): string
    {
        return 'professional';
    }

    public function rules(): array
    {
        return [
            'occupation_id' => ['sometimes', 'nullable', 'integer', 'exists:occupations,id'],
            'company' => ['sometimes', 'nullable', 'string', 'max:150'],
            'job_type' => ['sometimes', 'nullable', Rule::in(['govt', 'private', 'business', 'self_employed', 'defence', 'not_working'])],
            'income_range_id' => ['sometimes', 'nullable', 'integer', 'exists:income_ranges,id'],
            'work_country_id' => ['sometimes', 'nullable', 'integer', 'exists:countries,id'],
            'work_state_id' => ['sometimes', 'nullable', 'integer', 'exists:states,id'],
            'work_city_id' => ['sometimes', 'nullable', 'integer', 'exists:cities,id'],
        ];
    }
}
