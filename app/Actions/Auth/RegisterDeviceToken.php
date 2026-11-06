<?php

namespace App\Actions\Auth;

use App\Models\DeviceToken;
use App\Models\User;
use App\Models\UserFcmToken;

/**
 * FCM tokens move between accounts: a shared phone, or one person logging out
 * and a family member logging in. The token is unique platform-wide, so it is
 * re-pointed at the current user rather than duplicated — otherwise the
 * previous owner keeps receiving this user's notifications.
 *
 * Written to both token tables. Pushes read either one, but the app registers
 * here and only this table was being filled, so `user_fcm_tokens` looked empty
 * even for a registered phone.
 */
class RegisterDeviceToken
{
    public function __invoke(User $user, array $data): DeviceToken
    {
        UserFcmToken::updateOrCreate(
            ['fcm_token' => $data['token']],
            [
                'user_id' => $user->id,
                'device_type' => $data['platform'],
                'device_id' => $data['device_id'] ?? null,
                'app_version' => $data['app_version'] ?? null,
                'last_used_at' => now(),
            ],
        );

        return DeviceToken::updateOrCreate(
            ['token' => $data['token']],
            [
                'user_id' => $user->id,
                'platform' => $data['platform'],
                'device_id' => $data['device_id'] ?? null,
                'app_version' => $data['app_version'] ?? null,
                'last_used_at' => now(),
            ],
        );
    }
}
