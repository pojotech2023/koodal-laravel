<?php

namespace App\Http\Controllers\Web\Admin;

use App\Actions\Moderation\ModerateProfile;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\RejectProfileRequest;
use App\Models\UserProfile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * The `Web\Admin` sibling of {@see \App\Http\Controllers\Api\V1\Admin\
 * ProfileModerationController} — same queue query, same oldest-first order,
 * same `UserProfilePolicy::moderate` ability on every method, same
 * `ModerateProfile` Action (which writes its own audit row and sends the
 * member's notification, so this controller writes neither).
 *
 * Two things the API sibling has no reason to do, both because a moderator
 * works this screen with a keyboard for a whole shift rather than one call at
 * a time:
 *
 * 1. A decision redirects to the **next pending profile**, not back to the
 *    list. The queue is the workflow; returning to a table the moderator then
 *    has to re-scan costs a click and a re-read per profile.
 * 2. An already-reviewed profile is caught here and flashed, rather than
 *    left to `ModerateProfile`'s 409. Two moderators on the same queue is
 *    the expected case, not the exceptional one (the API's bulk endpoint
 *    makes the same allowance by reporting skips instead of failing), and a
 *    409 error page mid-shift loses the moderator their place. The Action's
 *    guard stays exactly where it is — it is the real invariant; this is a
 *    friendlier path to the same answer.
 */
class ProfileModerationController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('moderate', UserProfile::class);

        $request->validate([
            'status' => ['nullable', 'in:pending,approved,rejected'],
            'gender' => ['nullable', 'in:male,female'],
            'q' => ['nullable', 'string', 'max:120'],
        ]);

        $status = $request->string('status')->toString() ?: 'pending';

        $profiles = UserProfile::query()
            ->where('approval_status', $status)
            ->with([
                'user:id,profile_code,name,gender,date_of_birth,status,registration_source,created_at',
                'city:id,name',
                'state:id,name',
            ])
            // Aliased, because `UserProfile::photos()` is approved-only — a
            // bare `photos_count` on a moderation queue would read as "photos
            // uploaded" and mean "photos already cleared", which is the
            // opposite of what a moderator would take from it.
            ->withCount('photos as approved_photos_count')
            ->when($request->filled('gender'), fn ($q) => $q->whereHas(
                'user',
                fn ($u) => $u->where('gender', $request->input('gender')),
            ))
            ->when($request->filled('q'), fn ($q) => $q->whereHas(
                'user',
                fn ($u) => $u->where('name', 'like', trim($request->string('q')->toString()).'%')
                    ->orWhere('profile_code', strtoupper(trim($request->string('q')->toString()))),
            ))

            // Oldest first, always — same reasoning as the API sibling: newest
            // first starves the tail, and the profile that waited a week is
            // the one invisible in search for a week.
            ->orderBy('created_at')
            ->paginate(25)
            ->withQueryString();

        return view('admin.profiles.index', [
            'profiles' => $profiles,
            'status' => $status,
            'pendingCount' => UserProfile::query()->where('approval_status', 'pending')->count(),
        ]);
    }

    public function show(UserProfile $profile): View
    {
        $this->authorize('moderate', UserProfile::class);

        $profile->load([
            'user:id,profile_code,name,email,mobile,gender,date_of_birth,status,registration_source,created_at',

            // All of them, not `$profile->photos` — that relation is
            // approved-only (it feeds member-facing payloads), and a
            // moderator reviewing a profile needs to see the pending and
            // rejected ones too.
            'user.photos',
            'user.jadhagams',
            ...UserProfile::WITH_LOOKUPS,
        ]);

        return view('admin.profiles.show', [
            'profile' => $profile,
            'reasons' => config('matrimony.reports.rejection_reasons.profile', []),
            'remaining' => UserProfile::query()->where('approval_status', 'pending')->count(),
        ]);
    }

    public function approve(Request $request, UserProfile $profile, ModerateProfile $moderate): RedirectResponse
    {
        $this->authorize('moderate', UserProfile::class);

        if (! $profile->isPendingApproval()) {
            return $this->advance($profile, 'That profile had already been reviewed by someone else.');
        }

        $moderate($profile, 'approve', $request->user());

        return $this->advance($profile, 'Profile approved and the member has been told.');
    }

    public function reject(RejectProfileRequest $request, UserProfile $profile, ModerateProfile $moderate): RedirectResponse
    {
        $this->authorize('moderate', UserProfile::class);

        if (! $profile->isPendingApproval()) {
            return $this->advance($profile, 'That profile had already been reviewed by someone else.');
        }

        $moderate($profile, 'reject', $request->user(), $request->reason());

        return $this->advance($profile, 'Profile rejected and the member has been told why.');
    }

    // --- Internals ---------------------------------------------------------

    /**
     * Straight to the next profile in the queue, so a shift is a sequence of
     * decisions rather than a sequence of round trips through the list.
     *
     * "Next" is the oldest pending row other than the one just decided —
     * ordering by `created_at` the same way `index()` does, so the moderator
     * walks the queue in exactly the order the table showed it. When the queue
     * empties, the list is the right place to land: it is where the status
     * filter lives.
     */
    protected function advance(UserProfile $decided, string $message): RedirectResponse
    {
        $next = UserProfile::query()
            ->where('approval_status', 'pending')
            ->whereKeyNot($decided->getKey())
            ->orderBy('created_at')
            ->first();

        $target = $next
            ? redirect()->route('admin.profiles.show', $next)
            : redirect()->route('admin.profiles.index');

        return $target->with('status', $message.($next ? '' : ' The queue is empty.'));
    }
}
