<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A photo in the moderation grid.
 *
 * Staff get the real file, never the blurred derivative — `PhotoResource`
 * hands a gated *member* a different file, and doing the same here would mean
 * moderating an image nobody can see. The dimensions and byte size travel with
 * it because "poor quality" is one of the configured rejection reasons and a
 * 40 KB 300×300 upload is decidable from the numbers alone.
 */
class AdminPhotoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'url' => $this->urlFor('full'),
            'thumbnail_url' => $this->urlFor('thumbnail'),

            'approval_status' => $this->approval_status,
            'rejection_reason' => $this->rejection_reason,
            'is_primary' => (bool) $this->is_primary,
            'sort_order' => $this->sort_order,

            'width' => $this->width,
            'height' => $this->height,
            'size_bytes' => $this->size_bytes,
            'mime_type' => $this->mime_type,

            // The queue is sorted oldest-first, so the owner is what tells a
            // moderator that eleven consecutive rejections came from one
            // account rather than eleven people having a bad day.
            'user' => $this->whenLoaded('user', fn () => [
                'id' => $this->user->id,
                'profile_code' => $this->user->profile_code,
                'name' => $this->user->name,
                'gender' => $this->user->gender,
            ]),

            'moderated_by' => $this->whenLoaded('moderator', fn () => $this->moderator?->name),
            'moderated_at' => $this->moderated_at?->toIso8601String(),
            'uploaded_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
