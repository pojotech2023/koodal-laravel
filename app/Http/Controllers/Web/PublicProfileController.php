<?php

namespace App\Http\Controllers\Web;

use App\Actions\Profile\RecordProfileView;
use App\Actions\Profile\RevealContact;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserProfile;
use App\Services\Games\CompatibilityReports;
use App\Services\Profile\ProfileVisibility;
use App\Services\Profile\ViewerState;
use App\Support\ProfileSlug;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Someone else's profile, at `/profile/{slug}` — the `Web\*` sibling of
 * `Api\V1\User\PublicProfileController`. Same `owner()` chokepoint, same
 * 404-not-403 rule (PROGRESS.md §6 / ProfileVisibility docblock): a blocked or
 * hidden profile must be indistinguishable from one that never existed.
 *
 * No `ProfileResource` — this is a Blade view working with the Eloquent model
 * directly (docs/05-website-guide.md §4). `canSeePhotos`/`canSeeContact` are
 * the same two gates the Resource asks; only who's asking changed.
 */
class PublicProfileController extends Controller
{
    public function __construct(
        protected ProfileVisibility $visibility,
        protected ViewerState $viewerState,
    ) {}

    public function show(string $slug, Request $request, RecordProfileView $recordView): View
    {
        $viewer = $request->user();
        $owner = $this->owner($slug, $viewer);

        $recordView($viewer, $owner, 'direct');

        $profile = $owner->profile->load(array_merge(
            UserProfile::WITH_LOOKUPS,
            ['photos', 'primaryPhoto', 'user', 'user.privacySettings'],
        ));

        $this->viewerState->hydrateOne($profile, $viewer);

        // The two report buttons' percentages; not on your own profile.
        $reports = app(CompatibilityReports::class);
        $other = $viewer && $viewer->id !== $owner->id;
        $rasiPorutham = $other ? $reports->rasi($viewer, $owner) : null;
        $preferenceMatch = $other ? $reports->preference($viewer, $owner) : null;

        $contactApproval = $other
            ? \App\Models\ContactApproval::where('requester_id', $viewer->id)->where('owner_id', $owner->id)->first()
            : null;

        return view('profile.public', [
            'owner' => $owner,
            'profile' => $profile,
            'viewer' => $viewer,
            'canSeePhotos' => $this->visibility->canViewPhotos($viewer, $owner),
            'canSeeContact' => $this->visibility->canViewContact($viewer, $owner),
            'contact' => session('contact'),
            'contactApproval' => $contactApproval,
            'rasiPorutham' => $rasiPorutham,
            'preferenceMatch' => $preferenceMatch,
        ]);
    }

    /**
     * The paywall. Renders the same page again with the mobile/email flashed
     * in session — a POST that redirects back rather than a page of its own,
     * same plain-form-first rule as everything else this session
     * (docs/05-website-guide.md §7). `FeatureLimitException` (no quota left)
     * self-renders as a redirect back with the upgrade message; nothing here
     * needs to catch it.
     */
    public function contact(string $slug, Request $request, RevealContact $revealContact): RedirectResponse
    {
        $viewer = $request->user();
        $owner = $this->owner($slug, $viewer);

        if (! $viewer->hasPaidPlan() && $viewer->id !== $owner->id) {
            $approval = \App\Models\ContactApproval::where('requester_id', $viewer->id)
                ->where('owner_id', $owner->id)
                ->first();

            if (! $approval || $approval->status !== 'approved') {
                return redirect()->route('profile.public', $slug)->with('error', 'Contact details can only be viewed after the member approves your request.');
            }
        }

        try {
            $contact = $revealContact($viewer, $owner);
            return redirect()->route('profile.public', $slug)->with('contact', $contact);
        } catch (\Throwable $e) {
            return redirect()->route('profile.public', $slug)->with('error', $e->getMessage());
        }
    }

    /**
     * Request contact detail visibility from the profile owner.
     */
    public function requestContact(string $slug, Request $request, \App\Services\FirebaseNotificationService $fcm): RedirectResponse
    {
        $viewer = $request->user();
        $owner = $this->owner($slug, $viewer);

        if ($viewer->id === $owner->id) {
            return redirect()->route('profile.public', $slug)->with('error', 'You cannot request contact access for your own profile.');
        }

        $approval = \App\Models\ContactApproval::firstOrCreate(
            ['requester_id' => $viewer->id, 'owner_id' => $owner->id],
            ['status' => 'pending']
        );

        if ($approval->status !== 'approved' && $approval->status !== 'pending') {
            $approval->update(['status' => 'pending']);
        }

        try {
            $fcm->sendToUser(
                $owner->id,
                'Contact Request',
                "{$viewer->name} has requested access to view your contact details.",
                [
                    'type' => 'contact_request',
                    'reference_id' => (string) $approval->id,
                    'requester_id' => (string) $viewer->id,
                    'requester_code' => (string) $viewer->profile_code,
                ],
                'contact_request'
            );
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Web contact request push failed: '.$e->getMessage());
        }

        return redirect()->route('profile.public', $slug)->with('success', 'Contact request sent successfully! You will be notified when '.$owner->name.' approves.');
    }

    /**
     * Dedicated Rasi Porutham Details Page (10 Porutham Astrology report)
     */
    public function rasiPorutham(string $slug, Request $request, CompatibilityReports $reports): View
    {
        $viewer = $request->user();

        return view('profile.rasi', $reports->pageData($viewer, $this->reportOwner($slug, $viewer)));
    }

    /**
     * Dedicated Partner Preference Match Details Page
     */
    public function preference(string $slug, Request $request, CompatibilityReports $reports): View
    {
        $viewer = $request->user();

        return view('profile.preference', $reports->pageData($viewer, $this->reportOwner($slug, $viewer)));
    }

    /**
     * Resolve and authorise in one step. `firstOrFail` and the visibility
     * check produce the same 404, so a profile code that does not exist and
     * one that is hidden from this viewer are indistinguishable from outside.
     */
    /** A report compares you with someone else; there is none of you with yourself. */
    protected function reportOwner(string $slug, User $viewer): User
    {
        $owner = $this->owner($slug, $viewer);

        abort_if($owner->id === $viewer->id, 404);

        return $owner;
    }

    protected function owner(string $slug, User $viewer): User
    {
        $code = ProfileSlug::codeFrom($slug);

        abort_if($code === null, 404);

        $owner = User::query()
            ->where('profile_code', $code)
            ->with(['profile', 'privacySettings', 'approvedJadhagams', 'jadhagams'])
            ->firstOrFail();

        abort_unless($this->visibility->canViewProfile($viewer, $owner), 404);

        return $owner;
    }
}
