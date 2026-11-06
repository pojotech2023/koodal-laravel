<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Everything a member needs to understand where their submission stands, and
 * nothing that points at the file.
 *
 * There is no `url` key and no `path` key, deliberately — the model hides both
 * from serialisation and the only way to the document is the audited admin
 * endpoint. A member re-checking their own upload gets the status, not a second
 * route into the private disk.
 */
class VerificationDocumentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'type_label' => config('matrimony.verification.types.'.$this->type, $this->type),
            'status' => $this->status,

            // The rejection reason is the whole point of showing this at all:
            // "rejected" without "the document was unreadable" is a member who
            // uploads the same blurred photo three more times.
            'remarks' => $this->remarks,
            'reviewed_at' => $this->reviewed_at?->toIso8601String(),

            // Who reviewed it is staff information. A member who knows which
            // moderator rejected them has someone to direct their anger at.
            'submitted_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
