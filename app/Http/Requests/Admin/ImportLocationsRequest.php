<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * The city CSV upload. Cities are the only location table large enough that
 * anyone would import one — countries and states are seeded once and edited by
 * hand.
 *
 * `mimes:csv,txt` rather than a MIME check alone: browsers on Windows send
 * `application/vnd.ms-excel` for a .csv often enough that a strict MIME rule
 * rejects real files from real admins.
 */
class ImportLocationsRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'type' => ['sometimes', Rule::in(['cities'])],
            'file' => ['required', 'file', 'mimes:csv,txt', 'max:5120'],
        ];
    }

    public function messages(): array
    {
        return [
            'file.max' => 'Keep the file under 5 MB. A larger list should be split, or seeded.',
            'type.in' => 'Only city imports are supported. Countries and states are edited individually.',
        ];
    }
}
