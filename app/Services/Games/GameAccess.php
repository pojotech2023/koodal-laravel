<?php

namespace App\Services\Games;

use App\Models\BlockedUser;
use App\Models\User;
use App\Services\Profile\ProfileVisibility;
use App\Services\UserPresenceService;

/**
 * The single answer to "may these two members play together", and the single
 * place a member is turned into the small player card every game payload uses.
 *
 * Rules mirror the website (any active member may invite any other) with the
 * two gaps closed: you cannot play yourself, and a block in either direction
 * ends it. A blocked or unknown code is a plain 404 so a block is not
 * distinguishable from a profile that never existed.
 */
class GameAccess
{
    public function __construct(
        protected UserPresenceService $presence,
        protected ProfileVisibility $visibility,
    ) {}

    public function partner(User $viewer, string $profileCode): User
    {
        $partner = User::query()
            ->with(['profile.primaryPhoto'])
            ->where('profile_code', $profileCode)
            ->first();

        return $this->assertPartner($viewer, $partner);
    }

    /** The website addresses partners by user id (`/games/{partner}`); same rules. */
    public function partnerById(User $viewer, int $id): User
    {
        return $this->assertPartner($viewer, User::with(['profile.primaryPhoto'])->find($id));
    }

    protected function assertPartner(User $viewer, ?User $partner): User
    {
        abort_if(
            ! $partner
            || $partner->id === $viewer->id
            || $partner->status !== 'active'
            || $this->blocked($viewer, $partner),
            404,
            'Member not found.',
        );

        return $partner;
    }

    /** For an existing room: the other participant may since have been blocked. */
    public function assertCanPlay(User $viewer, User $other): void
    {
        abort_if(
            $other->status !== 'active' || $this->blocked($viewer, $other),
            403,
            'You can no longer play with this member.',
        );
    }

    public function blocked(User $a, User $b): bool
    {
        return BlockedUser::between($a->id, $b->id)->exists();
    }

    /**
     * @return array{profile_code: string, name: string, avatar: ?string, online: bool, presence: array}
     */
    public function card(User $viewer, User $user): array
    {
        $user->loadMissing('profile.primaryPhoto', 'privacySettings');
        $viewer->loadMissing('privacySettings');

        // displayNameFor() reads $profile->user; hand it the model we already hold.
        $user->profile?->setRelation('user', $user);

        $photo = $user->profile?->primaryPhoto;
        $canSeePhoto = $user->id === $viewer->id || $this->visibility->canViewPhotos($viewer, $user);
        $presence = $this->presence->formatStatus($user);

        return [
            'profile_code' => $user->profile_code,
            'name' => $user->profile?->displayNameFor($viewer) ?? $user->name,
            'avatar' => $canSeePhoto ? GameAssets::url($photo?->urlFor('thumbnail')) : null,
            'presence' => [
                'is_online' => (bool) ($presence['is_online'] ?? false),
                'label' => $presence['label'] ?? 'Offline',
            ],
        ];
    }
}
