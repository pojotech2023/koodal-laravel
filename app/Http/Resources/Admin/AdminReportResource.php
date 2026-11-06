<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A report as a moderator works it — the inverse of `ReportResource`, which
 * exists to withhold most of this from the person who filed it.
 *
 * Everything the reporter is denied is here: who is accused, who is assigned,
 * the internal note, the sanction applied. What is still withheld is the
 * evidence *files* — `evidence_paths` are private-disk paths and a path in a
 * JSON response is either useless or a leak. The count travels instead, and
 * the panel fetches each one through a signed URL when the moderator opens it.
 */
class AdminReportResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'category' => $this->category,
            'category_label' => config("matrimony.reports.categories.{$this->category}"),
            'description' => $this->description,

            'status' => $this->status,
            'action_taken' => $this->action_taken,
            'resolution_note' => $this->resolution_note,

            'reporter' => $this->whenLoaded('reporter', fn () => $this->reporter ? [
                'id' => $this->reporter->id,
                'profile_code' => $this->reporter->profile_code,
                'name' => $this->reporter->name,
            ] : null),

            'reported_user' => $this->whenLoaded('reportedUser', fn () => $this->reportedUser ? [
                'id' => $this->reportedUser->id,
                'profile_code' => $this->reportedUser->profile_code,
                'name' => $this->reportedUser->name,
                'status' => $this->reportedUser->status,
                // The count of *other* reports against this account is the
                // single most useful number on the screen: one complaint is
                // noise, five is a pattern, and a moderator judging each report
                // in isolation never sees the pattern.
                'reports_against_count' => $this->whenNotNull($this->reports_against_count),
            ] : null),

            'assigned_to' => $this->whenLoaded('assignee', fn () => $this->assignee?->name),
            'resolved_by' => $this->whenLoaded('resolver', fn () => $this->resolver?->name),
            'resolved_at' => $this->resolved_at?->toIso8601String(),

            'evidence_count' => count($this->evidence_paths ?? []),
            'evidence_message_ids' => $this->evidence_message_ids ?? [],

            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
