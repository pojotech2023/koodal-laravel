<?php

namespace App\Services\Chat;

use App\Models\Conversation;
use App\Models\User;
use App\Models\UserProfile;
use App\Services\Profile\ViewerState;
use Illuminate\Support\Collection;

/**
 * Fills `participant` and `participant_online` for a whole page of
 * conversations in two queries plus one Redis MGET. Extracted from
 * `Api\V1\User\ConversationController` (its original home) the moment
 * `Web\MessageController` needed the exact same hydration for the same
 * screen rendered as HTML instead of JSON — same reasoning as
 * `UserProfile::displayNameFor()` and `MembershipPlan::featureRows()`
 * (PROGRESS.md §6).
 *
 * `participant` is set as a synthetic relation rather than eager-loaded as
 * `userOne`/`userTwo`, because loading both sides to render one of them
 * fetches twice the profile tree for every row — and the profile tree is
 * eleven lookup relations deep.
 */
class ConversationParticipantHydrator
{
    public function __construct(
        protected PresenceStore $presence,
        protected ViewerState $viewerState,
    ) {}

    /** @param  Collection<int, Conversation>  $conversations */
    public function hydrate(Collection $conversations, User $viewer): void
    {
        if ($conversations->isEmpty()) {
            return;
        }

        $otherIds = $conversations
            ->map(fn (Conversation $conversation) => $conversation->otherUserId($viewer->id))
            ->unique()
            ->values()
            ->all();

        $participants = User::query()
            ->whereIn('id', $otherIds)
            ->with($this->participantRelations())
            ->get()
            ->keyBy('id');

        $online = $this->onlineMap($otherIds);

        foreach ($conversations as $conversation) {
            $otherId = $conversation->otherUserId($viewer->id);
            $participant = $participants->get($otherId);

            $conversation->setRelation('participant', $participant);

            // Left unset when presence is unavailable, so the resource omits the
            // key and the client keeps its last known state. Explicitly false —
            // not absent — for someone who turned online status off: absent
            // means "unknown", and a hidden user must read as offline.
            if ($online !== null) {
                $conversation->participant_online = ($participant?->privacySettings?->show_online_status ?? true)
                    ? ($online[$otherId] ?? false)
                    : false;
            }
        }

        // Same three-query hydration every other list endpoint does, so the
        // heart and the interest button on the chat header render in their
        // final state instead of flickering after mount.
        $this->viewerState->hydrate($participants->pluck('profile')->filter(), $viewer);
    }

    /**
     * Redis is where presence lives, and presence is the one part of chat that
     * is allowed to disappear. docs/10-chat-realtime.md §12: Redis down means
     * no presence and no typing, messaging unaffected.
     *
     * @return array<int, bool>|null
     */
    protected function onlineMap(array $userIds): ?array
    {
        try {
            return $this->presence->onlineMap($userIds);
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Everything ProfileResource reads, eager-loaded here rather than in the
     * resource. `profile.user` and its privacy row matter most: the resource
     * reaches for them on every gated field, and without them
     * preventLazyLoading throws on the first row of the list.
     *
     * @return array<int, string>
     */
    protected function participantRelations(): array
    {
        return array_merge(
            [
                // Read directly by hydrate() for the online-status check,
                // before ProfileResource or a Blade view ever sees the model.
                'privacySettings',
                'profile',
                'profile.primaryPhoto',
                'profile.user',
                'profile.user.privacySettings',
            ],
            array_map(fn (string $lookup) => 'profile.'.$lookup, UserProfile::WITH_LOOKUPS),
        );
    }
}
