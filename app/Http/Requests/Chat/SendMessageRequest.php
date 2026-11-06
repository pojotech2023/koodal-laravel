<?php

namespace App\Http\Requests\Chat;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SendMessageRequest extends FormRequest
{
    /**
     * `type` defaults here rather than in the action so `required_if` has
     * something to compare against — a client that omits it is sending text.
     */
    protected function prepareForValidation(): void
    {
        $this->merge(['type' => $this->input('type', 'text')]);
    }

    public function rules(): array
    {
        $image = config('matrimony.media.photo');

        return [
            // `system` is deliberately absent: system messages caption a state
            // change the server made, and a client that can forge one can forge
            // "you are now connected" into a thread.
            'type' => ['required', Rule::in(['text', 'image'])],

            'body' => [
                'required_if:type,text', 'nullable', 'string',
                'max:'.config('matrimony.chat.message_max_length'),
            ],

            'attachment' => [
                'required_if:type,image', 'nullable', 'file',
                'mimes:'.implode(',', $image['mimes']),
                'max:'.$image['max_size_kb'],
            ],

            // Required, not optional. It is the only thing standing between a
            // retry on a flaky connection and a duplicate message, and a client
            // allowed to omit it will omit it. See docs/10-chat-realtime.md §7.
            'client_message_id' => ['required', 'uuid'],
        ];
    }

    public function messages(): array
    {
        return [
            'body.required_if' => 'A message cannot be empty.',
            'attachment.required_if' => 'Select an image to send.',
            'client_message_id.required' => 'A client_message_id is required so retries do not duplicate messages.',
        ];
    }
}
