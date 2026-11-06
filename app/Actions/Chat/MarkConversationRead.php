<?php

namespace App\Actions\Chat;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use App\Services\Chat\Contracts\RealtimeTransport;
use Illuminate\Support\Facades\DB;

/**
 * Reads are marked in bulk to a high-water mark, never per message.
 *
 * Opening a thread after a day away can mean two hundred unread messages; a
 * per-message read call is two hundred requests, on the mobile network least
 * able to carry them. One statement covers the lot, and the receipt broadcast
 * carries the same high-water mark rather than a list of ids.
 */
class MarkConversationRead
{
    public function __construct(protected RealtimeTransport $transport) {}

    /** @return int how many messages this call actually moved to `read` */
    public function __invoke(Conversation $conversation, User $reader, ?int $upToMessageId = null): int
    {
        abort_unless($conversation->includes($reader->id), 404);

        // A client that opened the thread and scrolled to the bottom means "all
        // of it" and should not have to look up an id to say so.
        $upTo = $upToMessageId ?? (int) $conversation->messages()->max('id');

        if ($upTo <= 0) {
            return 0;
        }

        $marked = DB::transaction(fn () => $this->mark($conversation, $reader, $upTo));

        // Nothing changed means nothing to announce — re-broadcasting on every
        // poll of an already-read thread is a frame per poll per participant.
        if ($marked > 0) {
            // Privacy lives in the transport: a reader who hid their online
            // status has also opted out of announcing exactly when they read.
            // The receipt is still stored — it is their own unread state.
            $this->transport->publishRead($conversation, $reader, $upTo);
        }

        return $marked;
    }

    // --- Internals ---------------------------------------------------------

    protected function mark(Conversation $conversation, User $reader, int $upTo): int
    {
        $now = now();

        $marked = Message::query()
            ->where('conversation_id', $conversation->id)
            // Your own messages are not yours to read. Without this the reader
            // would decrement their own unread count for messages they sent.
            ->where('sender_id', '!=', $reader->id)
            ->where('id', '<=', $upTo)
            // Only rows that were genuinely unread, so the count returned below
            // is exactly what the badge should come down by.
            ->whereNull('read_at')
            ->update([
                'status' => 'read',
                'read_at' => $now,
                // A message that was read was certainly delivered. There is no
                // separate delivery-ack endpoint — the socket ack and the FCM
                // receipt both surface here — so backfilling is what makes the
                // double tick appear at all. COALESCE keeps a real earlier
                // delivery timestamp; the literal is server-generated, never
                // request input.
                'delivered_at' => DB::raw("COALESCE(delivered_at, '{$now->toDateTimeString()}')"),
                'updated_at' => $now,
            ]);

        if ($marked === 0) {
            return 0;
        }

        $column = $conversation->unreadColumnFor($reader->id);

        // Decremented by exactly what was marked rather than zeroed: a client
        // marking up to a mid-thread id must not clear the badge for the
        // messages below it. Exact by construction — SendMessage increments this
        // column once per unread row, and this is the only thing that lowers it.
        // CASE rather than GREATEST(col - n, 0) because the column is UNSIGNED
        // and MySQL errors on the intermediate negative rather than clamping it.
        Conversation::whereKey($conversation->id)->update([
            $column => DB::raw("CASE WHEN {$column} > {$marked} THEN {$column} - {$marked} ELSE 0 END"),
        ]);

        return $marked;
    }
}
