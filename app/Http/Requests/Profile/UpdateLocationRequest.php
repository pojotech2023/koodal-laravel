<?php

namespace App\Http\Requests\Profile;

use Illuminate\Validation\Rule;

class UpdateLocationRequest extends ProfileSectionRequest
{
    public function section(): string
    {
        return 'location';
    }

    public function rules(): array
    {
        return [
            'country_id' => ['sometimes', 'nullable', 'integer', 'exists:countries,id'],
            // The hierarchy is enforced here, not just in the UI cascade: a
            // city that does not belong to the stated state makes every
            // location filter lie.
            'state_id' => ['sometimes', 'nullable', 'integer', Rule::exists('states', 'id')
                ->where(fn ($q) => $this->filled('country_id') ? $q->where('country_id', $this->input('country_id')) : $q)],
            'city_id' => ['sometimes', 'nullable', 'integer', Rule::exists('cities', 'id')
                ->where(fn ($q) => $this->filled('state_id') ? $q->where('state_id', $this->input('state_id')) : $q)],
            'residency_status' => ['sometimes', 'nullable', 'string', 'max:40'],
            'pincode' => ['sometimes', 'nullable', 'string', 'max:10'],
        ];
    }

    public function messages(): array
    {
        return [
            'state_id.exists' => 'That state does not belong to the selected country.',
            'city_id.exists' => 'That city does not belong to the selected state.',
        ];
    }
}
