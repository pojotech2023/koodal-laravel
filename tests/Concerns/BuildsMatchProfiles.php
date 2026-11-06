<?php

namespace Tests\Concerns;

use App\Models\User;
use App\Models\UserPreference;
use App\Models\UserProfile;

/**
 * Every `Criteria/*` class reads only `$profile->user->preferences` and plain
 * attributes off two `UserProfile` instances — never a database query on the
 * profile/user/preference rows themselves. Building them in memory, with
 * relations wired via `setRelation()` rather than persisted, keeps these
 * tests exact and fast; `preventLazyLoading` is on in testing (see
 * PROGRESS.md §6), so a relation that is never explicitly set — even to
 * `null` — throws instead of silently querying.
 */
trait BuildsMatchProfiles
{
    protected function matchProfile(array $profileAttrs = [], array $userAttrs = []): UserProfile
    {
        $user = new User($userAttrs);
        $user->setRelation('preferences', null);

        $profile = new UserProfile($profileAttrs);
        $profile->setRelation('user', $user);

        return $profile;
    }

    protected function withPreferences(UserProfile $profile, array $preferenceAttrs): UserProfile
    {
        $profile->user->setRelation('preferences', new UserPreference($preferenceAttrs));

        return $profile;
    }
}
