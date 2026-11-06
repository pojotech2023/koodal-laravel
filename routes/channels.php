<?php

use App\Models\Conversation;
use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast channels
|--------------------------------------------------------------------------
| Authorisation reuses the same Sanctum token the REST API uses, so there is
| no second auth system to keep in sync.
*/

Broadcast::channel('conversation.{id}', function (User $user, int $id) {
    return Conversation::where('id', $id)
        ->where(fn ($q) => $q->where('user_one_id', $user->id)->orWhere('user_two_id', $user->id))
        ->exists();
});

Broadcast::channel('user.{id}', fn (User $user, int $id) => $user->id === $id);

// Presence is suppressed for users who turned off online status — they should
// not appear in the roster at all, not merely be hidden client-side.
Broadcast::channel('online', function (User $user) {
    return $user->privacySettings?->show_online_status
        ? ['id' => $user->id, 'name' => $user->name]
        : false;
});
