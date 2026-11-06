<?php

namespace App\Http\Requests\Contact;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Approve or reject a contact request.
 *
 * Parameters:
 * - request_id: (int, optional if requester_id is given) Contact approval row ID.
 * - requester_id: (int, optional) Requester user ID.
 * - status: (string, required) 'approved' or 'rejected'.
 */
class RespondContactAccessRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'request_id' => ['required_without:requester_id', 'nullable', 'integer', 'exists:contact_approvals,id'],
            'requester_id' => ['required_without:request_id', 'nullable', 'integer', 'exists:users,id'],
            'status' => ['required', 'string', 'in:approved,rejected'],
        ];
    }
}
