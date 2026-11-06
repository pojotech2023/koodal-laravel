<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Filters for `GET /admin/interests`.
 *
 * A FormRequest rather than an inline `$request->validate()` — which is what
 * the other read-only admin queues use — because this one has eight parameters
 * and three of them are dates that the controller then feeds straight into a
 * WHERE clause. Validating a date format in the same expression that builds the
 * query is how an unbounded scan gets shipped.
 *
 * Authorisation is not here. `InterestPolicy::viewAny` runs in the controller,
 * where the 403 reads as "you may not see interests" rather than as a
 * validation failure.
 */
class InterestQueryRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'status' => ['nullable', 'in:pending,accepted,declined,cancelled'],

            'sender_id' => ['nullable', 'integer'],
            'receiver_id' => ['nullable', 'integer'],
            'user_id' => ['nullable', 'integer'],

            'with_message' => ['nullable', 'boolean'],

            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],

            // Capped at 100 like every other admin list. The ceiling is what
            // stops a panel bug from asking for the whole table.
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }
}
