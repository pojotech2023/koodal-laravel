<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\MissingValue;

/**
 * The conversation list is the most-polled screen in the app, so every field
 * here is read from a denormalised column on `conversations` — there is not one
 * query behind this resource, not even for the last message.
 *
 * `participant` is hydrated by the controller as a synthetic relation rather
 * than eager-loaded as `userOne`/`userTwo`: loading both sides to render one of
 * them doubles the profile tree fetched for every row of the page.
 */
class ConversationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $viewer = $request->user();
        $participant = $this->whenLoaded('participant');

        $partnerUser = ! ($participant instanceof MissingValue) ? $this->participant : null;
        $gamesCompleted = false;
        $canAccessChat = true;

        if ($viewer && $partnerUser) {
            $gamesCompleted = app(\App\Services\KoodalGameService::class)->hasCompletedAllGames($viewer, $partnerUser);
            $canAccessChat = $viewer->hasPaidPlan() || $gamesCompleted;
        }

        return [
            'id' => $this->id,
            'status' => $this->status,
            'can_access_chat' => (bool) $canAccessChat,
            'games_completed' => (bool) $gamesCompleted,
            'chat_locked' => ! $canAccessChat,

            // A deleted account leaves its conversations behind for the other
            // participant; the thread is still readable, it just has nobody on
            // the far end any more.
            'participant' => $participant instanceof MissingValue || $this->participant?->profile === null
                ? null
                : new ProfileResource($this->participant->profile),

            // Hydrated per page from Redis. Absent — not false — when presence
            // is unavailable, so the client keeps its last known state instead
            // of flipping everyone offline the moment Redis blinks.
            'participant_online' => $this->when(
                isset($this->participant_online),
                fn () => (bool) $this->participant_online,
            ),

            'unread_count' => $viewer ? $this->unreadCountFor($viewer->id) : 0,
            'messages_count' => (int) $this->messages_count,

            'last_message' => $this->last_message_at === null ? null : [
                'preview' => $this->last_message_preview,
                'sender_id' => $this->last_message_sender_id,
                'is_mine' => $viewer?->id === $this->last_message_sender_id,
                'sent_at' => $this->last_message_at?->toIso8601String(),
            ],

            'last_message_at' => $this->last_message_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
