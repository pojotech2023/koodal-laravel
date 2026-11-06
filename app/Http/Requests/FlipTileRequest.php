<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class FlipTileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tile' => ['required', 'integer', 'min:0', 'max:35'],
        ];
    }
}
