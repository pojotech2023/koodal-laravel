<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * One contact-details request, from the caller's side of it.
 *
 * `profile` is the other member's card (the requester when the caller owns the
 * contact details, the owner when the caller asked for them). `contact` is
 * present only to the requester, and only once the owner has approved.
 */
class ContactApprovalResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $viewerId = $request->user()?->id;
        $received = $viewerId === $this->owner_id;
        $counterpart = $received ? $this->requester : $this->owner;

        return [
            'id' => $this->id,
            'status' => $this->status,
            'direction' => $received ? 'received' : 'sent',
            'requester_id' => $this->requester_id,
            'owner_id' => $this->owner_id,
            'requested_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'profile' => $counterpart?->profile ? new ProfileResource($counterpart->profile) : null,
            'contact' => $this->when(
                ! $received && $this->status === 'approved' && $counterpart !== null,
                fn () => [
                    'mobile' => $counterpart->mobile,
                    'email' => $counterpart->email,
                    'whatsapp' => $counterpart->mobile,
                ],
            ),
        ];
    }
}
