<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\Report;
use App\Models\User;
use App\Services\AuditLogger;
use App\Support\ProfileCode;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * The `Web\Admin` sibling of {@see \App\Http\Controllers\Api\V1\Admin\
 * ChatModerationController}. Reading other people's private messages — the
 * single largest privacy trade-off in the product — so every restriction the
 * API sibling documents is reproduced here rather than softened for a UI:
 *
 *   • **No enumeration.** The index refuses to list anything without a member
 *     or a report to anchor it. A browse of everybody's threads is a different
 *     feature and nobody asked for it. The empty state says so explicitly
 *     instead of showing a blank table, which would read as "no conversations".
 *   • **No content in the listing.** `last_message_preview` is on the row and
 *     is deliberately not rendered.
 *   • **No cross-conversation search**, no export, no write. A moderator who
 *     needs to stop a conversation acts on the *account*, where the audit trail
 *     and the member's notification are.
 *   • **Every transcript read is audited, including each page.** Paging is
 *     reading, and a trail that records only the first page understates what
 *     was seen.
 *
 * Gated on `chat.moderate` through `ConversationPolicy::moderate` — the one
 * ability in that Policy that does not route through `view`, because staff are
 * not participants.
 *
 * There are two methods here and there should stay two. Deleting a message
 * from this surface would destroy the evidence the report is about, and
 * silencing a conversation is an action on the *account* —
 * `Web\Admin\UserController`, where it is audited and the member is told.
 */
class ChatModerationController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('moderate', Conversation::class);

        $request->validate([
            'q' => ['nullable', 'string', 'max:120'],
            'report_id' => ['nullable', 'integer'],
        ]);

        $member = $this->resolveMember($request);

        return view('admin.chat.index', [
            'member' => $member,

            // No anchor, no query at all — not an empty result set. See the
            // class docblock: an empty table is a lie a moderator would chase.
            'conversations' => $member
                ? Conversation::query()
                    ->with([
                        'userOne:id,profile_code,name,gender,status',
                        'userTwo:id,profile_code,name,gender,status',
                    ])
                    ->forUser($member->id)
                    ->orderByDesc('last_message_at')
                    ->paginate(25)
                    ->withQueryString()
                : null,
        ]);
    }

    /**
     * The transcript — oldest first, unlike the member-facing thread which
     * pages backwards from the newest message. A moderator reads an argument
     * from the beginning to work out who started it; the last fifty messages
     * of a hundred-message thread show the retaliation and hide the
     * provocation.
     *
     * The audit row is written before the query, same order and same reason as
     * `VerificationController`: a logged access that then failed is a harmless
     * false positive; the reverse loses the trail when the response does.
     */
    public function show(Request $request, Conversation $conversation, AuditLogger $audit): View
    {
        $this->authorize('moderate', Conversation::class);

        $request->validate(['after' => ['nullable', 'integer']]);

        $conversation->load([
            'userOne:id,profile_code,name,gender,status',
            'userTwo:id,profile_code,name,gender,status',
        ]);

        $after = $request->integer('after');

        $audit->logAccess(
            'chat.transcript_viewed',
            $conversation,
            "Read the transcript of conversation #{$conversation->id} between members "
                ."{$conversation->user_one_id} and {$conversation->user_two_id}"
                .($after ? " (from message #{$after})" : ''),
        );

        $limit = 50;

        $messages = Message::query()
            // Deleted messages are evidence — one removed seconds after it was
            // sent is very often the complaint itself.
            ->withTrashed()
            ->with('sender:id,profile_code,name')
            ->where('conversation_id', $conversation->id)
            ->when($after, fn ($q, $id) => $q->where('id', '>', $id))
            ->orderBy('id')

            // limit + 1 answers "is there more" without a COUNT over the
            // largest table in the schema.
            ->limit($limit + 1)
            ->get();

        $hasMore = $messages->count() > $limit;
        $page = $messages->take($limit);

        return view('admin.chat.show', [
            'conversation' => $conversation,
            'messages' => $page,
            'nextCursor' => $hasMore ? $page->last()?->id : null,
        ]);
    }

    // --- Internals ---------------------------------------------------------

    /**
     * The anchor: a member typed in directly, or the accused account on a
     * report. Investigating a complaint is what this surface is for, so
     * arriving from a report — `?report_id=…`, which the report page links —
     * must not require re-typing the member's code.
     */
    protected function resolveMember(Request $request): ?User
    {
        if ($id = $request->integer('report_id')) {
            $userId = Report::whereKey($id)->value('reported_user_id');

            return $userId ? User::query()->find($userId, ['id', 'profile_code', 'name', 'status']) : null;
        }

        if (! $term = trim($request->string('q')->toString())) {
            return null;
        }

        return User::query()
            ->when(
                str_starts_with(strtoupper($term), ProfileCode::PREFIX),
                fn ($q) => $q->where('profile_code', strtoupper($term)),
                fn ($q) => $q->where('email', strtolower($term)),
            )
            ->first(['id', 'profile_code', 'name', 'status']);
    }
}
