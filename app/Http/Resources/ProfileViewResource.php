<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * One row per (person, day) rather than per view — the rows are grouped in the
 * query, so `view_count` is how many times that person looked that day.
 *
 * Direction is inferred from which relation the controller loaded. The grouped
 * query cannot select the constant side of the pair without adding it to the
 * GROUP BY, and adding a column to a GROUP BY to work out something the caller
 * already knows is the wrong trade.
 */
class ProfileViewResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $received = $this->relationLoaded('viewer');
        $counterpart = $received ? $this->viewer : $this->viewed;

        return [
            'direction' => $received ? 'received' : 'sent',
            'viewed_on' => $this->viewed_on,
            // An aggregate, so it arrives as a raw string rather than through
            // the model's datetime cast. Normalised here so every timestamp in
            // the API is ISO 8601.
            'last_viewed_at' => $this->last_viewed_at
                ? Carbon::parse($this->last_viewed_at)->toIso8601String()
                : null,
            'view_count' => (int) $this->view_count,
            'profile' => $counterpart?->profile
                ? new ProfileResource($counterpart->profile)
                : null,
        ];
    }
}
