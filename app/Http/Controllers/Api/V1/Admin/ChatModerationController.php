<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\AdminConversationResource;
use App\Http\Resources\Admin\AdminMessageResource;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\Report;
use App\Services\AuditLogger;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Reading other people's private messages.
 *
 * This exists because harassment reports are unresolvable without it, and it is
 * the single largest privacy trade-off in the product. Every restriction below
 * is deliberate and follows [chat §11](docs/10-chat-realtime.md):
 *
 *   • **No enumeration.** `index` refuses to list conversations without a
 *     `user_id` or `report_id` to anchor it. An unfiltered list is a browse of
 *     everybody's threads, which is a different feature from investigating a
 *     complaint and nobody asked for it.
 *   • **No content in the listing.** `last_message_preview` sits denormalised
 *     on the row and is not returned — see `AdminConversationResource`.
 *   • **No cross-conversation search.** There is no `q` parameter here and
 *     adding one would turn a targeted investigation into a corpus.
 *   • **No export.** Nothing on this controller queues a job or emits a file.
 *   • **Read-only.** No write endpoint exists; a moderator who needs to stop a
 *     conversation acts on the *account*, where the audit trail and the
 *     member's notification are.
 *   • **Every transcript read is audited**, including each page of a long one.
 *     Paging is reading, and a trail that records only the first page
 *     understates what was seen.
 *
 * Gated on `chat.moderate` alone, through `ConversationPolicy::moderate` —
 * which is the one ability in that Policy that does not go through `view`,
 * because staff are not participants.
 */
class ChatModerationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->authorize('moderate', Conversation::class);

        $request->validate([
            'user_id' => ['nullable', 'integer'],
            'report_id' => ['nullable', 'integer'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);

        // The anchor is mandatory. 422 rather than an empty list, because an
        // empty list reads as "this member has no conversations" and would send
        // a moderator looking for a bug.
        abort_unless(
            $request->filled('user_id') || $request->filled('report_id'),
            422,
            'Look up conversations by member or by report. There is no browse.',
        );

        $userId = $request->integer('user_id')
            ?: Report::whereKey($request->integer('report_id'))->value('reported_user_id');

        abort_unless($userId, 404, 'Not found.');

        $conversations = Conversation::query()
            ->with([
                'userOne:id,profile_code,name,gender,status',
                'userTwo:id,profile_code,name,gender,status',
            ])
            ->forUser($userId)
            ->orderByDesc('last_message_at')
            ->paginate($request->integer('per_page', 25));

        return ApiResponse::paginated($conversations, AdminConversationResource::class, 'Conversations retrieved successfully');
    }

    /**
     * The transcript.
     *
     * Ordered oldest-first, unlike the member-facing thread which pages
     * backwards from the newest message. A moderator reads an argument from
     * the beginning to work out who started it; loading the last fifty
     * messages of a hundred-message thread shows them the retaliation and
     * hides the provocation.
     *
     * The audit row is written before the query, for the same reason it is in
     * `VerificationManagementController`: logging an access that then failed is
     * a harmless false positive, and the other order logs nothing when the
     * response is lost in flight.
     */
    public function messages(Request $request, Conversation $conversation, AuditLogger $audit): JsonResponse
    {
        $this->authorize('moderate', Conversation::class);

        $request->validate([
            'after' => ['nullable', 'integer'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $audit->logAccess(
            'chat.transcript_viewed',
            $conversation,
            "Read the transcript of conversation #{$conversation->id} between members "
                ."{$conversation->user_one_id} and {$conversation->user_two_id}",
        );

        $limit = $request->integer('limit', 50);

        $messages = Message::query()
            // Deleted messages are part of the evidence — a message removed
            // seconds after it was sent is very often the complaint itself.
            ->withTrashed()
            ->with('sender:id,profile_code,name')
            ->where('conversation_id', $conversation->id)
            ->when($request->integer('after'), fn ($q, $after) => $q->where('id', '>', $after))
            ->orderBy('id')
            ->limit($limit + 1)
            ->get();

        // limit + 1 answers "is there more" without a COUNT over the largest
        // table in the schema.
        $hasMore = $messages->count() > $limit;
        $page = $messages->take($limit);

        return ApiResponse::keyset(
            AdminMessageResource::collection($page)->resolve(),
            $hasMore ? $page->last()?->id : null,
            'Transcript retrieved successfully',
        );
    }
}
