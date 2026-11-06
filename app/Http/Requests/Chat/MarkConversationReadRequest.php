<?php

namespace App\Http\Requests\Chat;

use Illuminate\Foundation\Http\FormRequest;

class MarkConversationReadRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            // Optional: a client that just opened the thread and scrolled to
            // the bottom means "all of it", and making it send an id it would
            // have to look up first is friction for the common case. The action
            // resolves the thread's newest message when it is absent.
            'last_message_id' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
