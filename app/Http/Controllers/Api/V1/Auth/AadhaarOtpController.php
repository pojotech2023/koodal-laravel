<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Services\Verification\AadhaarOtpRequests;
use App\Services\Verification\AadhaarVerificationService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class AadhaarOtpController extends Controller
{
    public function __construct(
        protected AadhaarVerificationService $service,
        protected AadhaarOtpRequests $requests,
    ) {}

    /**
     * Generate OTP for Aadhaar verification via QuickEKYC.
     */
    public function generateOtp(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'id_number' => ['required', 'string'],
        ]);

        $cleanAadhaar = preg_replace('/\D/', '', $validated['id_number']);

        if (strlen($cleanAadhaar) !== 12) {
            return ApiResponse::error('Please provide a valid 12-digit Aadhaar number.', [
                'id_number' => ['The Aadhaar number must be exactly 12 digits.'],
            ], 422);
        }

        $result = $this->service->generateOtp($cleanAadhaar);

        if (! $result['success']) {
            return ApiResponse::error($result['message'], [], 400);
        }

        $requestId = (string) $result['request_id'];

        // verifyOtp() takes the number from here, never from the client.
        $this->requests->remember(AadhaarOtpRequests::requestKey($requestId), $requestId, $cleanAadhaar);
        Cache::put("aadhaar_req_by_num_{$cleanAadhaar}", $requestId, now()->addMinutes(AadhaarOtpRequests::TTL_MINUTES));

        return ApiResponse::success([
            'request_id' => $requestId,
            'id_number' => $cleanAadhaar,
            'expires_in' => 600, // 10 minutes
        ], $result['message']);
    }

    /**
     * Submit OTP to verify Aadhaar via QuickEKYC.
     *
     * Only for a request generateOtp() started, and only for the number that
     * request was for. `id_number` / `aadhaar_number` are accepted for older
     * clients, but must match.
     */
    public function verifyOtp(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'request_id' => ['required', 'string'],
            'otp' => ['required', 'string', 'size:6', 'regex:/^[0-9]{6}$/'],
            'id_number' => ['nullable', 'string'],
            'aadhaar_number' => ['nullable', 'string'],
        ]);

        $requestId = (string) $validated['request_id'];
        $otp = $validated['otp'];
        $key = AadhaarOtpRequests::requestKey($requestId);
        $pending = $this->requests->find($key, $requestId);

        if (! $pending) {
            return ApiResponse::error(AadhaarOtpRequests::EXPIRED, ['otp' => [AadhaarOtpRequests::EXPIRED]], 422);
        }

        $cleanAadhaar = $pending['aadhaar'];

        if (! $this->requests->claims($pending, $validated['id_number'] ?? $validated['aadhaar_number'] ?? null)) {
            return ApiResponse::error(AadhaarOtpRequests::OTHER_NUMBER, ['id_number' => [AadhaarOtpRequests::OTHER_NUMBER]], 422);
        }

        $result = $this->requests->submit($key, $pending, $otp);

        if (! $result['success']) {
            return ApiResponse::error($result['message'], $result['status'] === 422 ? ['otp' => [$result['message']]] : [], $result['status']);
        }

        $verificationToken = Str::random(40);

        // Store cache verification flag and verification token for 2 hours
        Cache::put("aadhaar_verified_{$cleanAadhaar}", true, now()->addHours(2));
        Cache::put("aadhaar_token_{$verificationToken}", $cleanAadhaar, now()->addHours(2));

        return ApiResponse::success([
            'verified' => true,
            'aadhaar_number' => $cleanAadhaar,
            'aadhaar_number_masked' => 'XXXX XXXX ' . substr($cleanAadhaar, -4),
            'verification_token' => $verificationToken,
            'full_name' => $result['data']['full_name'] ?? null,
            'gender' => $result['data']['gender'] ?? null,
            'dob' => $result['data']['dob'] ?? null,
            'badge' => [
                'verified' => true,
                'label' => 'Verified Profile',
                'icon' => '✓',
            ],
        ], 'Aadhaar verified successfully! ✓');
    }
}

