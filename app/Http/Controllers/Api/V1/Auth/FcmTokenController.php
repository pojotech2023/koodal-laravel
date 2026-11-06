<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RemoveFcmTokenRequest;
use App\Http\Requests\Auth\StoreFcmTokenRequest;
use App\Models\DeviceToken;
use App\Models\UserFcmToken;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

/**
 * @group Authentication & Devices
 * APIs for registering and removing mobile FCM push tokens.
 */
class FcmTokenController extends Controller
{
    /**
     * Store or Update FCM Device Token
     *
     * Registers an FCM token for the authenticated customer. If the same token previously
     * belonged to a different user (e.g. on a shared phone), it is safely reassigned.
     *
     * @response 200 {
     *   "success": true,
     *   "message": "FCM token registered successfully",
     *   "data": {
     *     "fcm_token": "dK8s_...",
     *     "device_type": "android"
     *   }
     * }
     */
    public function store(StoreFcmTokenRequest $request): JsonResponse
    {
        $user = $request->user();
        $validated = $request->validated();

        // 1. Update or create in user_fcm_tokens (ensuring platform-wide uniqueness & reassigning if needed)
        $tokenRecord = UserFcmToken::updateOrCreate(
            ['fcm_token' => $validated['fcm_token']],
            [
                'user_id' => $user->id,
                'device_type' => $validated['device_type'],
                'device_id' => $validated['device_id'] ?? null,
                'app_version' => $validated['app_version'] ?? null,
                'last_used_at' => now(),
            ]
        );

        // 2. Synchronize with legacy device_tokens table for backwards compatibility
        DeviceToken::updateOrCreate(
            ['token' => $validated['fcm_token']],
            [
                'user_id' => $user->id,
                'platform' => $validated['device_type'],
                'device_id' => $validated['device_id'] ?? null,
                'app_version' => $validated['app_version'] ?? null,
                'last_used_at' => now(),
            ]
        );

        return ApiResponse::success([
            'fcm_token' => $tokenRecord->fcm_token,
            'device_type' => $tokenRecord->device_type,
            'device_id' => $tokenRecord->device_id,
        ], 'FCM token registered successfully');
    }

    /**
     * Remove FCM Device Token
     *
     * Unregisters an FCM token when the customer logs out of their mobile handset.
     *
     * @response 200 {
     *   "success": true,
     *   "message": "FCM token removed successfully",
     *   "data": null
     * }
     */
    public function remove(RemoveFcmTokenRequest $request): JsonResponse
    {
        $fcmToken = $request->input('fcm_token');

        UserFcmToken::where('fcm_token', $fcmToken)->delete();
        DeviceToken::where('token', $fcmToken)->delete();

        return ApiResponse::success(null, 'FCM token removed successfully');
    }
}
