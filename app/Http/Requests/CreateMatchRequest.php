<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * The website's "challenge by email" form. (The API invites a member by
 * profile code instead; see Api\V1\User\Games\SpotController.)
 */
class CreateMatchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'puzzle_id' => ['required', 'integer', 'exists:puzzles,id'],
            'invited_email' => ['required', 'email', 'max:255'],
        ];
    }
}
