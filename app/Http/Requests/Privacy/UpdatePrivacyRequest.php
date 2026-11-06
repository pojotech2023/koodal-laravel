<?php

namespace App\Http\Requests\Privacy;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Allowed values come from config, not a literal list. The client renders its
 * selects from the same config, so an option can never exist in the UI and be
 * rejected by validation.
 */
class UpdatePrivacyRequest extends FormRequest
{
    public function rules(): array
    {
        $options = config('matrimony.privacy.options');

        return [
            'profile_visibility' => ['sometimes', Rule::in($options['profile_visibility'])],
            'photo_visibility' => ['sometimes', Rule::in($options['photo_visibility'])],
            'contact_visibility' => ['sometimes', Rule::in($options['contact_visibility'])],
            'who_can_message' => ['sometimes', Rule::in($options['who_can_message'])],
            'show_online_status' => ['sometimes', 'boolean'],
            'appear_in_search' => ['sometimes', 'boolean'],
            'show_profile_views' => ['sometimes', 'boolean'],
        ];
    }
}
