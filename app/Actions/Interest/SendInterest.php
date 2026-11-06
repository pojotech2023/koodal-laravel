<?php

namespace App\Actions\Interest;

use App\Events\InterestSent;
use App\Exceptions\DuplicateInterestException;
use App\Models\Interest;
use App\Models\User;
use App\Services\Membership\FeatureGate;
use App\Services\Profile\ProfileVisibility;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

/**
 * The single place a matrimonial interest is created.
 *
 * Every caller — website, app, and any future integration — comes through
 * here, so the invariants live in one file:
 *
 *   • the plan quota is checked BEFORE the write and consumed after, so a
 *     failed insert never costs the user a unit;
 *   • visibility is re-checked server-side, because the client only knows what
 *     it was shown, and what it was shown may be minutes stale;
 *   • a reciprocal pending interest auto-accepts into a match — if both people
 *     independently reached out, making them wait for a second tap is pure
 *     friction;
 *   • the unique index is the real duplicate guard. A check-then-insert races
 *     under double-taps, which on mobile is not a rare event.
 */
class SendInterest
{
    public function __construct(
        protected FeatureGate $features,
        protected ProfileVisibility $visibility,
        protected AcceptInterest $acceptInterest,
    ) {}

    public function __invoke(User $sender, User $receiver, ?string $message = null): Interest
    {
        if ($sender->is($receiver)) {
            throw new \InvalidArgumentException('You cannot send an interest to yourself.');
        }

        // 404 rather than 403 — never confirm that a profile hiding from this
        // viewer exists. See ProfileVisibility.
        if (! $this->visibility->canViewProfile($sender, $receiver)) {
            throw new ModelNotFoundException('Profile not found.');
        }

        $this->features->assertQuota($sender, 'interests_per_month');

        // Both people already reached out — skip the pending state entirely.
        //
        // The actor is $sender, not $receiver: the reciprocal row was sent BY
        // the receiver TO the sender, so the sender is the one entitled to
        // accept it. AcceptInterest checks exactly that, and passing the wrong
        // side turns every mutual interest into a 403.
        if ($reciprocal = $this->pendingFrom($receiver, $sender)) {
            return ($this->acceptInterest)($reciprocal, $sender);
        }

        try {
            $interest = DB::transaction(function () use ($sender, $receiver, $message) {
                $interest = Interest::create([
                    'sender_id' => $sender->id,
                    'receiver_id' => $receiver->id,
                    'status' => 'pending',
                    'message' => $message,
                ]);

                $receiver->profile()->increment('interests_received_count');

                return $interest;
            });
        } catch (QueryException $e) {
            // 1062 = duplicate key. The unique index caught a double-tap.
            throw $e->errorInfo[1] === 1062
                ? new DuplicateInterestException('You have already sent an interest to this profile.')
                : $e;
        }

        // Counted only after the row exists, so the tally can never run ahead
        // of reality.
        $this->features->consume($sender, 'interests_per_month');

        event(new InterestSent($interest));

        return $interest;
    }

    protected function pendingFrom(User $sender, User $receiver): ?Interest
    {
        return Interest::where('sender_id', $sender->id)
            ->where('receiver_id', $receiver->id)
            ->where('status', 'pending')
            ->first();
    }
}
