<?php

namespace App\Http\Requests\Web;

use App\Http\Requests\Auth\RegisterRequest;

/**
 * Web registration request.
 *
 * Inherits all phone normalisation, Indian age validation, and credentials checks
 * from the base RegisterRequest without modifying the API contract. Adds support
 * for optional profile photo upload during web registration.
 */
class WebRegisterRequest extends RegisterRequest
{
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'photo' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:5120'],
        ]);
    }

    public function messages(): array
    {
        return array_merge(parent::messages(), [
            'photo.image' => 'The profile image must be a valid image file.',
            'photo.mimes' => 'The profile image must be a JPG, PNG, or WEBP file.',
            'photo.max' => 'The profile image must not be larger than 5 MB.',
        ]);
    }
}
