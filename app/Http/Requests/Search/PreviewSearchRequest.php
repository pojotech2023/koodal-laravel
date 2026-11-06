<?php

namespace App\Http\Requests\Search;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * The signed-out `/browse` form. A deliberately small subset of SearchRequest.
 *
 * `looking_for` is the one field that has no counterpart there, and the one
 * that inverts SearchRequest's most important rule: that class refuses to
 * validate a `gender` key at all, "because accepting a validated gender would
 * be the first step towards honouring one". Here there is no authenticated
 * user to invert a gender from, so the visitor has to say — and saying it
 * costs nothing, because a guest has no own-gender profile that a
 * same-gender search could expose. SearchService::previewQuery() carries the
 * full reasoning.
 *
 * It is `sometimes`, not `required`: `/browse` with no query string at all is
 * the link in the nav and the crawler's entry point, and that request must
 * render the form rather than a validation error. Web\PreviewSearchController
 * treats its absence as "no search has been run yet".
 *
 * No `q`, no sort, no page, no `*_ids` arrays, no lifestyle fields. Each of
 * those either needs a viewer, opens an enumeration axis, or belongs to the
 * member filter panel — see RunPreviewSearch::PREVIEW_FILTERS, which strips
 * anything that gets past here anyway.
 */
class PreviewSearchRequest extends FormRequest
{
    public function rules(): array
    {
        $id = ['sometimes', 'nullable', 'integer', 'min:1'];
        $maxAge = config('matrimony.profile.max_age');

        return [
            'looking_for' => ['sometimes', 'nullable', Rule::in(['male', 'female'])],

            'age_min' => ['sometimes', 'nullable', 'integer', "between:18,{$maxAge}"],
            'age_max' => ['sometimes', 'nullable', 'integer', "between:18,{$maxAge}"],

            'religion_id' => $id,
            'mother_tongue_id' => $id,
            'marital_status_id' => $id,
            'country_id' => $id,
            'state_id' => $id,
            'city_id' => $id,

            'has_photo' => ['sometimes', 'nullable', 'boolean'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($this->filled('age_min') && $this->filled('age_max') && $this->input('age_min') > $this->input('age_max')) {
                $validator->errors()->add('age_max', 'Maximum age must be greater than the minimum.');
            }
        });
    }

    /** 'male', 'female', or null when the visitor has not chosen yet. */
    public function lookingFor(): ?string
    {
        return $this->validated()['looking_for'] ?? null;
    }

    /**
     * Validated input, emptied of blanks — and without `looking_for`, which is
     * not a filter over user_profiles but the gender the query is built on and
     * is passed to the Action separately.
     */
    public function criteria(): array
    {
        return array_filter(
            array_diff_key($this->validated(), ['looking_for' => null]),
            fn ($value) => $value !== null && $value !== '',
        );
    }
}
