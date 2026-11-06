<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\MissingValue;

class InterestResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $viewer = $request->user();
        $isSender = $viewer?->id === $this->sender_id;

        // The list always shows "the other person", whichever direction this
        // interest went — the client should not have to work that out.
        $counterpart = $isSender
            ? $this->whenLoaded('receiver')
            : $this->whenLoaded('sender');

        return [
            'id' => $this->id,
            'direction' => $isSender ? 'sent' : 'received',
            'status' => $this->status,
            'message' => $this->message,
            'responded_at' => $this->responded_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'can_resend' => $isSender && $this->canResend(),
            'profile' => $counterpart instanceof MissingValue
                ? $counterpart
                : new ProfileResource($counterpart->profile),
        ];
    }
}
