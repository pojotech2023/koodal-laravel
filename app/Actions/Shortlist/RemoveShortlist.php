<?php

namespace App\Actions\Shortlist;

use App\Models\Shortlist;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * The other half of ShortlistProfile. It exists as an Action rather than a
 * `delete()` in the controller for one reason: the denormalised counter has to
 * come back down in the same transaction, and a counter that only ever goes up
 * is the classic way these tables drift.
 */
class RemoveShortlist
{
    public function __invoke(User $viewer, User $target): void
    {
        DB::transaction(function () use ($viewer, $target) {
            $deleted = Shortlist::query()
                ->where('user_id', $viewer->id)
                ->where('shortlisted_user_id', $target->id)
                ->delete();

            // Only decrement if a row actually went away — a repeated DELETE
            // must be idempotent, not a slow drain on the counter.
            if ($deleted > 0) {
                $target->profile()->where('shortlisted_count', '>', 0)->decrement('shortlisted_count');
            }
        });
    }
}
