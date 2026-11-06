<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Str;

/**
 * The cosmetic `/profile/priya-r-mat2026004417` URL the website guide
 * promises (docs/05-website-guide.md §6) — a human-readable name prefix with
 * the real lookup key, `profile_code`, appended verbatim in lowercase.
 *
 * The slug is never stored or trusted as an identifier. `codeFrom()` only
 * pulls the trailing `profile_code` back out; the name prefix is decorative
 * and never consulted, so a stale or hand-edited name segment (someone
 * bookmarked a profile before a rename) still resolves correctly.
 */
class ProfileSlug
{
    public static function for(User $user): string
    {
        return Str::slug($user->name).'-'.strtolower((string) $user->profile_code);
    }

    /** Null when the slug carries no recognisable profile_code — an obvious 404, not a lookup. */
    public static function codeFrom(string $slug): ?string
    {
        if (! preg_match('/('.ProfileCode::PREFIX.'\d{10})$/i', $slug, $matches)) {
            return null;
        }

        return strtoupper($matches[1]);
    }
}
