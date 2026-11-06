<?php

namespace App\Http\Requests\Shortlist;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

class ShortlistRequest extends FormRequest
{
    /** Same `profile_code` fallback as `SendInterestRequest` — see its docblock. */
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
            'user_id' => ['required', 'integer', 'exists:users,id'],
            // A private annotation — "spoke to her father", "second choice".
            // Never shown to the person shortlisted.
            'note' => ['sometimes', 'nullable', 'string', 'max:200'],
        ];
    }
}
