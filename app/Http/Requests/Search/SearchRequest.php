<?php

namespace App\Http\Requests\Search;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Search parameters. Everything optional, everything bounded.
 *
 * `gender` is deliberately absent from the rule set. The backend inverts it
 * from the authenticated user, and accepting a validated `gender` would be the
 * first step towards honouring one.
 *
 * There are no `exists:` rules on the id filters. An unknown id already matches
 * nothing, and paying a lookup per filter on the hottest endpoint in the product
 * would buy a nicer error message for a case no real client produces.
 */
class SearchRequest extends FormRequest
{
    /** Same cap as partner preferences — an uncapped IN clause is the risk. */
    protected const MAX_SELECTIONS = 50;

    public function rules(): array
    {
        $multi = ['sometimes', 'nullable', 'array', 'max:'.self::MAX_SELECTIONS];
        $id = ['sometimes', 'nullable', 'integer', 'min:1'];
        $maxAge = config('matrimony.profile.max_age');

        return [
            'q' => ['sometimes', 'nullable', 'string', 'max:100'],
            'sort' => ['sometimes', 'nullable', Rule::in(config('matrimony.search.sorts'))],
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'between:1,'.config('matrimony.search.max_per_page')],

            'age_min' => ['sometimes', 'nullable', 'integer', "between:18,{$maxAge}"],
            'age_max' => ['sometimes', 'nullable', 'integer', "between:18,{$maxAge}"],
            'height_min' => ['sometimes', 'nullable', 'integer', 'between:120,220'],
            'height_max' => ['sometimes', 'nullable', 'integer', 'between:120,220'],
            'income_min' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'income_max' => ['sometimes', 'nullable', 'integer', 'min:0'],

            'religion_id' => $id,
            'religion_ids' => $multi,
            'religion_ids.*' => ['integer'],
            'community_id' => $id,
            'community_ids' => $multi,
            'community_ids.*' => ['integer'],
            'sub_community_ids' => $multi,
            'sub_community_ids.*' => ['integer'],
            'mother_tongue_id' => $id,
            'mother_tongue_ids' => $multi,
            'mother_tongue_ids.*' => ['integer'],
            'marital_status_id' => $id,
            'marital_status_ids' => $multi,
            'marital_status_ids.*' => ['integer'],
            'education_id' => $id,
            'education_ids' => $multi,
            'education_ids.*' => ['integer'],
            'occupation_id' => $id,
            'occupation_ids' => $multi,
            'occupation_ids.*' => ['integer'],
            'country_id' => $id,
            'country_ids' => $multi,
            'country_ids.*' => ['integer'],
            'state_id' => $id,
            'state_ids' => $multi,
            'state_ids.*' => ['integer'],
            'city_id' => $id,
            'city_ids' => $multi,
            'city_ids.*' => ['integer'],

            'diet' => ['sometimes', 'nullable', 'string', 'max:30'],
            'smoking' => ['sometimes', 'nullable', 'string', 'max:20'],
            'drinking' => ['sometimes', 'nullable', 'string', 'max:20'],
            'manglik' => ['sometimes', 'nullable', 'string', 'max:20'],
            'physical_status' => ['sometimes', 'nullable', 'string', 'max:30'],
            'body_type' => ['sometimes', 'nullable', 'string', 'max:30'],
            'complexion' => ['sometimes', 'nullable', 'string', 'max:30'],
            'family_type' => ['sometimes', 'nullable', 'string', 'max:30'],
            'family_status' => ['sometimes', 'nullable', 'string', 'max:30'],
            'job_type' => ['sometimes', 'nullable', 'string', 'max:30'],

            'has_photo' => ['sometimes', 'nullable', 'boolean'],
            'is_verified' => ['sometimes', 'nullable', 'boolean'],
            'min_completion' => ['sometimes', 'nullable', 'integer', 'between:0,100'],
            'active_within_days' => ['sometimes', 'nullable', 'integer', 'between:1,365'],
            'exclude_actioned' => ['sometimes', 'nullable', 'boolean'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            foreach ([['age_min', 'age_max', 'age'], ['height_min', 'height_max', 'height'], ['income_min', 'income_max', 'income']] as [$min, $max, $label]) {
                if ($this->filled($min) && $this->filled($max) && $this->input($min) > $this->input($max)) {
                    $validator->errors()->add($max, "Maximum {$label} must be greater than the minimum.");
                }
            }
        });
    }

    /**
     * Validated input, emptied of blanks, plus the viewer id.
     *
     * SearchFilters needs the viewer for its NOT EXISTS on already-actioned
     * profiles. The key is underscore-prefixed because it is server-injected:
     * anything a client sends under that name is discarded by validation, and
     * RunSearch skips underscore keys when deciding what counts as a filter.
     */
    public function criteria(): array
    {
        $criteria = array_filter(
            $this->validated(),
            fn ($value) => $value !== null && $value !== '',
        );

        return array_merge($criteria, ['_viewer_id' => $this->user()->id]);
    }

    /**
     * The filter keys search understands — also the keys a saved search may
     * store. Derived from the rules rather than listed twice, so a filter added
     * above is immediately savable and cannot drift.
     *
     * @return array<int, string>
     */
    public static function filterKeys(): array
    {
        $keys = array_map(
            fn (string $key) => str_contains($key, '.') ? strstr($key, '.', true) : $key,
            array_keys((new self)->rules()),
        );

        return array_values(array_unique(array_diff($keys, ['page', 'per_page'])));
    }
}
