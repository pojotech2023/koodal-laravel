<?php

namespace App\Http\Requests\Chat;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

class StartConversationRequest extends FormRequest
{
    /** Same `profile_code` fallback as `SendInterestRequest`/`ShortlistRequest` — see their docblocks. */
    protected function prepareForValidation(): void
    {
        if (! $this->filled('user_id') && $this->filled('profile_code')) {
            $this->merge([
                'user_id' => User::where('profile_code', $this->string('profile_code'))->value('id'),
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'user_id' => [
                'required', 'integer', 'exists:users,id',
                // Caught here so the action's exception paths stay reserved for
                // the interesting failures — consent, blocks and paywalls.
                'different:'.$this->user()->id,
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'user_id.different' => 'You cannot start a conversation with yourself.',
            'user_id.exists' => 'This profile is no longer available.',
        ];
    }
}
