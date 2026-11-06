<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Actions\Auth\RegisterDeviceToken;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\DeviceTokenRequest;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DeviceTokenController extends Controller
{
    public function store(DeviceTokenRequest $request, RegisterDeviceToken $register): JsonResponse
    {
        $register($request->user(), $request->validated());

        return ApiResponse::success(null, 'Device registered for notifications');
    }

    /**
     * Called on logout. Without it, the next person to log in on this handset
     * keeps receiving the previous user's notifications until the token
     * naturally rotates.
     */
    public function destroy(Request $request): JsonResponse
    {
        $request->validate(['token' => ['required', 'string', 'max:255']]);

        $request->user()->deviceTokens()->where('token', $request->input('token'))->delete();
        $request->user()->fcmTokens()->where('fcm_token', $request->input('token'))->delete();

        return ApiResponse::noContent();
    }
}
