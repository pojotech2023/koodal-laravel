<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/**
 * A message in a moderated transcript.
 *
 * Two differences from `MessageResource`, both deliberate:
 *
 *   • The sender is named. A member's own thread has two people in it and
 *     `is_mine` is enough; a moderator reading a harassment report needs to
 *     know which account said what, and `sender_id` alone means cross-checking
 *     every line against a lookup.
 *   • Soft-deleted messages are rendered, flagged. A message deleted right
 *     after it was sent is very often the entire complaint — the transcript
 *     that quietly omits it shows the moderator a conversation in which
 *     nothing happened.
 *
 * Attachments are signed for two minutes rather than the member-facing TTL.
 * Long enough to look at, short enough that a link pasted anywhere is already
 * dead.
 */
class AdminMessageResource extends JsonResource
{
    protected const ATTACHMENT_TTL_MINUTES = 2;

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'conversation_id' => $this->conversation_id,
            'type' => $this->type,
            'body' => $this->body,

            'sender' => $this->whenLoaded('sender', fn () => [
                'id' => $this->sender->id,
                'profile_code' => $this->sender->profile_code,
                'name' => $this->sender->name,
            ]),

            'attachment' => $this->when(
                $this->attachment_path !== null,
                fn () => [
                    'url' => Storage::disk('private')->temporaryUrl(
                        $this->attachment_path,
                        now()->addMinutes(static::ATTACHMENT_TTL_MINUTES),
                    ),
                    'expires_in_minutes' => static::ATTACHMENT_TTL_MINUTES,
                    'meta' => $this->attachment_meta ?? [],
                ],
            ),

            'status' => $this->status,
            'read_at' => $this->read_at?->toIso8601String(),

            // The flag, not the omission.
            'deleted_at' => $this->deleted_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
