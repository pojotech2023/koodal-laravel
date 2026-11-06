<?php

namespace App\Http\Controllers\Api\V1\User;

use App\Actions\Photo\DeleteProfilePhoto;
use App\Actions\Photo\SetPrimaryPhoto;
use App\Actions\Photo\UploadProfilePhoto;
use App\Http\Controllers\Controller;
use App\Http\Requests\Photo\UploadPhotoRequest;
use App\Http\Resources\PhotoResource;
use App\Models\ProfilePhoto;
use App\Services\Profile\ProfileCompletion;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProfilePhotoController extends Controller
{
    /** The owner sees every photo, including pending and rejected ones. */
    public function index(Request $request): JsonResponse
    {
        return ApiResponse::success(
            PhotoResource::collection($request->user()->photos)->resolve(),
            'Photos retrieved successfully',
        );
    }

    /**
     * 202, not 201: the row exists but the derivatives do not yet. The client
     * shows the photo as "processing" rather than rendering a URL that is not
     * ready.
     */
    public function store(UploadPhotoRequest $request, UploadProfilePhoto $upload, ProfileCompletion $completion): JsonResponse
    {
        $photo = $upload($request->user(), $request->file('photo'));

        $completion->recalculate($request->user());

        return ApiResponse::success(
            new PhotoResource($photo),
            config('matrimony.profile.photo_moderation_required')
                ? 'Photo uploaded. It will appear once approved.'
                : 'Photo uploaded successfully',
            202,
        );
    }

    public function setPrimary(ProfilePhoto $photo, SetPrimaryPhoto $setPrimary, Request $request): JsonResponse
    {
        return ApiResponse::success(
            new PhotoResource($setPrimary($request->user(), $photo)),
            'Primary photo updated',
        );
    }

    public function reorder(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'order' => ['required', 'array', 'min:1', 'max:20'],
            'order.*' => ['integer'],
        ]);

        $user = $request->user();

        // Scoped to the user's own ids, so a payload containing someone else's
        // photo id silently affects nothing rather than reordering their album.
        $owned = $user->photos()->pluck('id')->all();

        foreach ($validated['order'] as $position => $photoId) {
            if (in_array($photoId, $owned)) {
                $user->photos()->whereKey($photoId)->update(['sort_order' => $position]);
            }
        }

        return ApiResponse::success(
            PhotoResource::collection($user->photos()->get())->resolve(),
            'Photos reordered successfully',
        );
    }

    public function destroy(ProfilePhoto $photo, DeleteProfilePhoto $delete, Request $request, ProfileCompletion $completion): JsonResponse
    {
        $delete($request->user(), $photo);

        $completion->recalculate($request->user());

        return ApiResponse::noContent();
    }
}
