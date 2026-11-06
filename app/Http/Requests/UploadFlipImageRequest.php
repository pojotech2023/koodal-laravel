<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UploadFlipImageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'images' => ['required', 'array', 'min:1'],
            'images.*' => ['required', 'image', 'mimes:jpeg,png,jpg,webp', 'max:2048'],
        ];
    }

    public function messages(): array
    {
        return [
            'images.required' => 'Please select at least one image to upload.',
            'images.*.image' => 'File must be an image.',
            'images.*.mimes' => 'Images must be JPG, PNG, or WEBP format.',
            'images.*.max' => 'Each image must not exceed 2MB in size.',
        ];
    }
}
