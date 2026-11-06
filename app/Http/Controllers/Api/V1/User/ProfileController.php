<?php

namespace App\Http\Controllers\Api\V1\User;

use App\Actions\Profile\DeactivateAccount;
use App\Actions\Profile\RequestAccountDeletion;
use App\Actions\Profile\UpdateProfileSection;
use App\Http\Controllers\Controller;
use App\Http\Requests\Profile\ProfileSectionRequest;
use App\Http\Requests\Profile\UpdateAboutRequest;
use App\Http\Requests\Profile\UpdateBasicRequest;
use App\Http\Requests\Profile\UpdateEducationRequest;
use App\Http\Requests\Profile\UpdateFamilyRequest;
use App\Http\Requests\Profile\UpdateLifestyleRequest;
use App\Http\Requests\Profile\UpdateLocationRequest;
use App\Http\Requests\Profile\UpdateProfessionalRequest;
use App\Http\Requests\Profile\UpdateReligiousRequest;
use App\Http\Resources\OwnProfileResource;
use App\Models\UserProfile;
use App\Services\Profile\ProfileCompletion;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Own profile. Every read here is unfiltered — this is the one endpoint where
 * a user sees all their own fields, so it returns OwnProfileResource rather
 * than the visibility-filtered ProfileResource used for everyone else.
 */
class ProfileController extends Controller
{
    public function __construct(
        protected UpdateProfileSection $updateSection,
        protected ProfileCompletion $completion,
    ) {}

    public function show(Request $request): JsonResponse
    {
        // Eager-loaded here, never inside the Resource. WITH_LOOKUPS is the
        // single list of lookup relations, so this cannot drift from search.
        $user = $request->user()->load(array_merge(
            ['profile', 'preferences', 'privacySettings', 'photos'],
            array_map(fn (string $relation) => 'profile.'.$relation, UserProfile::WITH_LOOKUPS),
        ));

        return ApiResponse::success(new OwnProfileResource($user), 'Profile retrieved successfully');
    }

    public function completion(Request $request): JsonResponse
    {
        return ApiResponse::success(
            $this->completion->report($request->user()),
            'Profile completion retrieved successfully',
        );
    }

    // --- Sectioned updates -------------------------------------------------
    //
    // Eight thin methods rather than one switch: the route table stays
    // readable, and each maps to exactly one FormRequest whose error keys
    // match the fields on the visible form.

    public function updateBasic(UpdateBasicRequest $request): JsonResponse
    {
        return $this->applySection($request);
    }

    public function updateReligious(UpdateReligiousRequest $request): JsonResponse
    {
        return $this->applySection($request);
    }

    public function updateEducation(UpdateEducationRequest $request): JsonResponse
    {
        return $this->applySection($request);
    }

    public function updateProfessional(UpdateProfessionalRequest $request): JsonResponse
    {
        return $this->applySection($request);
    }

    public function updateFamily(UpdateFamilyRequest $request): JsonResponse
    {
        return $this->applySection($request);
    }

    public function updateLifestyle(UpdateLifestyleRequest $request): JsonResponse
    {
        return $this->applySection($request);
    }

    public function updateAbout(UpdateAboutRequest $request): JsonResponse
    {
        return $this->applySection($request);
    }

    public function updateLocation(UpdateLocationRequest $request): JsonResponse
    {
        return $this->applySection($request);
    }

    // --- Lifecycle ---------------------------------------------------------

    public function deactivate(Request $request, DeactivateAccount $deactivate): JsonResponse
    {
        $request->validate(['reason' => ['nullable', 'string', 'max:200']]);

        $deactivate($request->user(), $request->input('reason'));

        return ApiResponse::success(null, 'Your profile is now hidden. Log in again any time to restore it.');
    }

    public function reactivate(Request $request): JsonResponse
    {
        $user = $request->user();

        $user->update(['status' => 'active']);
        $user->privacySettings()->update(['appear_in_search' => true]);

        return ApiResponse::success(null, 'Your profile is visible again');
    }

    public function destroy(Request $request, RequestAccountDeletion $delete): JsonResponse
    {
        $request->validate([
            // Deleting is irreversible after the retention window, so it costs
            // a password rather than a single tap.
            'password' => ['required', 'current_password:sanctum'],
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        $delete($request->user(), $request->input('reason'));

        $days = config('matrimony.retention.soft_deleted_accounts');

        return ApiResponse::success(
            ['purge_after_days' => $days],
            "Your account has been closed. All data will be permanently deleted after {$days} days.",
        );
    }

    /** Data-portability export — the user's own rows, as JSON. */
    public function export(Request $request): JsonResponse
    {
        $user = $request->user()->load(['profile', 'preferences', 'privacySettings', 'photos', 'subscriptions.plan']);

        return ApiResponse::success([
            'exported_at' => now()->toIso8601String(),
            'account' => $user->only(['profile_code', 'name', 'email', 'mobile', 'gender', 'date_of_birth', 'created_at']),
            'profile' => $user->profile?->toArray(),
            'preferences' => $user->preferences?->toArray(),
            'privacy' => $user->privacySettings?->toArray(),
            'photos' => $user->photos->map->only(['id', 'approval_status', 'created_at']),
            'subscriptions' => $user->subscriptions->map->only(['id', 'status', 'starts_at', 'expires_at']),
        ], 'Profile data exported successfully');
    }

    protected function applySection(ProfileSectionRequest $request): JsonResponse
    {
        $profile = ($this->updateSection)(
            $request->user(),
            $request->section(),
            $request->sectionData(),
        );

        return ApiResponse::success([
            'completion_percentage' => $profile->completion_percentage,
            'approval_status' => $profile->approval_status,
        ], ucfirst($request->section()).' details saved successfully');
    }
}
