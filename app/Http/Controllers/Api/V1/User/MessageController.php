<?php

namespace App\Http\Controllers\Api\V1\User;

use App\Actions\Chat\SendMessage;
use App\Http\Controllers\Concerns\ConversationAccess;
use App\Http\Controllers\Controller;
use App\Http\Requests\Chat\SendMessageRequest;
use App\Http\Resources\MessageResource;
use App\Models\Conversation;
use App\Services\Chat\Contracts\RealtimeTransport;
use App\Services\Chat\PresenceStore;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MessageController extends Controller
{
    use ConversationAccess;

    public function __construct(
        protected RealtimeTransport $transport,
        protected PresenceStore $presence,
    ) {}

    /**
     * Keyset pagination, never offset. A thread is written to while it is being
     * scrolled, and `OFFSET 30` on a list that grew by two rows silently skips
     * two messages — the one bug in a chat client nobody ever reports clearly.
     */
    public function index(Conversation $conversation, Request $request): JsonResponse
    {
        $this->assertVisible($conversation, $request->user());

        $limit = min(max($request->integer('limit', config('matrimony.chat.page_size')), 1), 100);

        // limit + 1 answers "is there more" without a COUNT over the largest
        // table in the schema, once per thread open, for one boolean.
        $rows = $conversation->messages()
            ->before($request->integer('before_id') ?: null)
            ->orderByDesc('id')
            ->limit($limit + 1)
            ->get();

        $hasMore = $rows->count() > $limit;
        $page = $rows->take($limit);

        return ApiResponse::keyset(
            MessageResource::collection($page)->resolve($request),
            // The cursor is the oldest id on this page: the next request asks
            // for what comes before it.
            $hasMore ? $page->last()?->id : null,
            'Messages retrieved successfully',
        );
    }

    public function store(Conversation $conversation, SendMessageRequest $request, SendMessage $sendMessage): JsonResponse
    {
        $message = $sendMessage(
            conversation: $conversation,
            sender: $request->user(),
            payload: $request->safe()->only(['type', 'body', 'client_message_id']),
            attachment: $request->file('attachment'),
        );

        // A retry whose first response was lost gets 200 and the original row,
        // not a second 201. Either way the client reconciles its optimistic
        // bubble on the same `client_message_id`, so both are a success.
        return $message->wasRecentlyCreated
            ? ApiResponse::created(new MessageResource($message), 'Message sent')
            : ApiResponse::success(new MessageResource($message), 'Message already sent');
    }

    /**
     * Broadcast-only and never persisted — a typing indicator is true for the
     * two seconds it describes, and one that outlives the typing is worse than
     * none at all.
     */
    public function typing(Conversation $conversation, Request $request): JsonResponse
    {
        $user = $request->user();

        $this->assertVisible($conversation, $user);

        try {
            // The route's rate limit is the abuse guard. This is the one that
            // stops an ordinary typist emitting a frame per keystroke: the key
            // outlives the gap between two frames, so a sentence produces a
            // handful of broadcasts rather than thirty.
            if ($this->presence->isTyping($conversation->id, $user->id)) {
                return ApiResponse::noContent();
            }

            $this->presence->startTyping($conversation->id, $user->id);
        } catch (\Throwable) {
            // Redis IS the throttle. Without it the choice is a frame per
            // keystroke or no indicator, and docs/10-chat-realtime.md §12 is
            // explicit: Redis down means typing disappears, messaging does not.
            return ApiResponse::noContent();
        }

        $this->transport->publishTyping($conversation, $user);

        return ApiResponse::noContent();
    }
}
