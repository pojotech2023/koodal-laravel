<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\MissingValue;

/**
 * A report as its *reporter* sees it.
 *
 * Four things are absent on purpose, not by oversight: the moderator's name,
 * the internal resolution note, the action taken against the other member, and
 * the stored evidence paths. Telling a reporter "we suspended them" turns the
 * report queue into a weapon — file a report, learn whether it landed, adjust,
 * repeat — and a private-disk path is not something a client should ever hold.
 *
 * What they get is what they need: it was received, and it is being dealt with
 * or it is finished.
 */
class ReportResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $reported = $this->whenLoaded('reportedUser');

        return [
            'id' => $this->id,
            'category' => $this->category,
            'category_label' => config("matrimony.reports.categories.{$this->category}"),
            'description' => $this->description,

            // Collapsed to two states. `investigating` is an internal queue
            // position, and a reporter refreshing to watch it change is not a
            // feature we want to build.
            'status' => $this->isOpen() ? 'under_review' : 'closed',
            'evidence_count' => count($this->evidence_paths ?? []),

            'submitted_at' => $this->created_at?->toIso8601String(),
            'closed_at' => $this->resolved_at?->toIso8601String(),

            'reported_user' => $reported instanceof MissingValue ? $reported : [
                'name' => $reported->name,
                'profile_code' => $reported->profile_code,
            ],
        ];
    }
}
