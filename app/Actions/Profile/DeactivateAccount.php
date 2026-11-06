<?php

namespace App\Actions\Profile;

use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * A reversible hide, not a deletion.
 *
 * Data is left intact — the account is simply excluded from every discovery
 * path. People deactivate because they met someone, and a meaningful share
 * come back; destroying their profile on the way out means they start from
 * zero when they do.
 */
class DeactivateAccount
{
    public function __invoke(User $user, ?string $reason = null): User
    {
        return DB::transaction(function () use ($user) {
            $user->update(['status' => 'deactivated']);

            // Belt and braces: status alone already excludes them from search,
            // but a stale privacy row must not resurrect them if that check
            // ever changes.
            $user->privacySettings()->update(['appear_in_search' => false]);

            // Pending interests are withdrawn. Leaving them live means someone
            // accepts an interest from an account that cannot answer.
            $user->sentInterests()->where('status', 'pending')->update(['status' => 'cancelled']);

            // Sessions end everywhere, so the app on their phone does not keep
            // showing a logged-in state for a hidden account.
            $user->tokens()->delete();

            return $user->refresh();
        });
    }
}
