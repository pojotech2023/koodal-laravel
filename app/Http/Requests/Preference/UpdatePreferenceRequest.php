<?php

namespace App\Http\Requests\Preference;

use App\Support\Astrology;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Multi-selects are capped. An uncapped `religion_ids` is a client sending ten
 * thousand integers into a JSON column and then into an IN clause on the
 * hottest query in the product.
 */
class UpdatePreferenceRequest extends FormRequest
{
    protected const MAX_SELECTIONS = 50;

    /** Raasis and stars are stored under the website's names, as on a profile. */
    protected function prepareForValidation(): void
    {
        foreach (['raasis' => Astrology::raasi(...), 'stars' => Astrology::star(...)] as $field => $canonical) {
            if (is_array($this->input($field))) {
                $this->merge([$field => array_values(array_unique(array_map(
                    fn ($v) => is_string($v) ? ($canonical($v) ?? $v) : $v,
                    $this->input($field),
                ), SORT_REGULAR))]);
            }
        }
    }

    public function rules(): array
    {
        $multi = ['sometimes', 'nullable', 'array', 'max:'.static::MAX_SELECTIONS];

        return [
            'age_min' => ['sometimes', 'nullable', 'integer', 'between:18,75'],
            'age_max' => ['sometimes', 'nullable', 'integer', 'between:18,75'],
            'height_min_cm' => ['sometimes', 'nullable', 'integer', 'between:120,220'],
            'height_max_cm' => ['sometimes', 'nullable', 'integer', 'between:120,220'],
            'weight_min_kg' => ['sometimes', 'nullable', 'integer', 'between:30,200'],
            'weight_max_kg' => ['sometimes', 'nullable', 'integer', 'between:30,200'],

            'marital_status_ids' => $multi,
            'marital_status_ids.*' => ['integer', 'exists:marital_statuses,id'],
            'religion_ids' => $multi,
            'religion_ids.*' => ['integer', 'exists:religions,id'],
            'community_ids' => $multi,
            'community_ids.*' => ['integer', 'exists:communities,id'],
            'mother_tongue_ids' => $multi,
            'mother_tongue_ids.*' => ['integer', 'exists:mother_tongues,id'],
            'education_ids' => $multi,
            'education_ids.*' => ['integer', 'exists:educations,id'],
            'occupation_ids' => $multi,
            'occupation_ids.*' => ['integer', 'exists:occupations,id'],
            'income_range_ids' => $multi,
            'income_range_ids.*' => ['integer', 'exists:income_ranges,id'],
            'country_ids' => $multi,
            'country_ids.*' => ['integer', 'exists:countries,id'],
            'state_ids' => $multi,
            'state_ids.*' => ['integer', 'exists:states,id'],
            'city_ids' => $multi,
            'city_ids.*' => ['integer', 'exists:cities,id'],

            'complexions' => $multi,
            'complexions.*' => ['string', 'max:60'],
            'raasis' => $multi,
            'raasis.*' => ['string', 'max:80'],
            'stars' => $multi,
            'stars.*' => ['string', 'max:80'],
            'castes' => ['sometimes', 'nullable', 'array', 'max:200'],
            'castes.*' => ['string', 'max:100'],

            'income_min' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'income_max' => ['sometimes', 'nullable', 'integer', 'min:0'],

            'diet' => ['sometimes', 'nullable', 'string', 'max:30'],
            'smoking' => ['sometimes', 'nullable', 'string', 'max:20'],
            'drinking' => ['sometimes', 'nullable', 'string', 'max:20'],
            'manglik_preference' => ['sometimes', 'nullable', Rule::in(['yes', 'no', 'doesnt_matter'])],
            'physical_status_preference' => ['sometimes', 'nullable', 'string', 'max:30'],
            'about_partner' => ['sometimes', 'nullable', 'string', 'max:2000'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            foreach ([
                ['age_min', 'age_max', 'age'],
                ['height_min_cm', 'height_max_cm', 'height'],
                ['weight_min_kg', 'weight_max_kg', 'weight'],
                ['income_min', 'income_max', 'income'],
            ] as [$min, $max, $label]) {
                if ($this->filled($min) && $this->filled($max) && (int) $this->input($min) > (int) $this->input($max)) {
                    $validator->errors()->add($max, "Maximum {$label} must be greater than or equal to the minimum.");
                }
            }
        });
    }
}
