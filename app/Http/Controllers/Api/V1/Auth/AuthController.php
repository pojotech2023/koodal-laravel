<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Actions\Auth\LoginUser;
use App\Actions\Auth\RegisterUser;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ChangePasswordRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Resources\MembershipResource;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\Auth\OtpService;
use App\Services\Membership\FeatureGate;
use App\Support\ApiResponse;
use App\Support\PhoneNumber;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function register(RegisterRequest $request, RegisterUser $registerUser): JsonResponse
    {
        $result = $registerUser(
            $request->validated(),
            source: $this->source($request),
            ip: $request->ip(),
        );

        // No token yet. A typo'd mobile number must not produce a working
        // account that nobody can recover.
        return ApiResponse::created([
            'user_id' => $result['user']->id,
            'profile_code' => $result['user']->profile_code,
            'mobile' => PhoneNumber::mask($result['user']->mobile),
            'otp_required' => $result['otp_required'],
            'otp_expires_in' => $result['otp_expires_in'],
        ], 'Account created. Enter the OTP sent to your mobile.');
    }

    public function login(LoginRequest $request, LoginUser $loginUser): JsonResponse
    {
        $credentials = $request->credentials();

        $result = $loginUser(
            $credentials['column'],
            $credentials['value'],
            $request->input('password'),
            $request->deviceName(),
        );

        return ApiResponse::success(
            $this->sessionPayload($result['user'], $result['token']),
            'Logged in successfully',
        );
    }

    /**
     * Passwordless login. Responds identically whether or not the account
     * exists — anything else turns this into an account-enumeration oracle.
     */
    public function requestOtpLogin(Request $request, OtpService $otp): JsonResponse
    {
        $raw = $request->input('identifier')
            ?? $request->input('mobile')
            ?? $request->input('phone')
            ?? $request->input('phone_number')
            ?? $request->input('username')
            ?? $request->input('email');

        $identifier = PhoneNumber::normalise($raw) ?? $raw;

        if (! $identifier) {
            return ApiResponse::error('Validation failed', ['identifier' => ['The identifier field is required.']], 422);
        }

        $norm = PhoneNumber::normalise($identifier);
        $user = User::where('mobile', $identifier)
            ->when($norm, fn ($q) => $q->orWhere('mobile', $norm))
            ->orWhere('email', $identifier)
            ->first();

        if ($user) {
            $issued = $otp->issue($user->mobile, 'login', 'sms', $request->ip());
            $otp->deliver($user->mobile, $issued['code']);
        }

        return ApiResponse::success(
            ['otp_expires_in' => 300],
            'If an account exists for that number, a login code has been sent.',
        );
    }

    public function me(Request $request, FeatureGate $features): JsonResponse
    {
        $user = $request->user()->load([
            'role', 'profile.primaryPhoto', 'activeSubscription.plan', 'privacySettings',
        ]);

        return ApiResponse::success(
            $this->sessionPayload($user, null, $features),
            'Account retrieved successfully',
        );
    }

    public function logout(Request $request): JsonResponse
    {
        if ($fcmToken = ($request->input('fcm_token') ?? $request->input('token'))) {
            \App\Models\UserFcmToken::where('fcm_token', $fcmToken)->delete();
            $request->user()?->deviceTokens()->where('token', $fcmToken)->delete();
        }

        $request->user()->currentAccessToken()->delete();

        return ApiResponse::success(null, 'Logged out successfully');
    }

    public function logoutAll(Request $request): JsonResponse
    {
        $request->user()->tokens()->delete();

        return ApiResponse::success(null, 'Logged out from all devices');
    }

    public function changePassword(ChangePasswordRequest $request): JsonResponse
    {
        $user = $request->user();

        $user->update(['password' => $request->input('password')]);

        // Revoking other sessions is the point of a password change after a
        // suspected compromise, so it defaults to on.
        if ($request->boolean('logout_other_devices', true)) {
            $current = $user->currentAccessToken()->id;
            $user->tokens()->where('id', '!=', $current)->delete();
        }

        return ApiResponse::success(null, 'Password changed successfully');
    }

    /** One shape for login, /auth/me and post-OTP verification. */
    protected function sessionPayload(User $user, ?string $token, ?FeatureGate $features = null): array
    {
        $features ??= app(FeatureGate::class);
        $subscription = $user->activeSubscription;

        return array_filter([
            'token' => $token,
            'token_type' => $token ? 'Bearer' : null,
            'user' => new UserResource($user),
            'profile' => [
                'completion_percentage' => $user->profile?->completion_percentage ?? 0,
                'approval_status' => $user->profile?->approval_status ?? 'pending',
                'is_verified' => (bool) ($user->profile?->is_verified ?? false),
                'primary_photo_url' => $user->profile?->primaryPhoto?->urlFor('thumbnail'),
            ],
            'subscription' => new MembershipResource($subscription, $features->features($user)),
        ], fn ($v) => $v !== null);
    }

    protected function source(Request $request): string
    {
        return match ($request->header('X-Client')) {
            'android' => 'android',
            'ios' => 'ios',
            default => 'web',
        };
    }
}
