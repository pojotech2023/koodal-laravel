<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * The couple's names and their story, and never their accounts.
 *
 * `groom_user_id` / `bride_user_id` exist on the row so the platform can prove
 * a story is real and can retire the profiles, but they are not returned:
 * publishing a success story must not hand every visitor a link to two members
 * who have just announced that they are off the market and are about to be
 * contacted by everyone who reads it.
 */
class SuccessStoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'groom_name' => $this->groom_name,
            'bride_name' => $this->bride_name,
            'marriage_date' => $this->marriage_date?->toDateString(),
            'story' => $this->story,
            'photo_url' => $this->photoUrl(),
            'location' => $this->location,

            'is_published' => (bool) $this->is_published,
            'is_featured' => (bool) $this->is_featured,
            'sort_order' => $this->sort_order,
        ];
    }
}
