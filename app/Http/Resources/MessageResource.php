<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Attachment URL minting itself lives on `Message::attachmentPayload()` now
 * (PROGRESS.md §6) — shared with `Web\MessageController`'s Blade thread —
 * this resource just decides whether the key appears at all.
 */
class MessageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $viewer = $request->user();

        return array_filter([
            'id' => $this->id,
            'conversation_id' => $this->conversation_id,
            'sender_id' => $this->sender_id,
            // Saves every client the same three-line comparison on every bubble.
            'is_mine' => $viewer?->id === $this->sender_id,
            'type' => $this->type,
            'body' => $this->body,

            'attachment' => $this->when(
                $this->attachment_path !== null,
                fn () => $this->resource->attachmentPayload(),
            ),

            'status' => $this->status,
            'delivered_at' => $this->delivered_at?->toIso8601String(),
            'read_at' => $this->read_at?->toIso8601String(),

            // Echoed so an optimistically-rendered bubble is reconciled with
            // the server row rather than drawn a second time beside it.
            'client_message_id' => $this->client_message_id,
            'created_at' => $this->created_at?->toIso8601String(),
        ], fn ($value) => $value !== null);
    }
}
