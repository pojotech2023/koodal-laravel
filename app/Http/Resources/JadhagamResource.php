<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * The owner's view of one uploaded Jadhagam. Moderation state is included
 * because only the owner ever receives this resource — other members see an
 * approved chart on the profile page, never the queue it went through.
 */
class JadhagamResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'url' => $this->url(),
            'approval_status' => $this->approval_status,
            'rejection_reason' => $this->rejection_reason,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
