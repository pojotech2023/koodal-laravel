<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A thread's *metadata*, and nothing that was said in it.
 *
 * `last_message_preview` is denormalised onto the row and is deliberately not
 * returned. The listing endpoint is not audited — it is a lookup, not a read of
 * anyone's messages — and putting 160 characters of someone's chat in it would
 * make the unaudited endpoint the one that leaks content while the audited one
 * carries the trail.
 */
class AdminConversationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'status' => $this->status,
            'messages_count' => $this->messages_count,

            'participants' => [
                $this->participant('userOne'),
                $this->participant('userTwo'),
            ],

            'match_id' => $this->match_id,
            'last_message_at' => $this->last_message_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }

    /** @return array<string, mixed>|null */
    protected function participant(string $relation): ?array
    {
        if (! $this->relationLoaded($relation) || ! $user = $this->{$relation}) {
            return null;
        }

        return [
            'id' => $user->id,
            'profile_code' => $user->profile_code,
            'name' => $user->name,
            'gender' => $user->gender,
            'status' => $user->status,
        ];
    }
}
