<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A static page — terms, privacy, about.
 *
 * The meta fields are returned to every caller including the public one: they
 * exist to be rendered into `<head>` by the website, and a page whose SEO
 * metadata is only visible to admins is metadata that never reaches a crawler.
 */
class CmsPageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'title' => $this->title,
            'content' => $this->content,

            'meta' => [
                'title' => $this->meta_title ?: $this->title,
                'description' => $this->meta_description,
                'og_image' => $this->og_image_path,
            ],

            'is_published' => (bool) $this->is_published,
            'published_at' => $this->published_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
