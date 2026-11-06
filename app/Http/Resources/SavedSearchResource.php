<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SavedSearchResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            // Returned verbatim so the client can re-apply the set to its own
            // filter panel without a translation layer.
            'filters' => $this->filters ?? [],
            'alert_enabled' => (bool) $this->alert_enabled,
            'last_alerted_at' => $this->last_alerted_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
