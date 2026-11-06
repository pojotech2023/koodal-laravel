<?php

namespace App\Http\Controllers\Web;

use App\Actions\Chat\MarkConversationRead;
use App\Actions\Chat\SendMessage;
use App\Actions\Chat\StartConversation;
use App\Http\Controllers\Concerns\ConversationAccess;
use App\Http\Controllers\Controller;
use App\Http\Requests\Chat\SendMessageRequest;
use App\Http\Requests\Chat\StartConversationRequest;
use App\Models\Conversation;
use App\Models\User;
use App\Services\Chat\ConversationParticipantHydrator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * `Web\*` sibling of `Api\V1\User\ConversationController` / `MessageController`
 * — same Actions, same `ConversationParticipantHydrator`
 * (`App\Services\Chat`), one Blade view (`messages/index.blade.php`) doing
 * master-detail duty for both `/messages` (list only) and
 * `/messages/{conversation}` (list + open thread), matching the frozen React
 * `MessagesPage`'s single-page shape.
 *
 * The send form is a plain `<form method="POST">` per docs/05-website-guide.md
 * §7's own rule — `resources/js/chat.js` layers Echo + a 10s poll fallback on
 * top once that works; it is never the only way to send or see a message.
 *
 * Typing indicators are not wired here. `Api\V1\User\MessageController::typing`
 * stays mobile/websocket-only — a page that reloads on every send has no
 * continuous keystroke stream to report, and the guide does not ask for one.
 */
class MessageController extends Controller
{
    use ConversationAccess;

    public function __construct(protected ConversationParticipantHydrator $hydrator) {}

    public function index(Request $request): View
    {
        return $this->render($request, null);
    }

    /**
     * Opening a thread marks it read up to its newest message — the same
     * default `MarkConversationRead` applies when no `last_message_id` is
     * given, and the natural reading of "the page just loaded and showed it
     * to you".
     */
    public function show(Conversation $conversation, Request $request, MarkConversationRead $markRead): View
    {
        $this->assertVisible($conversation, $request->user());

        $markRead($conversation, $request->user());

        return $this->render($request, $conversation->fresh());
    }

    /**
     * The "Message" action on a matched profile card / profile detail page.
     * `StartConversationRequest` resolves `profile_code` the same way
     * `SendInterestRequest`/`ShortlistRequest` do — no numeric id ever
     * appears in the form.
     */
    public function start(StartConversationRequest $request, StartConversation $startConversation): RedirectResponse
    {
        $starter = $request->user();
        $target = User::with(['profile', 'privacySettings'])->findOrFail($request->integer('user_id'));

        if (! $starter->hasPaidPlan()) {
            $gameService = app(\App\Services\KoodalGameService::class);
            if (! $gameService->hasCompletedAllGames($starter, $target)) {
                return back()->with('error', "You can access chat with {$target->name} only after playing all 4 games together. Click 'Games & Vibe' to play!");
            }
        }

        try {
            $conversation = $startConversation($starter, $target);
            return redirect()->route('messages.show', $conversation);
        } catch (\App\Exceptions\ChatNotAllowedException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function store(Conversation $conversation, SendMessageRequest $request, SendMessage $sendMessage): RedirectResponse
    {
        try {
            $sendMessage(
                conversation: $conversation,
                sender: $request->user(),
                payload: $request->safe()->only(['type', 'body', 'client_message_id']),
                attachment: $request->file('attachment'),
            );
        } catch (\App\Exceptions\ChatNotAllowedException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('messages.show', $conversation);
    }

    /** Same one-column mutation as the API controller — see its docblock. No dedicated Action exists. */
    public function archive(Conversation $conversation, Request $request): RedirectResponse
    {
        $this->assertVisible($conversation, $request->user());

        $conversation->update(['status' => 'archived']);

        return redirect()->route('messages.index')->with('status', 'Conversation archived.');
    }

    // --- Internals ---------------------------------------------------------

    protected function render(Request $request, ?Conversation $active): View
    {
        $user = $request->user();

        $conversations = Conversation::query()
            ->forUser($user->id)
            ->where('status', 'active')
            ->orderByRaw('COALESCE(last_message_at, created_at) DESC')
            ->paginate(20);

        $this->hydrator->hydrate(collect($conversations->items()), $user);

        $messages = null;
        $hasOlder = false;
        $canAccessChat = true;
        $gamesCompleted = false;
        $otherUser = null;

        if ($active) {
            $this->hydrator->hydrate(collect([$active]), $user);

            $otherUserId = $active->otherUserId($user->id);
            $otherUser = $active->participant ?? User::find($otherUserId);

            $gameService = app(\App\Services\KoodalGameService::class);
            $gamesCompleted = $gameService->hasCompletedAllGames($user->id, $otherUserId);
            $canAccessChat = $user->hasPaidPlan() || $gamesCompleted;

            // Same keyset pagination as MessageController::index — walking
            // backwards on id, never offset, for the same reason: a thread
            // written to while it's being scrolled makes OFFSET skip rows.
            $limit = config('matrimony.chat.page_size');

            $rows = $active->messages()
                ->before($request->integer('before_id') ?: null)
                ->orderByDesc('id')
                ->limit($limit + 1)
                ->get();

            $hasOlder = $rows->count() > $limit;
            // Oldest-first for the thread; the query above is newest-first
            // because that's what "the last $limit messages" means.
            $messages = $rows->take($limit)->reverse()->values();
        }

        return view('messages.index', [
            'conversations' => $conversations,
            'active' => $active,
            'messages' => $messages,
            'hasOlder' => $hasOlder,
            'viewer' => $user,
            'canAccessChat' => $canAccessChat,
            'gamesCompleted' => $gamesCompleted,
            'otherUser' => $otherUser,
        ]);
    }
}
