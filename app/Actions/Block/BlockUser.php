<?php

namespace App\Actions\Block;

use App\Models\BlockedUser;
use App\Models\Conversation;
use App\Models\Interest;
use App\Models\MatchRecord;
use App\Models\Shortlist;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Blocking is not just a row — it is a teardown.
 *
 * A block that only writes to `blocked_users` leaves the two of them still
 * matched, still holding an open conversation, still counted in each other's
 * shortlists. ProfileVisibility hides the profile, but the chat thread was
 * reached by conversation id and never went through it. The person you blocked
 * can still message you, which is the exact failure the feature exists to
 * prevent.
 *
 * So everything connecting them comes down in one transaction:
 *
 *   • pending interests in both directions are cancelled — a pending interest
 *     you can no longer see is one you can never answer;
 *   • the match is unmatched and the conversation is marked `blocked`, which
 *     is what MessageController checks before accepting a send;
 *   • shortlists in both directions are removed, with the denormalised counter
 *     brought down in the same transaction.
 *
 * Accepted and declined interests are left alone. They are the history of what
 * happened, and a report filed next week is investigated from them.
 */
class BlockUser
{
    public function __invoke(User $blocker, User $target, ?string $reason = null): BlockedUser
    {
        if ($blocker->is($target)) {
            throw new \InvalidArgumentException('You cannot block your own profile.');
        }

        return DB::transaction(function () use ($blocker, $target, $reason) {
            // firstOrCreate, not create: blocking someone already blocked is a
            // double-tap, not an error, and must not 500 on the unique index.
            $block = BlockedUser::firstOrCreate(
                ['user_id' => $blocker->id, 'blocked_user_id' => $target->id],
                ['reason' => $reason],
            );

            if (! $block->wasRecentlyCreated) {
                return $block;
            }

            $this->cancelPendingInterests($blocker, $target);
            $this->severConnection($blocker, $target);
            $this->removeShortlists($blocker, $target);

            return $block;
        });
    }

    protected function cancelPendingInterests(User $a, User $b): void
    {
        Interest::query()
            ->between($a->id, $b->id)
            ->where('status', 'pending')
            ->update(['status' => 'cancelled', 'responded_at' => now()]);
    }

    protected function severConnection(User $a, User $b): void
    {
        [$one, $two] = MatchRecord::pair($a->id, $b->id);

        MatchRecord::where('user_one_id', $one)
            ->where('user_two_id', $two)
            ->update(['status' => 'unmatched']);

        // Not deleted. The thread is evidence if either of them files a report,
        // and admin moderation reads it. Marked so the send path refuses it.
        Conversation::where('user_one_id', $one)
            ->where('user_two_id', $two)
            ->update(['status' => 'blocked']);
    }

    /**
     * Both directions. A blocked person left sitting in your shortlist keeps
     * surfacing on a screen the block was supposed to clear.
     */
    protected function removeShortlists(User $a, User $b): void
    {
        foreach ([[$a, $b], [$b, $a]] as [$owner, $target]) {
            $deleted = Shortlist::query()
                ->where('user_id', $owner->id)
                ->where('shortlisted_user_id', $target->id)
                ->delete();

            if ($deleted > 0) {
                $target->profile()->where('shortlisted_count', '>', 0)->decrement('shortlisted_count');
            }
        }
    }
}
