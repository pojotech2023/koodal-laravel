<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

/**
 * A partial update of the settings table: `{ "settings": { "key": value } }`.
 *
 * Keys are **not** validated against a list here. The rows are the schema —
 * `settings.type` says what each value is and `SettingsController` casts
 * against it — so an allow-list in code would have to be edited every time a
 * row is added, which defeats the point of a settings table.
 *
 * What is refused is *creating* a key. A settings screen that silently accepts
 * a typo'd key writes a row nothing reads, and the admin sees a saved value
 * that does nothing.
 */
class UpdateSettingsRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'settings' => ['required', 'array', 'min:1'],
            'settings.*' => ['present'],
        ];
    }

    public function messages(): array
    {
        return [
            'settings.required' => 'Send the changed keys under a `settings` object.',
        ];
    }
}
