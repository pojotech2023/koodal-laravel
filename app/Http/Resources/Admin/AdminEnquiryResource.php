<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A contact enquiry as support works it.
 *
 * There is no member-facing counterpart — `CmsController::contact` answers the
 * public form with `null`, deliberately, so the sender never sees the row that
 * was created. Everything on it is therefore internal by default and this is
 * the only class that projects it.
 *
 * The `message` is included in full, and in the list as well as the detail view.
 * Support triages on the first line of what someone wrote; a queue of subjects
 * with a click-through for the body means opening every row to find the urgent
 * one.
 */
class AdminEnquiryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,

            'name' => $this->name,
            'email' => $this->email,
            'mobile' => $this->mobile,

            'subject' => $this->subject,
            'message' => $this->message,

            'status' => $this->status,
            'response' => $this->response,

            // The name, not the id: this is read by a human deciding whether to
            // pick the row up, and `handled_by: 14` answers a different
            // question than the one being asked.
            'handled_by' => $this->whenLoaded('handler', fn () => $this->handler?->name),

            // Filled by the controller. Whether the enquirer is a registered
            // member changes the reply entirely — a member has a profile code, a
            // subscription and a history, and support can act on all three.
            'member' => $this->whenNotNull($this->member ?? null),

            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
