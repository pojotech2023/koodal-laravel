<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Actions\Moderation\ModeratePhoto;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\BulkPhotoModerationRequest;
use App\Http\Requests\Admin\RejectPhotoRequest;
use App\Http\Resources\Admin\AdminPhotoResource;
use App\Models\ProfilePhoto;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * The highest-volume screen in the product. A moderator works it from the
 * keyboard — approve, reject, next — for a whole shift, so the two things this
 * controller owes them are a queue that never starves and a batch endpoint
 * that does not quietly skip the per-photo rules.
 */
class PhotoModerationController extends Controller
{
    public function pending(Request $request): JsonResponse
    {
        $this->authorize('moderate', ProfilePhoto::class);

        $request->validate([
            'status' => ['nullable', 'in:pending,approved,rejected'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $photos = ProfilePhoto::query()
            ->where('approval_status', $request->string('status')->toString() ?: 'pending')
            ->with(['user:id,profile_code,name,gender', 'moderator:id,name'])

            // Oldest first: newest-first starves the tail of the queue, and a
            // photo that waits is a profile rendering as a blank avatar.
            ->orderBy('created_at')
            ->paginate($request->integer('per_page', 40));

        return ApiResponse::paginated($photos, AdminPhotoResource::class, 'Photo queue retrieved successfully');
    }

    public function approve(Request $request, ProfilePhoto $photo, ModeratePhoto $moderate): JsonResponse
    {
        $this->authorize('moderate', ProfilePhoto::class);

        $photo = $moderate($photo, 'approve', $request->user());

        return ApiResponse::success(
            new AdminPhotoResource($photo->load('user:id,profile_code,name,gender')),
            'Photo approved',
        );
    }

    public function reject(RejectPhotoRequest $request, ProfilePhoto $photo, ModeratePhoto $moderate): JsonResponse
    {
        $this->authorize('moderate', ProfilePhoto::class);

        $photo = $moderate($photo, 'reject', $request->user(), $request->reason());

        return ApiResponse::success(
            new AdminPhotoResource($photo->load('user:id,profile_code,name,gender')),
            'Photo rejected and the member has been told why',
        );
    }

    /**
     * The result reports what was skipped rather than failing the batch.
     *
     * The panel advances optimistically, so by the time a batch is submitted
     * some of its ids may already have been decided in another tab or by
     * another moderator. Rejecting the whole call for one stale id discards
     * ninety-nine good decisions; naming the stale ones lets the client drop
     * them from its local queue and move on.
     */
    public function bulk(BulkPhotoModerationRequest $request, ModeratePhoto $moderate): JsonResponse
    {
        $this->authorize('moderate', ProfilePhoto::class);

        $result = $moderate->bulk(
            ids: $request->input('ids'),
            decision: $request->string('action')->toString(),
            moderator: $request->user(),
            reason: $request->input('reason'),
        );

        return ApiResponse::success($result, "{$result['moderated']} photos moderated");
    }
}
