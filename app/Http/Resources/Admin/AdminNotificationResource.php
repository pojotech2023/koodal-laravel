<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A delivery record — deliberately NOT the notification.
 *
 * `title` and `body` are absent, and that is the entire point of this class
 * rather than an oversight to be tidied up later. `SendMessageNotification`
 * sets the body to `Message::preview()`, so a notification log that returned
 * bodies would be a searchable, unaudited excerpt of every private chat on the
 * platform — reachable by any role holding `notifications.view`, which is
 * broader than the `chat.moderate` that guards the real thing. The chat
 * moderation surface is narrow on purpose (one conversation at a time, every
 * access audited); this would be the back door into it.
 *
 * What is left is what delivery health actually needs: which type, over which
 * channel, to whom, and whether it landed. Nothing here is member content.
 *
 * Announcement copy is not lost by this — `CmsManagementController::broadcast`
 * writes the title it queued into `audit_logs`, so staff read their own
 * announcements from the trail that already records who sent them.
 */
class AdminNotificationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'channel' => $this->channel,

            'recipient' => $this->whenLoaded('user', fn () => $this->user ? [
                'id' => $this->user->id,
                'profile_code' => $this->user->profile_code,
                'name' => $this->user->name,
            ] : null),

            // The four states a row can be in, as timestamps rather than as a
            // derived status string: "queued at 09:00, sent at 09:04" is the
            // shape of a backlog, and a single `status` field hides it.
            'sent_at' => $this->sent_at?->toIso8601String(),
            'read_at' => $this->read_at?->toIso8601String(),
            'failed_at' => $this->failed_at?->toIso8601String(),
            'failure_reason' => $this->failure_reason,

            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
