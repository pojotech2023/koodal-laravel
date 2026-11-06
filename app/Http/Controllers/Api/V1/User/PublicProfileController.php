<?php

namespace App\Http\Controllers\Api\V1\User;

use App\Actions\Profile\RecordProfileView;
use App\Actions\Profile\RevealContact;
use App\Http\Controllers\Controller;
use App\Http\Resources\PhotoResource;
use App\Http\Resources\ProfileResource;
use App\Models\User;
use App\Models\UserProfile;
use App\Services\Games\CompatibilityReports;
use App\Services\Profile\ProfileVisibility;
use App\Services\Profile\ViewerState;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Someone else's profile.
 *
 * Every method here resolves the owner through one place — `owner()` — which
 * applies ProfileVisibility and aborts 404 when the answer is no. Not 403: a
 * 403 confirms the account exists and tells a blocked user they were blocked,
 * which turns a safety feature into a notification.
 */
class PublicProfileController extends Controller
{
    public function __construct(
        protected ProfileVisibility $visibility,
        protected ViewerState $viewerState,
    ) {}

    /**
     * Records the view before returning. The quota lives in the action, so an
     * exhausted free plan surfaces here as the standard upgrade envelope rather
     * than a profile the user was not entitled to see.
     */
    public function show(string $profileCode, Request $request, RecordProfileView $recordView): JsonResponse
    {
        $viewer = $request->user();
        $owner = $this->owner($profileCode, $viewer);

        $recordView($viewer, $owner, $request->string('source', 'direct')->toString());

        // `user` and its privacy row are loaded explicitly: ProfileResource
        // reads both for every field it gates, and a lazy load there throws
        // outside production. Approved Jadhagams only exist on this full view.
        $profile = $owner->profile->load(array_merge(
            UserProfile::WITH_LOOKUPS,
            ['photos', 'primaryPhoto', 'user', 'user.privacySettings', 'user.approvedJadhagams'],
        ));

        $this->viewerState->hydrateOne($profile, $viewer);

        return ApiResponse::success(new ProfileResource($profile), 'Profile retrieved successfully');
    }

    /**
     * A gated viewer gets the URLs of the blurred derivatives — different
     * files, not the real ones with a flag attached. A flag is a suggestion.
     */
    public function photos(string $profileCode, Request $request): JsonResponse
    {
        $viewer = $request->user();
        $owner = $this->owner($profileCode, $viewer);

        $blurred = ! $this->visibility->canViewPhotos($viewer, $owner);

        $photos = $owner->photos()->approved()->get()
            ->map(fn ($photo) => (new PhotoResource($photo))->additional(['blurred' => $blurred])->resolve())
            ->values();

        return ApiResponse::success([
            'photos' => $photos,
            'blurred' => $blurred,
        ], 'Photos retrieved successfully');
    }

    /** The paywall. Quota, privacy and metering are all in RevealContact. */
    public function contact(string $profileCode, Request $request, RevealContact $revealContact): JsonResponse
    {
        $viewer = $request->user();
        $owner = $this->owner($profileCode, $viewer);

        return ApiResponse::success($revealContact($viewer, $owner), 'Contact details retrieved successfully');
    }

    /**
     * Resolve and authorise in one step.
     *
     * `firstOrFail` and the visibility check produce the same 404 body, so a
     * profile code that does not exist and one that is hidden from this viewer
     * are indistinguishable from outside.
     */
    /**
     * Rasi Porutham and Partner Preference Match with this member — the
     * website's two report pages and the percentages on its profile buttons.
     */
    public function compatibility(string $profileCode, Request $request, CompatibilityReports $reports): JsonResponse
    {
        $viewer = $request->user();

        return ApiResponse::success(
            $reports->api($viewer, $this->owner($profileCode, $viewer)),
            'Compatibility retrieved successfully',
        );
    }

    protected function owner(string $profileCode, User $viewer): User
    {
        $owner = User::query()
            ->where('profile_code', $profileCode)
            ->with(['profile', 'privacySettings'])
            ->firstOrFail();

        abort_unless($this->visibility->canViewProfile($viewer, $owner), 404);

        return $owner;
    }
}
