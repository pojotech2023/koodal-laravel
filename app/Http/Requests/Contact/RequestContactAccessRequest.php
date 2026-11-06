<?php

namespace App\Http\Requests\Contact;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Request contact access from a profile owner.
 *
 * Parameters:
 * - owner_id: (int, optional if profile_code is given) The ID of the profile owner.
 * - profile_code: (string, optional) The profile code of the owner.
 */
class RequestContactAccessRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'owner_id' => ['required_without:profile_code', 'nullable', 'integer', 'exists:users,id'],
            'profile_code' => ['required_without:owner_id', 'nullable', 'string', 'exists:users,profile_code'],
        ];
    }
}
