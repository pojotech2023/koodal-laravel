<?php

namespace App\Http\Requests\Interest;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

class SendInterestRequest extends FormRequest
{
    /**
     * Every other "someone else's profile" endpoint is keyed on `profile_code`
     * (`GET /profiles/{profileCode}`) precisely because `ProfileResource` never
     * exposes a target's numeric id — so a client rendering a `ProfileCard` has
     * no `receiver_id` to send. Accepting `profile_code` here and resolving it
     * to `receiver_id` before validation keeps the rule below, the `different`
     * check and the controller completely unchanged; a missing/unknown code
     * simply leaves `receiver_id` null and fails the existing `exists` rule
     * with the existing message.
     */
    protected function prepareForValidation(): void
    {
        if (! $this->filled('receiver_id') && $this->filled('profile_code')) {
            $this->merge([
                'receiver_id' => User::where('profile_code', $this->string('profile_code'))->value('id'),
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'receiver_id' => [
                'required', 'integer', 'exists:users,id',
                // Blocks the obvious case here so the action's exception path
                // is reserved for genuinely exceptional input.
                'different:'.$this->user()->id,
            ],
            'message' => ['nullable', 'string', 'max:'.config('matrimony.interests.message_max_length')],
        ];
    }

    public function messages(): array
    {
        return [
            'receiver_id.different' => 'You cannot send an interest to your own profile.',
            'receiver_id.exists' => 'This profile is no longer available.',
        ];
    }
}
