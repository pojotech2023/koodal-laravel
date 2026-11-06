<?php

namespace App\Actions\Block;

use App\Models\BlockedUser;
use App\Models\User;

/**
 * Deletes the block row and nothing else — deliberately not the inverse of
 * BlockUser.
 *
 * Unblocking restores discoverability, not the relationship. The match stays
 * unmatched, the conversation stays closed, the shortlist entries stay gone.
 * Silently re-opening a channel someone shut is the opposite of what they
 * asked for, and they have no way to know it happened. If both of them want it
 * back, an interest costs one tap and goes through the normal consent path.
 *
 * A conversation marked `blocked` is reactivated by CreateConversationForMatch
 * if — and only if — they match again.
 */
class UnblockUser
{
    public function __invoke(User $blocker, User $target): bool
    {
        return BlockedUser::query()
            ->where('user_id', $blocker->id)
            ->where('blocked_user_id', $target->id)
            ->delete() > 0;
    }
}
