<?php

namespace App\Http\Resources\Admin;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * An interest as an investigator reads it, which is a different object from
 * `InterestResource`.
 *
 * The member-facing resource shows one side of the exchange and hides the
 * other's identity until it is mutual. This shows both, because the question
 * staff bring to this table is never "who likes whom" — it is "is this account
 * spraying interests at everybody", and that is unanswerable without the
 * sender and the receiver on the same row.
 *
 * The attached message travels too. It is the most common carrier of abuse in
 * the product: 500 characters that reach a stranger before any consent, so a
 * report about an interest is usually a report about its message.
 */
class AdminInterestResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'status' => $this->status,
            'message' => $this->message,

            'sender' => $this->whenLoaded('sender', fn () => $this->party($this->sender)),
            'receiver' => $this->whenLoaded('receiver', fn () => $this->party($this->receiver)),

            // Filled per page by `InterestManagementController::hydrateVolume`,
            // not by the Resource — a lazy count here would be an N+1 on the
            // one screen whose whole purpose is scanning a lot of rows.
            'sender_sent_count' => $this->whenNotNull($this->sender_sent_count),

            // Null on a `pending` or `cancelled` row: nobody answered. A
            // withdrawal is the sender changing their mind, not a response.
            'responded_at' => $this->responded_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }

    /** @param  User|null  $user */
    protected function party($user): ?array
    {
        return $user ? [
            'id' => $user->id,
            'profile_code' => $user->profile_code,
            'name' => $user->name,
            'gender' => $user->gender,
            'status' => $user->status,
        ] : null;
    }
}
