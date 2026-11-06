<?php

namespace App\Actions\Chat;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use App\Services\Chat\Contracts\RealtimeTransport;
use App\Services\Media\ImageProcessor;
use App\Services\Membership\FeatureGate;
use Illuminate\Database\QueryException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

/**
 * Persist first, publish second — the rule the whole chat design rests on.
 *
 * MySQL is the truth; the socket is a delivery optimisation. If Reverb is down
 * the send still succeeds, history is intact, the FCM push still lands and the
 * client falls back to polling. Nothing is lost but immediacy. Broadcasting
 * before (or inside) the write would put the WebSocket server on the critical
 * path of message persistence, which is the one part of chat that must never be
 * at risk.
 *
 * Idempotency is the second load-bearing property. On Indian mobile networks a
 * request routinely succeeds while its response is lost, and the client retries.
 * Without `client_message_id` every flaky connection produces a double message.
 */
class SendMessage
{
    public function __construct(
        protected FeatureGate $features,
        protected RealtimeTransport $transport,
        protected ImageProcessor $images,
    ) {}

    /**
     * @param  array{type: string, body?: ?string, client_message_id: string}  $payload
     */
    public function __invoke(
        Conversation $conversation,
        User $sender,
        array $payload,
        ?UploadedFile $attachment = null,
    ): Message {
        $this->assertCanSend($conversation, $sender);

        // The fast path for a retry. The unique index below is the real guard —
        // this lookup just avoids re-encoding an image and burning an insert to
        // find out. Callers read `wasRecentlyCreated` to decide 201 vs 200.
        if ($existing = $this->existing($conversation, $payload['client_message_id'])) {
            return $existing;
        }

        // Outside the transaction on purpose. A re-encode is tens to hundreds of
        // milliseconds; holding the conversation row's lock for that long would
        // serialise a busy thread behind image processing.
        $stored = $attachment
            ? $this->images->processChatImage($attachment, $conversation->id)
            : null;

        try {
            $message = DB::transaction(fn () => $this->persist($conversation, $sender, $payload, $stored));
        } catch (QueryException $e) {
            // 1062 = duplicate key: two retries of the same send raced past the
            // lookup above. Not an error — the first one won, and its row is
            // exactly what the caller asked for.
            if (($e->errorInfo[1] ?? null) !== 1062) {
                throw $e;
            }

            // The loser's upload is orphaned on the private disk otherwise, and
            // nothing else will ever reference it.
            if ($stored) {
                $this->images->delete('private', [$stored['path']]);
            }

            return $this->existing($conversation, $payload['client_message_id']) ?? throw $e;
        }

        // Delivery, after commit, through the transport seam — so the same
        // Action works against Reverb or Firebase without knowing which.
        //
        // This is also the single raise point for the MessageSent event, which
        // SendMessageNotification listens to for the push half. ReverbTransport
        // dispatches it through broadcast(); FirebaseTransport dispatches it
        // explicitly after its RTDB write, for exactly this reason.
        $this->transport->publishMessage($conversation, $message);

        return $message;
    }

    // --- Internals ---------------------------------------------------------

    protected function assertCanSend(Conversation $conversation, User $sender): void
    {
        // 404, not 403. Conversation ids are sequential, and telling a stranger
        // that thread 8841 exists but is not theirs is an enumeration oracle.
        abort_unless($conversation->includes($sender->id), 404);

        // One neutral answer covers both "they blocked you" and "this thread is
        // closed". Distinguishing them tells a blocked user they were blocked,
        // which turns a safety feature into a notification.
        //
        // `conversations.status` is the authority here rather than a second
        // lookup against blocked_users: BlockUser marks the row inside the same
        // transaction as the block, and chat sends are the highest-volume write
        // in the product — an extra EXISTS on every one of them buys nothing.
        abort_if($conversation->status === 'blocked', 403, 'This conversation is no longer available.');

        // Re-checked on every send, not just at conversation creation: a lapsed
        // subscription must stop the sending, and the thread already existing is
        // not a licence that outlives the plan that opened it.
        //
        // who_can_message is deliberately NOT re-checked. The conversation row is
        // the consent record, and both ways consent is withdrawn — a block and an
        // unmatch — go through BlockUser, which marks the row.
        $this->features->assertAllowed($sender, 'can_chat');

        // Free members chat only after the pair has played all four games —
        // the same rule StartConversation applies, re-checked here because a
        // thread can already exist (AcceptInterest creates it on a match).
        if (! $sender->hasPaidPlan()) {
            $partnerId = $conversation->otherUserId($sender->id);

            if ($partnerId && ! app(\App\Services\KoodalGameService::class)->hasCompletedAllGames($sender->id, $partnerId)) {
                if (request()->is('api/*') || request()->expectsJson()) {
                    abort(response()->json([
                        'success' => false,
                        'chat_locked' => true,
                        'games_completed' => false,
                        'message' => 'You can access chat only after playing all the games.',
                    ], 403));
                }

                throw new \App\Exceptions\ChatNotAllowedException(
                    'You can access chat only after playing all 4 games together. Click \'Games & Vibe\' to play!'
                );
            }
        }
    }

    /**
     * withTrashed, because the unique index does not care that a message was
     * soft-deleted. A retry carrying the UUID of a message the sender has since
     * deleted would otherwise hit 1062 and surface as a 500.
     */
    protected function existing(Conversation $conversation, string $clientMessageId): ?Message
    {
        return Message::withTrashed()
            ->where('conversation_id', $conversation->id)
            ->where('client_message_id', $clientMessageId)
            ->first();
    }

    /**
     * @param  array{type: string, body?: ?string, client_message_id: string}  $payload
     * @param  array{path: string, width: int, height: int, size_bytes: int, mime_type: string}|null  $stored
     */
    protected function persist(Conversation $conversation, User $sender, array $payload, ?array $stored): Message
    {
        $message = Message::create([
            'conversation_id' => $conversation->id,
            'sender_id' => $sender->id,
            'type' => $payload['type'],
            // An image message carries no body. Accepting a caption here would
            // mean a second render path in three clients for one field nothing
            // in the spec asks for.
            'body' => $payload['type'] === 'image' ? null : ($payload['body'] ?? null),
            'attachment_path' => $stored['path'] ?? null,
            'attachment_meta' => $stored ? Arr::except($stored, 'path') : null,
            'status' => 'sent',
            'client_message_id' => $payload['client_message_id'],
        ]);

        $unreadColumn = $conversation->unreadColumnFor($conversation->otherUserId($sender->id));

        // One UPDATE maintains every denormalised column the conversation list
        // reads, so that screen stays a single indexed row with no joins.
        //
        // Counters are incremented in SQL rather than read-modify-written: two
        // people sending at the same instant would otherwise each write the
        // value they read, and one of the two messages would vanish from the
        // badge. The column name comes from unreadColumnFor(), which returns one
        // of two literals — never anything derived from the request.
        Conversation::whereKey($conversation->id)->update([
            'last_message_at' => $message->created_at,
            'last_message_preview' => $message->preview(),
            'last_message_sender_id' => $sender->id,
            'messages_count' => DB::raw('messages_count + 1'),
            $unreadColumn => DB::raw($unreadColumn.' + 1'),
            // A new message pulls an archived thread back into the list.
            // Archiving is "I am done with this for now", not "never again".
            'status' => 'active',
            'updated_at' => now(),
        ]);

        return $message;
    }
}
