<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A gated viewer receives the URL of the *blurred derivative* — a different
 * file — not the real URL plus a flag. A flag is a suggestion; a different
 * file is enforcement.
 */
class PhotoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $blurred = (bool) ($this->additional['blurred'] ?? false);

        return array_filter([
            'id' => $this->id,
            'url' => $blurred ? $this->urlFor('blurred') : $this->urlFor('full'),
            'thumbnail_url' => $blurred ? $this->urlFor('blurred') : $this->urlFor('thumbnail'),
            'blurred' => $blurred,
            'is_primary' => (bool) $this->is_primary,
            'sort_order' => $this->sort_order,

            // Moderation state is the owner's business only. Another viewer
            // has no reason to learn that a photo was rejected.
            'approval_status' => $this->when($this->isOwner($request), $this->approval_status),
            'rejection_reason' => $this->when($this->isOwner($request), $this->rejection_reason),
            'width' => $this->width,
            'height' => $this->height,
        ], fn ($v) => $v !== null);
    }

    protected function isOwner(Request $request): bool
    {
        return $request->user()?->id === $this->user_id;
    }
}
