<?php

namespace App\Http\Requests\Profile;

use App\Support\Astrology;
use Illuminate\Validation\Rule;

class UpdateReligiousRequest extends ProfileSectionRequest
{
    public function section(): string
    {
        return 'religious';
    }

    /**
     * A resubmitted step can carry `birth_time` back from the DB's `H:i:s`
     * `time` column (see UserProfile::birthTime()) rather than the browser's
     * own `H:i` — trim it here so the `date_format:H:i` rule below never
     * rejects a value this same form put there.
     */
    protected function prepareForValidation(): void
    {
        if ($this->filled('birth_time')) {
            $this->merge(['birth_time' => substr($this->input('birth_time'), 0, 5)]);
        }

        // The app and the website spell raasi / star differently ("Rishabha",
        // "Vrishabha (Taurus)"); store the website's name whichever sent it.
        // A value that is neither is kept as typed.
        foreach (['raasi' => Astrology::raasi(...), 'star' => Astrology::star(...)] as $field => $canonical) {
            if (is_string($this->input($field)) && ($name = $canonical($this->input($field)))) {
                $this->merge([$field => $name]);
            }
        }
    }

    public function rules(): array
    {
        return [
            'religion_id' => ['sometimes', 'nullable', 'integer', 'exists:religions,id'],
            // Scoped to the chosen religion so a client cannot pair a Hindu
            // religion with a Christian denomination.
            'community_id' => ['sometimes', 'nullable', 'integer', Rule::exists('communities', 'id')
                ->where(fn ($q) => $this->filled('religion_id') ? $q->where('religion_id', $this->input('religion_id')) : $q)],
            'sub_community_id' => ['sometimes', 'nullable', 'integer', Rule::exists('sub_communities', 'id')
                ->where(fn ($q) => $this->filled('community_id') ? $q->where('community_id', $this->input('community_id')) : $q)],
            'mother_tongue_id' => ['sometimes', 'nullable', 'integer', 'exists:mother_tongues,id'],
            'caste' => ['sometimes', 'nullable', 'string', 'max:80'],
            'gothra' => ['sometimes', 'nullable', 'string', 'max:80'],
            'star' => ['sometimes', 'nullable', 'string', 'max:60'],
            'raasi' => ['sometimes', 'nullable', 'string', 'max:60'],
            'manglik' => ['sometimes', 'nullable', Rule::in(['yes', 'no', 'dont_know'])],
            'birth_time' => ['sometimes', 'nullable', 'date_format:H:i'],
            'birth_place' => ['sometimes', 'nullable', 'string', 'max:120'],
        ];
    }

    public function messages(): array
    {
        return [
            'community_id.exists' => 'That community does not belong to the selected religion.',
            'sub_community_id.exists' => 'That sub-community does not belong to the selected community.',
        ];
    }
}
