<?php

namespace App\Http\Controllers\Api\V1\User;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class NotificationConfigController extends Controller
{
    /**
     * Get Firebase push notification channel and sound configuration for mobile app registration.
     */
    public function show(): JsonResponse
    {
        $enabled = Setting::get('firebase_push_enabled', true);
        if (is_string($enabled)) {
            $enabled = filter_var($enabled, FILTER_VALIDATE_BOOLEAN);
        }

        return ApiResponse::success([
            'project_id' => Setting::get('firebase_project_id') ?: config('firebase.projects.app.project_id', 'koodalmatrimony-default'),
            'channel_id' => Setting::get('firebase_android_channel_id', 'koodal_notifications') ?: 'koodal_notifications',
            'channel_name' => 'Koodal Matrimony Notifications',
            'sound_name' => Setting::get('firebase_sound_name', 'default') ?: 'default',
            'sound_file' => (Setting::get('firebase_sound_name', 'default') ?: 'default').'.mp3',
            'push_enabled' => (bool) $enabled,
        ], 'Notification configuration retrieved successfully');
    }
}
