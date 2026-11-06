<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * `image_url` and `mobile_image_url` are resolved here rather than shipping the
 * stored paths. Banners are on the public disk, so the URL is stable and
 * cacheable — the private-disk signing dance that photos and invoices need
 * does not apply, and pretending it does would put an expiry on a marketing
 * image the website caches for a day.
 */
class BannerResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'image_url' => $this->imageUrl(),
            'mobile_image_url' => $this->imageUrl(mobile: true),
            'link_url' => $this->link_url,
            'position' => $this->position,
            'sort_order' => $this->sort_order,

            'starts_at' => $this->starts_at?->toIso8601String(),
            'ends_at' => $this->ends_at?->toIso8601String(),
            'is_active' => (bool) $this->is_active,
        ];
    }
}
