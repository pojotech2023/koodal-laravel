<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A verification submission in the review queue — the row, never the file.
 *
 * There is no `url` and no `path` key here, and that is the whole design of
 * this table's read surface: the document is reachable only through
 * `GET /admin/verifications/{id}/document`, which mints a five-minute signed
 * URL and writes an audit row naming the staff member on every single view.
 * A URL embedded in a list response would make the entire queue a bulk
 * download of government IDs with no trail behind it.
 *
 * `document_number_hash` is absent for a different reason: it is an HMAC over
 * a twelve-digit space and it is only ever compared, never read.
 */
class AdminVerificationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'type_label' => config('matrimony.verification.types.'.$this->type, $this->type),
            'status' => $this->status,
            'remarks' => $this->remarks,

            'mime_type' => $this->mime_type,
            'size_bytes' => $this->size_bytes,

            'user' => $this->whenLoaded('user', fn () => [
                'id' => $this->user->id,
                'profile_code' => $this->user->profile_code,
                // The reviewer's actual task is comparing this against the
                // name on the document, so it is the one field that has to be
                // on the row rather than a click away.
                'name' => $this->user->name,
                'gender' => $this->user->gender,
                'date_of_birth' => $this->user->date_of_birth?->toDateString(),
                'status' => $this->user->status,
            ]),

            'reviewed_by' => $this->whenLoaded('reviewer', fn () => $this->reviewer?->name),
            'reviewed_at' => $this->reviewed_at?->toIso8601String(),
            'submitted_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
