<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class JoinFlipGameRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nickname' => ['nullable', 'string', 'max:30'],
        ];
    }
}
