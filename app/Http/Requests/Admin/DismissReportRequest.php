<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Closing a report as not actionable.
 *
 * The note is optional here and mandatory on `resolve`, which looks
 * inconsistent and is not: nothing happens to anybody on a dismissal, so there
 * is no decision to defend on appeal. What the note feeds instead is the
 * category review — "duplicate", "retaliatory" — which is why it is offered at
 * all.
 */
class DismissReportRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'note' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
