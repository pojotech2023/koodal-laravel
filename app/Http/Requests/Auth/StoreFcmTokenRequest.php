<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

/**
 * @bodyParam fcm_token string required The FCM device token issued by Firebase SDK. Example: dK8s_example_token_...
 * @bodyParam device_type string required Device operating system: android or ios. Example: android
 * @bodyParam device_id string optional Unique handset identifier (UUID/IMEI). Example: a1b2c3d4-e5f6
 * @bodyParam app_version string optional Installed mobile application build version. Example: 1.0.4
 */
class StoreFcmTokenRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'fcm_token' => ['required', 'string', 'max:500'],
            'device_type' => ['required', 'string', 'in:android,ios'],
            'device_id' => ['nullable', 'string', 'max:191'],
            'app_version' => ['nullable', 'string', 'max:50'],
        ];
    }
}
