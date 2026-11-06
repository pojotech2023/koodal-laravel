<?php

namespace App\Http\Controllers\Web;

use App\Actions\Interest\AcceptInterest;
use App\Actions\Interest\DeclineInterest;
use App\Actions\Interest\SendInterest;
use App\Http\Controllers\Controller;
use App\Http\Requests\Interest\SendInterestRequest;
use App\Models\Interest;
use App\Models\User;
use App\Models\UserProfile;
use App\Services\Profile\ViewerState;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * `Web\*` sibling of `Api\V1\User\InterestController` — same Actions, same
 * eager-loading, HTML instead of JSON. `store`/`accept`/`decline`/`cancel`
 * are plain `<form method="POST">` targets: every business rule (quota,
 * duplicate detection, auto-match on reciprocal interest) lives in the
 * Action, and `SendInterest`'s `DuplicateInterestException` /
 * `FeatureLimitException` already self-render as a redirect back with a
 * flashed message (PROGRESS.md §6), so the happy path here is all that's
 * left to write.
 */
class InterestController extends Controller
{
    public function __construct(protected ViewerState $viewerState) {}

    /** `/interests` has no tab of its own — received needs a response, so it's the default. */
    public function index(): RedirectResponse
    {
        return redirect()->route('interests.received');
    }

    public function sent(Request $request): View
    {
        $interests = $request->user()->sentInterests()
            ->with($this->counterpartRelations('receiver'))
            ->latest()
            ->paginate(20);

        return $this->render('sent', $interests, 'receiver', $request);
    }

    public function received(Request $request): View
    {
        $interests = $request->user()->receivedInterests()
            ->with($this->counterpartRelations('sender'))
            ->latest()
            ->paginate(20);

        return $this->render('received', $interests, 'sender', $request);
    }

    public function store(SendInterestRequest $request, SendInterest $sendInterest): RedirectResponse
    {
        $interest = $sendInterest(
            sender: $request->user(),
            receiver: User::findOrFail($request->integer('receiver_id')),
            message: $request->input('message'),
        );

        return back()->with('status', $interest->status === 'accepted'
            ? 'You both expressed interest — you are now connected!'
            : 'Interest sent successfully.');
    }

    public function accept(Interest $interest, AcceptInterest $acceptInterest, Request $request): RedirectResponse
    {
        $acceptInterest($interest, $request->user());

        return back()->with('status', 'Interest accepted. You can now start a conversation.');
    }

    public function decline(Interest $interest, DeclineInterest $declineInterest, Request $request): RedirectResponse
    {
        $declineInterest($interest, $request->user());

        return back()->with('status', 'Interest declined.');
    }

    public function cancel(Interest $interest, Request $request): RedirectResponse
    {
        abort_unless($interest->sender_id === $request->user()->id, 403);
        abort_unless($interest->isPending(), 409, 'This interest has already been answered.');

        $interest->update(['status' => 'cancelled']);

        return back()->with('status', 'Interest cancelled.');
    }

    // --- Internals ---------------------------------------------------------

    /** Same relation set as the API controller — see its docblock. */
    protected function counterpartRelations(string $relation): array
    {
        return array_merge(
            [
                $relation.'.profile',
                $relation.'.profile.primaryPhoto',
                $relation.'.profile.user',
                $relation.'.profile.user.privacySettings',
            ],
            array_map(fn (string $lookup) => $relation.'.profile.'.$lookup, UserProfile::WITH_LOOKUPS),
        );
    }

    protected function render(string $tab, LengthAwarePaginator $page, string $relation, Request $request): View
    {
        $profiles = collect($page->items())
            ->map(fn (Interest $row) => $row->{$relation}?->profile)
            ->filter();

        $this->viewerState->hydrate($profiles, $request->user());

        return view('interests.index', [
            'tab' => $tab,
            'interests' => $page,
            'relation' => $relation,
            'viewer' => $request->user(),
        ]);
    }
}
