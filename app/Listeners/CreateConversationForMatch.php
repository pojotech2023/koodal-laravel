<?php

namespace App\Listeners;

use App\Events\InterestAccepted;
use App\Models\Conversation;
use App\Models\MatchRecord;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\DB;

/**
 * Finishes wiring the thread a match hangs off.
 *
 * AcceptInterest creates the conversation inside its own transaction — that is
 * deliberate, so the row exists before the "you're connected" push lands and
 * the user taps through to an empty state. What it cannot do in one pass is
 * set `conversations.match_id`: the conversation must exist before the match
 * row that references it, so the back-link is only knowable afterwards.
 *
 * The second job here is reactivation. A pair who matched, drifted, archived
 * the thread — or blocked each other and later unblocked — and then matched
 * again must land in an active conversation. Reaching this listener at all
 * proves no block stands between them, since ProfileVisibility would have
 * turned the interest into a 404 long before it could be accepted.
 *
 * Idempotent throughout: replaying the event changes nothing.
 */
class CreateConversationForMatch implements ShouldQueue
{
    use InteractsWithQueue;

    public int $tries = 3;

    public array $backoff = [10, 60, 300];

    public function handle(InterestAccepted $event): void
    {
        $conversation = $event->conversation->fresh();

        if (! $conversation) {
            return;     // deleted with one of the accounts
        }

        DB::transaction(function () use ($conversation) {
            $changes = [];

            if ($conversation->match_id === null) {
                $changes['match_id'] = $this->matchIdFor($conversation);
            }

            if ($conversation->status !== 'active') {
                $changes['status'] = 'active';
            }

            if ($changes !== []) {
                $conversation->update($changes);
            }
        });
    }

    /**
     * Looked up by the canonical pair rather than trusted from the event: the
     * match row is written after the conversation, and on the auto-match path
     * an earlier MatchRecord for the same two people may already exist.
     */
    protected function matchIdFor(Conversation $conversation): ?int
    {
        [$one, $two] = MatchRecord::pair($conversation->user_one_id, $conversation->user_two_id);

        return MatchRecord::where('user_one_id', $one)
            ->where('user_two_id', $two)
            ->value('id');
    }
}
