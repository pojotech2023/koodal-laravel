<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Actions\Moderation\ModerateProfile;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\RejectProfileRequest;
use App\Http\Resources\Admin\AdminProfileResource;
use App\Models\UserProfile;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProfileModerationController extends Controller
{
    /**
     * Oldest first, always.
     *
     * Newest-first is the default nobody thinks about and it is wrong here: it
     * starves the bottom of the queue, so on a busy day the profiles that wait
     * longest are the ones that wait forever. A member whose profile sat for a
     * week is invisible in search for a week.
     */
    public function pending(Request $request): JsonResponse
    {
        $this->authorize('moderate', UserProfile::class);

        $request->validate([
            'status' => ['nullable', 'in:pending,approved,rejected'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $profiles = UserProfile::query()
            ->where('approval_status', $request->string('status')->toString() ?: 'pending')
            ->with([
                'user:id,profile_code,name,email,mobile,gender,date_of_birth,status,registration_source,created_at',
                'user.photos',
                ...UserProfile::WITH_LOOKUPS,
            ])
            ->orderBy('created_at')
            ->paginate($request->integer('per_page', 20));

        return ApiResponse::paginated($profiles, AdminProfileResource::class, 'Moderation queue retrieved successfully');
    }

    public function approve(Request $request, UserProfile $profile, ModerateProfile $moderate): JsonResponse
    {
        $this->authorize('moderate', UserProfile::class);

        $profile = $moderate($profile, 'approve', $request->user());

        return ApiResponse::success(
            new AdminProfileResource($profile->load('user')),
            'Profile approved',
        );
    }

    public function reject(RejectProfileRequest $request, UserProfile $profile, ModerateProfile $moderate): JsonResponse
    {
        $this->authorize('moderate', UserProfile::class);

        $profile = $moderate($profile, 'reject', $request->user(), $request->reason());

        return ApiResponse::success(
            new AdminProfileResource($profile->load('user')),
            'Profile rejected and the member has been told why',
        );
    }
}
