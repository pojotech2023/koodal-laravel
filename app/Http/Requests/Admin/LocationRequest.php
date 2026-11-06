<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Countries, states and cities behind one `locations/{type}` route.
 *
 * Slugs are unique *within their parent* for states and cities, not globally:
 * there is a Hyderabad in Telangana and one in Sindh, and a global unique
 * would let whichever was imported first block the other forever.
 */
class LocationRequest extends FormRequest
{
    /** URL segment → table. */
    public const TABLES = [
        'countries' => 'countries',
        'states' => 'states',
        'cities' => 'cities',
    ];

    public function rules(): array
    {
        $type = $this->route('type');
        $id = $this->route('id');
        $required = $this->isMethod('POST') ? 'required' : 'sometimes';

        abort_unless(isset(static::TABLES[$type]), 404, 'Not found.');

        return match ($type) {
            'countries' => [
                'name' => [$required, 'string', 'max:120'],
                'slug' => [$required, 'string', 'max:140', 'alpha_dash', Rule::unique('countries', 'slug')->ignore($id)],
                'iso2' => [$required, 'string', 'size:2'],
                'iso3' => [$required, 'string', 'size:3'],
                // Kept as a string with its `+`: it is dialled, not summed, and
                // an integer loses the leading zero on the handful of codes
                // that have one.
                'phone_code' => [$required, 'string', 'max:8'],
                'sort_order' => ['sometimes', 'integer', 'min:0', 'max:65535'],
                'is_active' => ['sometimes', 'boolean'],
            ],

            'states' => [
                'country_id' => [$required, 'integer', 'exists:countries,id'],
                'name' => [$required, 'string', 'max:120'],
                'slug' => [
                    $required, 'string', 'max:140', 'alpha_dash',
                    Rule::unique('states', 'slug')
                        ->where(fn ($q) => $q->where('country_id', $this->input('country_id')))
                        ->ignore($id),
                ],
                'code' => ['nullable', 'string', 'max:10'],
                'is_active' => ['sometimes', 'boolean'],
            ],

            'cities' => [
                'state_id' => [$required, 'integer', 'exists:states,id'],
                'name' => [$required, 'string', 'max:120'],
                'slug' => [
                    $required, 'string', 'max:140', 'alpha_dash',
                    Rule::unique('cities', 'slug')
                        ->where(fn ($q) => $q->where('state_id', $this->input('state_id')))
                        ->ignore($id),
                ],
                'latitude' => ['nullable', 'numeric', 'between:-90,90'],
                'longitude' => ['nullable', 'numeric', 'between:-180,180'],
                'is_active' => ['sometimes', 'boolean'],
            ],
        };
    }
}
