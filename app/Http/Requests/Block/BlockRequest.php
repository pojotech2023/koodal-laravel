<?php

namespace App\Http\Requests\Block;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

class BlockRequest extends FormRequest
{
    /**
     * Same `profile_code` fallback as `SendInterestRequest` — see its docblock
     * for the reasoning. It matters more here than anywhere: block is reached
     * from a profile page or a chat header, and neither has ever been handed a
     * numeric id, because `ProfileResource` deliberately does not expose one.
     */
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
                'different:'.$this->user()->id,
            ],
            // The blocker's own note on why. Never shown to the person blocked
            // — a block they can read the reason for is a block they can argue
            // with.
            'reason' => ['sometimes', 'nullable', 'string', 'max:200'],
        ];
    }

    public function messages(): array
    {
        return [
            'user_id.different' => 'You cannot block your own profile.',
            'user_id.exists' => 'This profile is no longer available.',
        ];
    }
}
