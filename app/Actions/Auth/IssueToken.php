<?php

namespace App\Actions\Auth;

use App\Models\User;
use Laravel\Sanctum\NewAccessToken;

/**
 * Token minting, in one place so the ability set cannot drift.
 *
 * Members receive `user`, staff receive `admin`. That separation is what stops
 * a stolen member token from reaching an admin route even if someone guesses
 * the URL — route middleware checks the ability, not the role, so a member
 * token is inert against /admin regardless of what the role table says later.
 */
class IssueToken
{
    public function __invoke(User $user, string $deviceName, ?array $abilities = null): NewAccessToken
    {
        $abilities ??= $user->isStaff() ? ['admin'] : ['user'];

        $user->forceFill(['last_login_at' => now(), 'last_active_at' => now()])->saveQuietly();

        return $user->createToken($deviceName, $abilities);
    }
}
