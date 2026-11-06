<?php

namespace App\Actions\Chat;

use App\Exceptions\ChatNotAllowedException;
use App\Models\Conversation;
use App\Models\MatchRecord;
use App\Models\User;
use App\Services\Membership\FeatureGate;
use App\Services\Profile\ProfileVisibility;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

/**
 * The consent gate for chat, in the order docs/10-chat-realtime.md §5 sets out.
 *
 * `ProfileVisibility::canMessage()` answers the same question as one boolean —
 * it is what a client asks to decide whether to draw the button. This action
 * deliberately decomposes it instead, because the three ways it can be false
 * need three different answers:
 *
 *   • hidden, blocked or unapproved → 404, never confirming the account exists;
 *   • the plan does not include chat → the upgrade envelope, which every client
 *     already renders as a paywall;
 *   • not connected yet → a 403 that names the thing to do next.
 *
 * Collapsing those into one 403 produces a support ticket instead of an upgrade
 * and leaks the existence of profiles that are hiding.
 *
 * Under the default `chat.requires = mutual_interest` this endpoint is almost
 * always a lookup rather than an insert: AcceptInterest already created the row
 * at the moment of mutual acceptance, so the thread exists before the "interest
 * accepted" push has landed.
 */
class StartConversation
{
    public function __construct(
        protected FeatureGate $features,
        protected ProfileVisibility $visibility,
    ) {}

    public function __invoke(User $starter, User $target): Conversation
    {
        if ($starter->is($target)) {
            throw new \InvalidArgumentException('You cannot start a conversation with yourself.');
        }

        // 404 rather than 403 — never confirm that a profile hiding from this
        // viewer exists. Covers blocks in both directions, suspension, an
        // unapproved profile and the owner's audience setting.
        if (! $this->visibility->canViewProfile($starter, $target)) {
            throw new ModelNotFoundException('Profile not found.');
        }

        $this->features->assertAllowed($starter, 'can_chat');

        // Free users must complete all 4 required games with target before opening chat
        if (! $starter->hasPaidPlan()) {
            $gameService = app(\App\Services\KoodalGameService::class);
            if (! $gameService->hasCompletedAllGames($starter, $target)) {
                if (request()->is('api/*') || request()->expectsJson()) {
                    abort(response()->json([
                        'success' => false,
                        'chat_locked' => true,
                        'games_completed' => false,
                        'message' => 'You can access chat only after playing all the games.',
                    ], 403));
                }

                throw new \App\Exceptions\ChatNotAllowedException(
                    "You can access chat with {$target->name} only after playing all 4 games together. Click 'Games & Vibe' to play!"
                );
            }
        }

        $this->assertConsent($starter, $target);

        [$one, $two] = MatchRecord::pair($starter->id, $target->id);

        return DB::transaction(function () use ($one, $two) {
            $conversation = Conversation::firstOrCreate(
                ['user_one_id' => $one, 'user_two_id' => $two],
                ['status' => 'active'],
            );

            // A blocked thread is never reopened from here. UnblockUser
            // deliberately leaves it closed, and only a fresh match — i.e.
            // AcceptInterest — brings it back. Silently reopening a channel
            // someone shut is the opposite of what they asked for, and they
            // have no way to notice it happened.
            abort_if($conversation->status === 'blocked', 403, 'This conversation is no longer available.');

            // Archiving means "I am done with this for now", so deliberately
            // reaching for the thread again undoes it.
            if ($conversation->status === 'archived') {
                $conversation->update(['status' => 'active']);
            }

            return $conversation;
        });
    }

    /**
     * The receiver's own setting is checked before the configured product rule:
     * "only my connections may message me" outranks any plan the sender bought,
     * and a premium member must not be able to buy past it.
     */
    protected function assertConsent(User $starter, User $target): void
    {
        $connected = $starter->isMatchedWith($target);

        $whoCanMessage = $target->privacySettings?->who_can_message
            ?? config('matrimony.privacy.defaults.who_can_message');

        // ChatNotAllowedException rather than abort(403): both refusals are
        // 403, but neither is fixed by buying a plan, and its `upgradeable`
        // flag is what stops a client showing a paywall sheet for a consent
        // problem. `$upgradeable` stays false here for exactly that reason —
        // the paywall case is FeatureGate's can_chat assertion in __invoke().
        if ($whoCanMessage === 'matches_only' && ! $connected) {
            throw new ChatNotAllowedException(
                'This member only accepts messages from their connections. Send an interest first.',
            );
        }

        // `premium` needs no branch: the can_chat assertion above already is it.
        // `open` needs none either — that is the point of the setting.
        if (config('matrimony.chat.requires') === 'mutual_interest' && ! $connected) {
            throw new ChatNotAllowedException(
                'You can message each other once you have both accepted an interest.',
            );
        }
    }
}
