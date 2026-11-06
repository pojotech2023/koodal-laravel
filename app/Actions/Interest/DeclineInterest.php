<?php

namespace App\Actions\Interest;

use App\Events\InterestDeclined;
use App\Models\Interest;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * The third answer to an interest, after accept and cancel.
 *
 * Two things it deliberately does NOT do.
 *
 * It does not give the sender's quota back. A declined interest still consumed
 * the receiver's attention, and refunding it would make an unlimited stream of
 * speculative interests free for anyone willing to be declined.
 *
 * It does not block. Declining is "not for me"; blocking is "do not contact
 * me". Collapsing the two would mean a member who declines twenty profiles has
 * silently blocked twenty people, and blocks are invisible from the outside —
 * they would never find out. The client offers both buttons; the user picks.
 *
 * `responded_at` is what Interest::canResend() measures the cooldown from, so
 * writing it is not bookkeeping — it is the harassment guard.
 */
class DeclineInterest
{
    public function __invoke(Interest $interest, User $actor): Interest
    {
        abort_unless($interest->receiver_id === $actor->id, 403, 'This interest was not sent to you.');
        abort_unless($interest->isPending(), 409, 'This interest has already been answered.');

        $interest = DB::transaction(function () use ($interest) {
            $interest->update(['status' => 'declined', 'responded_at' => now()]);

            return $interest->fresh();
        });

        event(new InterestDeclined($interest));

        return $interest;
    }
}
