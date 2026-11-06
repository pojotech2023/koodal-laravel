<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\MissingValue;

/**
 * The blocked person, rendered for the blocker's own list — the only place a
 * block is ever visible.
 *
 * Deliberately not a ProfileResource. That one runs every field through
 * ProfileVisibility, which by definition answers "no" for someone you blocked,
 * so a blocked profile would come back as an almost-empty object and the
 * unblock list would be a column of blanks. The four fields here are what the
 * row needs: enough to recognise who this was.
 */
class BlockResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $blocked = $this->whenLoaded('blockedUser');

        return [
            'id' => $this->id,
            // The blocker's private note to themselves.
            'reason' => $this->reason,
            'blocked_at' => $this->created_at?->toIso8601String(),
            'user' => $blocked instanceof MissingValue ? $blocked : [
                'id' => $blocked->id,
                'name' => $blocked->name,
                'profile_code' => $blocked->profile_code,
                // Read off the eager-loaded relation, never queried here.
                'photo' => $blocked->profile?->primaryPhoto
                    ? (new PhotoResource($blocked->profile->primaryPhoto))->resolve()
                    : null,
            ],
        ];
    }
}
