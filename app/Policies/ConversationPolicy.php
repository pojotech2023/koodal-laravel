<?php

namespace App\Policies;

use App\Models\Conversation;
use App\Models\User;
use App\Policies\Concerns\ActsAsStaff;
use Illuminate\Auth\Access\Response;

class ConversationPolicy
{
    use ActsAsStaff;

    /**
     * `denyAsNotFound`, not `deny`. Conversation ids are sequential, so a 403
     * on someone else's thread confirms it exists — and for a thread closed by
     * a block it also tells the blocked user they were blocked, which turns a
     * safety feature into a notification.
     */
    public function view(User $user, Conversation $conversation): Response
    {
        return $conversation->includes($user->id) && $conversation->status !== 'blocked'
            ? Response::allow()
            : Response::denyAsNotFound();
    }

    /** Archiving and marking read. Same audience as reading it. */
    public function update(User $user, Conversation $conversation): Response
    {
        return $this->view($user, $conversation);
    }

    /**
     * Sending. Deliberately narrower than `view`: the plan capability and the
     * consent rules live in the Actions, because they need to distinguish a
     * paywall from a 404 from "send an interest first", and a Policy can only
     * say yes or no. This answers the participation question alone.
     */
    public function message(User $user, Conversation $conversation): Response
    {
        return $this->view($user, $conversation);
    }

    /**
     * Chat moderation is a genuine privacy trade-off, kept narrow on purpose:
     * read-only, one conversation at a time, no export, no cross-conversation
     * search, every access audited. Staff are NOT participants, so this is the
     * one ability that does not go through `view`.
     */
    public function moderate(User $user): bool
    {
        return $this->staffCan($user, 'chat.moderate');
    }
}
