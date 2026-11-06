<?php

namespace App\Http\Requests\Profile;

class UpdateAboutRequest extends ProfileSectionRequest
{
    public function section(): string
    {
        return 'about';
    }

    public function rules(): array
    {
        return [
            'about_me' => ['sometimes', 'nullable', 'string', 'max:3000'],
            'expectations' => ['sometimes', 'nullable', 'string', 'max:3000'],
        ];
    }
}
