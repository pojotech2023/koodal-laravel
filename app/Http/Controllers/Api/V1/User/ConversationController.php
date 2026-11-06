<?php

namespace App\Http\Controllers\Api\V1\User;

use App\Actions\Chat\MarkConversationRead;
use App\Actions\Chat\StartConversation;
use App\Http\Controllers\Concerns\ConversationAccess;
use App\Http\Controllers\Controller;
use App\Http\Requests\Chat\MarkConversationReadRequest;
use App\Http\Requests\Chat\StartConversationRequest;
use App\Http\Resources\ConversationResource;
use App\Models\Conversation;
use App\Models\User;
use App\Services\Chat\ConversationParticipantHydrator;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ConversationController extends Controller
{
    use ConversationAccess;

    public function __construct(protected ConversationParticipantHydrator $hydrator) {}

    /**
     * The most-polled screen in the app. Every field the list renders is a
     * denormalised column on `conversations`, so this is one indexed read plus
     * the two hydration queries below — never a join onto `messages`.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $conversations = Conversation::query()
            ->forUser($user->id)
            // Blocked threads are gone as far as both participants are
            // concerned. The row survives because a report filed next week is
            // investigated from it, and admin moderation reads it.
            ->where('status', $request->boolean('archived') ? 'archived' : 'active')
            // A thread AcceptInterest just created has no last_message_at yet,
            // and a brand-new connection belongs at the top of the list, not
            // silently below every old chat. Costs a filesort over one member's
            // conversations — tens of rows, not the (user, last_message_at)
            // index's reason for existing.
            ->orderByRaw('COALESCE(last_message_at, created_at) DESC')
            ->paginate($request->integer('per_page', 20));

        $this->hydrator->hydrate(collect($conversations->items()), $user);

        return ApiResponse::paginated($conversations, ConversationResource::class, 'Conversations retrieved successfully');
    }

    public function show(Conversation $conversation, Request $request): JsonResponse
    {
        $this->assertVisible($conversation, $request->user());

        $this->hydrator->hydrate(collect([$conversation]), $request->user());

        return ApiResponse::success(new ConversationResource($conversation), 'Conversation retrieved successfully');
    }

    /**
     * Under the default `chat.requires = mutual_interest` this is almost always
     * a lookup: AcceptInterest created the thread at the moment of mutual
     * acceptance. It still exists as an endpoint because the `open` and
     * `premium` rules have no other way in.
     */
    public function store(StartConversationRequest $request, StartConversation $startConversation): JsonResponse
    {
        $conversation = $startConversation(
            starter: $request->user(),
            // Loaded with the relations ProfileVisibility reads. Resolving the
            // target with a bare findOrFail leaves the consent checks lazy-
            // loading privacy and approval state, which preventLazyLoading
            // throws on outside production.
            target: User::with(['profile', 'privacySettings'])->findOrFail($request->integer('user_id')),
        );

        $this->hydrator->hydrate(collect([$conversation]), $request->user());

        // A second POST for a thread that already exists is not an error — the
        // client tapped "Message" on a profile it is already connected to.
        return $conversation->wasRecentlyCreated
            ? ApiResponse::created(new ConversationResource($conversation), 'Conversation started')
            : ApiResponse::success(new ConversationResource($conversation), 'Conversation retrieved successfully');
    }

    public function markRead(
        Conversation $conversation,
        MarkConversationReadRequest $request,
        MarkConversationRead $markConversationRead,
    ): JsonResponse {
        $marked = $markConversationRead(
            $conversation,
            $request->user(),
            $request->integer('last_message_id') ?: null,
        );

        return ApiResponse::success([
            'marked_read' => $marked,
            // Re-read rather than computed: the badge is what the client is
            // here for, and returning the stored value proves the counter and
            // the messages agree.
            'unread_count' => $conversation->fresh()?->unreadCountFor($request->user()->id) ?? 0,
        ], 'Conversation marked as read');
    }

    /**
     * Archiving is shared, not per-participant — `conversations.status` is one
     * column. That is a deliberate simplification of the schema rather than an
     * oversight, and it is survivable because it is not destructive: a new
     * message from either side pulls the thread straight back into the list.
     */
    public function archive(Conversation $conversation, Request $request): JsonResponse
    {
        $this->assertVisible($conversation, $request->user());

        $conversation->update(['status' => 'archived']);

        return ApiResponse::success(null, 'Conversation archived');
    }
}
