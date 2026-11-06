<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * An announcement to a segment of the member base.
 *
 * The audience filters are an allow-list rather than a free query, because
 * this endpoint can put a notification on 128,000 phones and the blast radius
 * of a mistake is the whole product. Everything here narrows; nothing widens
 * beyond "every active member", which is already the default.
 */
class BroadcastNotificationRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:120'],
            // Deliberately short. This is push copy: anything past ~180
            // characters is truncated by both platforms, and writing to the
            // limit produces announcements that end mid-sentence on a phone
            // and read fine in the admin preview.
            'body' => ['required', 'string', 'max:180'],

            'audience' => ['sometimes', 'array'],
            'audience.gender' => ['nullable', Rule::in(['male', 'female'])],
            'audience.status' => ['nullable', Rule::in(['active', 'pending'])],
            'audience.plan_id' => ['nullable', 'integer', 'exists:membership_plans,id'],
            'audience.paid_only' => ['nullable', 'boolean'],
            'audience.registration_source' => ['nullable', Rule::in(['web', 'android', 'ios'])],

            // Deep link. Validated as a path rather than a URL so an
            // announcement cannot send the whole member base to a domain
            // somebody typed into an admin form.
            'route' => ['nullable', 'string', 'max:120', 'regex:/^\/[A-Za-z0-9\/_\-]*$/'],
        ];
    }

    public function messages(): array
    {
        return [
            'route.regex' => 'The deep link must be an in-app path such as /membership/plans.',
            'body.max' => 'Keep the message under 180 characters — both app stores truncate past that.',
        ];
    }
}
