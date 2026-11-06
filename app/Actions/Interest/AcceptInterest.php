<?php

namespace App\Actions\Interest;

use App\Events\InterestAccepted;
use App\Models\Conversation;
use App\Models\Interest;
use App\Models\MatchRecord;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Acceptance is where a match and its conversation come into existence.
 *
 * The conversation row is created here rather than lazily on first message, so
 * the thread already exists when the "interest accepted" push lands and the
 * user taps through — no empty-state flicker, no race between two people
 * opening the chat at once.
 */
class AcceptInterest
{
    public function __invoke(Interest $interest, User $actor): Interest
    {
        abort_unless($interest->receiver_id === $actor->id, 403, 'This interest was not sent to you.');
        abort_unless($interest->status === 'pending', 409, 'This interest has already been answered.');

        return DB::transaction(function () use ($interest) {
            $interest->update(['status' => 'accepted', 'responded_at' => now()]);

            [$one, $two] = $this->canonicalPair($interest->sender_id, $interest->receiver_id);

            $conversation = Conversation::firstOrCreate(
                ['user_one_id' => $one, 'user_two_id' => $two],
                ['status' => 'active'],
            );

            $match = MatchRecord::updateOrCreate(
                ['user_one_id' => $one, 'user_two_id' => $two],
                ['matched_at' => now(), 'conversation_id' => $conversation->id, 'status' => 'active'],
            );

            // The back-link, which cannot be set in the insert above: the
            // conversation has to exist before the match row that points at it.
            // Both directions are indexed and both are read — matches → thread
            // on the connections list, thread → match when chat checks that the
            // connection still stands.
            $conversation->update(['match_id' => $match->id, 'status' => 'active']);

            event(new InterestAccepted($interest, $conversation));

            return $interest->fresh();
        });
    }

    /**
     * Canonical ordering (lower id first) is what makes the unique index on
     * matches and conversations meaningful in both directions — without it,
     * (A,B) and (B,A) are two different rows and users end up with duplicate
     * threads.
     *
     * @return array{0: int, 1: int}
     */
    protected function canonicalPair(int $a, int $b): array
    {
        return $a < $b ? [$a, $b] : [$b, $a];
    }
}
