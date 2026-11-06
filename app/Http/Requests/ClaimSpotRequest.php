<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ClaimSpotRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'x_pct' => 'required|numeric|between:0,100',
            'y_pct' => 'required|numeric|between:0,100',
        ];
    }
}
