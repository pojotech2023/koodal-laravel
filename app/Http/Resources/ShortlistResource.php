<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\MissingValue;

/**
 * Like InterestResource, this always renders "the other person" — whichever
 * side of the row the viewer is on. The client should not have to work that
 * out from two differently-shaped lists.
 */
class ShortlistResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $isOwner = $request->user()?->id === $this->user_id;

        $counterpart = $isOwner
            ? $this->whenLoaded('shortlistedUser')
            : $this->whenLoaded('user');

        return [
            'id' => $this->id,
            'direction' => $isOwner ? 'mine' : 'received',
            // The note is the owner's private annotation. Nobody who was
            // shortlisted should ever read what was written about them.
            'note' => $this->when($isOwner, $this->note),
            'created_at' => $this->created_at?->toIso8601String(),
            'profile' => $counterpart instanceof MissingValue
                ? $counterpart
                : new ProfileResource($counterpart->profile),
        ];
    }
}
