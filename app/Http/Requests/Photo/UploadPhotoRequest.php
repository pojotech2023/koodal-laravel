<?php

namespace App\Http\Requests\Photo;

use Illuminate\Foundation\Http\FormRequest;

class UploadPhotoRequest extends FormRequest
{
    public function rules(): array
    {
        $config = config('matrimony.media.photo');

        return [
            'photo' => [
                'required',
                'file',
                // `image` plus an explicit mimes list: `image` alone accepts
                // SVG, which is a script-execution vector dressed as a picture.
                'image',
                'mimes:'.implode(',', $config['mimes']),
                'max:'.$config['max_size_kb'],
                'dimensions:min_width=200,min_height=200',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'photo.dimensions' => 'The photo must be at least 200×200 pixels.',
            'photo.max' => 'The photo must be smaller than '.round(config('matrimony.media.photo.max_size_kb') / 1024).' MB.',
        ];
    }
}
