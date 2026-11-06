<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A finished export, projected from the `export_ready` notification that
 * announced it. There is no `exports` table — the notification *is* the record,
 * and `WritesCsvExport::announceExport` is what writes it.
 *
 * `path` is absent, and this is the same rule the report evidence follows: a
 * private-disk path in a JSON response is either useless to the caller or a
 * leak. The id travels instead, and the download endpoint resolves the path
 * server-side.
 */
class AdminExportResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $data = (array) $this->data;

        return [
            'id' => $this->id,

            // 'members' | 'payments' — whatever the job passed to
            // `announceExport`. Also what decides which permission the download
            // re-checks.
            'subject' => $data['subject'] ?? null,

            // Only the basename. The directory is an implementation detail of
            // the private disk and the client has no use for it.
            'filename' => isset($data['path']) ? basename((string) $data['path']) : null,

            'rows' => $this->whenNotNull($data['rows'] ?? null),

            // Set by the controller, which is the only place that can answer it
            // — the file may have been swept by `PruneStaleRecords` long after
            // this notification was written.
            'available' => $this->whenNotNull($this->available ?? null),

            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
