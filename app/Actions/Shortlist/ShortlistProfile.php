<?php

namespace App\Actions\Shortlist;

use App\Events\ProfileShortlisted;
use App\Models\Shortlist;
use App\Models\User;
use App\Services\Profile\ProfileVisibility;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

/**
 * Adds a profile to the viewer's shortlist.
 *
 * Ungated on purpose. Shortlisting is the cheapest signal a user can give and
 * the one the matching pass learns most from — metering it would cost more in
 * signal than it could earn in upgrades. Seeing *who shortlisted you* is the
 * paid half.
 */
class ShortlistProfile
{
    public function __construct(protected ProfileVisibility $visibility) {}

    public function __invoke(User $viewer, User $target, ?string $note = null): Shortlist
    {
        if ($viewer->is($target)) {
            throw new \InvalidArgumentException('You cannot shortlist your own profile.');
        }

        // Re-checked server-side: the client only knows what it was shown, and
        // what it was shown may be minutes stale. 404, never 403.
        if (! $this->visibility->canViewProfile($viewer, $target)) {
            throw new ModelNotFoundException('Profile not found.');
        }

        try {
            $shortlist = DB::transaction(function () use ($viewer, $target, $note) {
                $shortlist = Shortlist::create([
                    'user_id' => $viewer->id,
                    'shortlisted_user_id' => $target->id,
                    'note' => $note,
                ]);

                // Denormalised: the count is read on every profile card and
                // must never become a COUNT(*) per row.
                $target->profile()->increment('shortlisted_count');

                return $shortlist;
            });
        } catch (QueryException $e) {
            // 1062 = duplicate key. The unique index caught a double-tap, which
            // on mobile is not a rare event.
            if ($e->errorInfo[1] !== 1062) {
                throw $e;
            }

            abort(409, 'This profile is already in your shortlist.');
        }

        event(new ProfileShortlisted($shortlist));

        return $shortlist;
    }
}
