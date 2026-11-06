<?php

namespace App\Http\Controllers\Api\V1\User;

use App\Http\Controllers\Controller;
use App\Http\Resources\JadhagamResource;
use App\Models\UserJadhagam;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * The member's own Jadhagam (horoscope chart) uploads — the app's side of the
 * website's wizard step 6. Same rules as `Web\ProfileController`: an image up
 * to 5 MB, stored pending, shown to others only once an admin approves it.
 */
class JadhagamController extends Controller
{
    /** The owner sees every upload, including pending and rejected ones. */
    public function index(Request $request): JsonResponse
    {
        return ApiResponse::success(
            JadhagamResource::collection($request->user()->jadhagams()->get())->resolve(),
            'Jadhagam retrieved successfully',
        );
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'jadhagam' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ], [
            'jadhagam.required' => 'Please choose your Jadhagam image to upload.',
            'jadhagam.image' => 'The Jadhagam must be an image (JPG, PNG or WEBP).',
            'jadhagam.mimes' => 'The Jadhagam must be an image (JPG, PNG or WEBP).',
            'jadhagam.max' => 'The Jadhagam image must be 5 MB or smaller.',
        ]);

        $file = $request->file('jadhagam');

        $jadhagam = UserJadhagam::create([
            'user_id' => $request->user()->id,
            'path' => $file->store('jadhagams', 'public'),
            'disk' => 'public',
            'mime_type' => $file->getClientMimeType(),
            'size_bytes' => $file->getSize(),
            'approval_status' => 'pending',
        ]);

        return ApiResponse::created(
            new JadhagamResource($jadhagam),
            'Jadhagam uploaded. It will appear on your profile once approved by our team.',
        );
    }

    /** Someone else's upload answers 404, not 403 — its id is not ours to confirm. */
    public function destroy(Request $request, int $jadhagam): JsonResponse
    {
        $row = $request->user()->jadhagams()->whereKey($jadhagam)->firstOrFail();

        if (Storage::disk($row->disk)->exists($row->path)) {
            Storage::disk($row->disk)->delete($row->path);
        }

        $row->delete();

        return ApiResponse::noContent();
    }
}
