<?php

namespace App\Http\Controllers\Api\V1\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\Privacy\UpdatePrivacyRequest;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PrivacyController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $settings = $request->user()->privacySettings
            ?? $request->user()->privacySettings()->create(config('matrimony.privacy.defaults'));

        return ApiResponse::success([
            'settings' => $settings->only(array_keys(config('matrimony.privacy.defaults'))),
            // The client renders its selects from this rather than hardcoding
            // the option lists, so adding an audience is a server change only.
            'options' => config('matrimony.privacy.options'),
        ], 'Privacy settings retrieved successfully');
    }

    public function update(UpdatePrivacyRequest $request): JsonResponse
    {
        $user = $request->user();

        $settings = $user->privacySettings()->updateOrCreate(
            ['user_id' => $user->id],
            $request->validated(),
        );

        return ApiResponse::success(
            $settings->only(array_keys(config('matrimony.privacy.defaults'))),
            'Privacy settings saved successfully',
        );
    }
}
