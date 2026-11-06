<?php

namespace App\Services\Profile;

use App\Models\User;
use App\Services\Membership\FeatureGate;

/**
 * The core privacy mechanism. Every endpoint that returns another user's data
 * runs through here.
 *
 * Five inputs — the viewer's plan, the owner's privacy settings, the block
 * relationship in both directions, the connection state, and the owner's
 * approval status — collapse into four answers.
 *
 * The most important rule is the first one: a blocked viewer gets "does not
 * exist", never "forbidden". A 403 confirms the account is real and tells a
 * blocked user they were blocked, which turns a safety feature into a
 * notification.
 */
class ProfileVisibility
{
    /**
     * Per-request memo. canViewProfile is the entry point for all four answers,
     * so rendering one profile card asks it up to three times, and each ask
     * costs two block lookups. Bound as a singleton in AppServiceProvider so a
     * page of twenty profiles reuses the same instance.
     *
     * @var array<string, bool>
     */
    protected array $decisions = [];

    public function __construct(protected FeatureGate $features) {}

    public function canViewProfile(?User $viewer, User $owner): bool
    {
        $key = ($viewer?->id ?? 'guest').':'.$owner->id;

        return $this->decisions[$key] ??= $this->resolveCanViewProfile($viewer, $owner);
    }

    protected function resolveCanViewProfile(?User $viewer, User $owner): bool
    {
        if (! $this->isDiscoverable($owner)) {
            return false;
        }

        if ($viewer && $this->blockedEitherWay($viewer, $owner)) {
            return false;
        }

        return $this->satisfiesAudience(
            $viewer,
            $owner,
            $owner->privacySettings?->profile_visibility ?? 'members',
        );
    }

    public function canViewPhotos(?User $viewer, User $owner): bool
    {
        if (! $this->canViewProfile($viewer, $owner)) {
            return false;
        }

        $setting = $owner->privacySettings?->photo_visibility ?? 'members';

        // `on_request` means photos are visible only to a confirmed connection;
        // everyone else gets the server-generated blurred derivative.
        if ($setting === 'on_request') {
            return $viewer !== null && $this->isConnected($viewer, $owner);
        }

        return $this->satisfiesAudience($viewer, $owner, $setting);
    }

    /**
     * Contact access needs three things, not one: the owner must permit the
     * audience, the viewer's plan must grant the capability, and the viewer
     * must have quota left this month.
     */
    public function canViewContact(?User $viewer, User $owner): bool
    {
        if (! $viewer || ! $this->canViewProfile($viewer, $owner)) {
            return false;
        }

        if (! $this->satisfiesAudience($viewer, $owner, $owner->privacySettings?->contact_visibility ?? 'premium')) {
            return false;
        }

        return $this->features->allows($viewer, 'can_view_contact')
            && $this->features->remaining($viewer, 'contact_views_per_month') > 0;
    }

    public function canMessage(User $viewer, User $owner): bool
    {
        if (! $this->canViewProfile($viewer, $owner)) {
            return false;
        }

        if (! $this->features->allows($viewer, 'can_chat')) {
            return false;
        }

        $who = $owner->privacySettings?->who_can_message ?? 'matches_only';

        if ($who === 'matches_only' && ! $this->isConnected($viewer, $owner)) {
            return false;
        }

        return match (config('matrimony.chat.requires')) {
            'mutual_interest' => $this->isConnected($viewer, $owner),
            'premium' => $this->features->allows($viewer, 'can_chat'),
            default => true,
        };
    }

    /** Whether the profile exists as far as the rest of the system is concerned. */
    protected function isDiscoverable(User $owner): bool
    {
        return $owner->status === 'active'
            && $owner->profile?->approval_status === 'approved'
            && ($owner->privacySettings?->appear_in_search ?? true);
    }

    protected function blockedEitherWay(User $a, User $b): bool
    {
        return $a->hasBlocked($b) || $b->hasBlocked($a);
    }

    protected function isConnected(User $a, User $b): bool
    {
        return $a->isMatchedWith($b);
    }

    protected function satisfiesAudience(?User $viewer, User $owner, string $audience): bool
    {
        return match ($audience) {
            'all' => true,
            'members' => $viewer !== null,
            'premium' => $viewer !== null && $viewer->hasPaidPlan(),
            'matches_only' => $viewer !== null && $this->isConnected($viewer, $owner),
            default => false,
        };
    }
}
