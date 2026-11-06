<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Conversation;
use App\Models\User;

/**
 * The read-side access rule for a chat thread, in one place because two
 * controllers and six endpoints apply it and a route that forgets it is a
 * transcript handed to a stranger.
 *
 * Deliberately not a Policy yet: `AuthServiceProvider`'s `Gate::after` callback
 * is typed `?bool $result`, which a Policy returning
 * `Response::denyAsNotFound()` would violate — and 404-not-403 is exactly what
 * this rule needs. The nine Policies are designed together in Phase 6, and that
 * typehint has to be resolved first.
 */
trait ConversationAccess
{
    /**
     * Two different reasons, one indistinguishable answer.
     *
     * A non-participant gets 404 because conversation ids are sequential and
     * confirming that thread 8841 exists is an enumeration oracle. A blocked
     * thread gets the same 404 because the alternative tells someone they were
     * blocked, which turns a safety feature into a notification.
     */
    protected function assertVisible(Conversation $conversation, User $user): void
    {
        abort_unless($conversation->includes($user->id), 404);

        abort_if($conversation->status === 'blocked', 404);
    }
}
