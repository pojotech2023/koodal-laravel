<?php

namespace App\Http\Requests\Search;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/**
 * A saved search is replayed straight back into the search query later, and by
 * a scheduled alert job that no user is watching. So the stored set is
 * restricted to keys search actually understands rather than whatever JSON the
 * client happened to send — an unknown key would sit in the database until it
 * silently did nothing on replay.
 */
class SaveSearchRequest extends FormRequest
{
    /** Saved searches drive alert emails; an unbounded list is an outbox. */
    protected const MAX_SAVED = 20;

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'filters' => ['required', 'array', 'min:1'],
            'alert_enabled' => ['sometimes', 'boolean'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $unknown = array_diff(
                array_keys((array) $this->input('filters', [])),
                SearchRequest::filterKeys(),
            );

            if ($unknown !== []) {
                $validator->errors()->add('filters', 'Unsupported filter: '.implode(', ', $unknown));
            }

            $this->assertUnderLimit($validator);
        });
    }

    /**
     * Re-saving under an existing name is an update, not a new row, so it must
     * not be refused once the user is at the cap.
     */
    protected function assertUnderLimit(Validator $validator): void
    {
        $user = $this->user();

        if ($user->savedSearches()->where('name', $this->input('name'))->exists()) {
            return;
        }

        if ($user->savedSearches()->count() >= self::MAX_SAVED) {
            $validator->errors()->add(
                'name',
                'You can keep up to '.self::MAX_SAVED.' saved searches. Delete one to save another.',
            );
        }
    }
}
