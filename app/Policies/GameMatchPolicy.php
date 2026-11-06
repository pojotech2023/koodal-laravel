<?php

namespace App\Policies;

use App\Models\GameMatch;
use App\Models\User;

/**
 * Who may touch a Spot the Difference match. Registered by name convention
 * (App\Models\GameMatch → App\Policies\GameMatchPolicy); the website's
 * SpotMatchController was already calling `authorize()` against it.
 */
class GameMatchPolicy
{
    public function view(User $user, GameMatch $match): bool
    {
        return $this->participates($user, $match);
    }

    public function claim(User $user, GameMatch $match): bool
    {
        return $this->participates($user, $match);
    }

    /** Either player may call it off, but only before the round starts. */
    public function cancel(User $user, GameMatch $match): bool
    {
        return $this->participates($user, $match) && in_array($match->status, ['waiting', 'ready'], true);
    }

    protected function participates(User $user, GameMatch $match): bool
    {
        return $match->host_id === $user->id
            || $match->players()->where('user_id', $user->id)->exists();
    }
}
