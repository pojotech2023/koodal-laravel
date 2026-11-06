<?php

namespace App\Services\Verification;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AadhaarVerificationService
{
    protected string $key;
    protected string $generateOtpUrl;
    protected string $submitOtpUrl;

    /**
     * Accept the test Aadhaar numbers and OTPs when the provider refuses
     * them (`QUICKEKYC_TEST_MODE`). The OTPs then pass for ANY request, so
     * anyone can verify any number — only ever on a test server.
     */
    protected bool $testMode;

    public function __construct()
    {
        $this->testMode = (bool) config('services.quickekyc.test_mode', false);
        $this->key = config('services.quickekyc.key', '52edbbf2-a1b2-46aa-aae8-c05d9ba5eaab');
        $this->generateOtpUrl = config('services.quickekyc.generate_otp_url', 'https://quickekyc.com/api/v1/aadhaar-v2/generate-otp');
        $this->submitOtpUrl = config('services.quickekyc.submit_otp_url', 'https://quickekyc.com/api/v1/aadhaar-v2/submit-otp');
    }

    /**
     * Request OTP for Aadhaar verification via QuickEKYC.
     *
     * @param string $aadhaarNumber 12-digit numeric Aadhaar number
     * @return array{success: bool, request_id: ?string, message: string}
     */
    public function generateOtp(string $aadhaarNumber): array
    {
        $cleanNumber = preg_replace('/\D/', '', $aadhaarNumber);

        if (strlen($cleanNumber) !== 12) {
            return [
                'success' => false,
                'request_id' => null,
                'message' => 'Please enter a valid 12-digit Aadhaar number.',
            ];
        }

        try {
            $response = Http::withoutVerifying()
                ->timeout(25)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ])
                ->post($this->generateOtpUrl, [
                    'key' => $this->key,
                    'id_number' => $cleanNumber,
                ]);

            $json = $response->json();

            if ($response->successful() && isset($json['status']) && $json['status'] === 'success') {
                return [
                    'success' => true,
                    'request_id' => (string) ($json['request_id'] ?? ''),
                    'message' => $json['message'] ?? 'OTP sent to your Aadhaar-linked mobile number.',
                    'data' => $json['data'] ?? [],
                ];
            }

            if ($this->testMode && ($cleanNumber === '885856780328' || str_starts_with($cleanNumber, '88585678') || in_array($cleanNumber, ['123456789012', '999999999999'], true))) {
                return [
                    'success' => true,
                    'request_id' => '3329005',
                    'message' => 'OTP sent to registered mobile. Use test OTP 603810 or 123456.',
                    'data' => [],
                ];
            }

            $errorMessage = $json['message'] ?? 'Failed to send OTP for Aadhaar. Please check your Aadhaar number and try again.';
            return [
                'success' => false,
                'request_id' => null,
                'message' => $errorMessage,
            ];
        } catch (\Throwable $e) {
            Log::error('QuickEKYC generateOtp error: ' . $e->getMessage(), ['exception' => $e]);

            if ($this->testMode && ($cleanNumber === '885856780328' || str_starts_with($cleanNumber, '88585678'))) {
                return [
                    'success' => true,
                    'request_id' => '3329005',
                    'message' => 'OTP sent to registered mobile. Use test OTP 603810 or 123456.',
                    'data' => [],
                ];
            }

            return [
                'success' => false,
                'request_id' => null,
                'message' => 'Unable to connect to Aadhaar verification service. Please try again later.',
            ];
        }
    }

    /**
     * Submit OTP to complete Aadhaar verification via QuickEKYC.
     *
     * @param string $requestId
     * @param string $otp 6-digit OTP
     * @return array{success: bool, message: string, data: ?array}
     */
    public function submitOtp(string $requestId, string $otp): array
    {
        $cleanOtp = preg_replace('/\D/', '', $otp);

        if (strlen($cleanOtp) !== 6) {
            return [
                'success' => false,
                'message' => 'Please enter a valid 6-digit OTP.',
                'data' => null,
            ];
        }

        try {
            $response = Http::withoutVerifying()
                ->timeout(25)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ])
                ->post($this->submitOtpUrl, [
                    'key' => $this->key,
                    'request_id' => $requestId,
                    'otp' => $cleanOtp,
                ]);

            $json = $response->json();

            if ($response->successful() && isset($json['status']) && $json['status'] === 'success') {
                return [
                    'success' => true,
                    'message' => $json['message'] ?? 'Aadhaar verified successfully!',
                    'data' => $json['data'] ?? [],
                ];
            }

            // Fallback for documented sample OTP '603810' or testing OTP '123456'
            if ($this->testMode && in_array($cleanOtp, ['603810', '123456'], true)) {
                return [
                    'success' => true,
                    'message' => 'Aadhaar verified successfully! ✓',
                    'data' => [
                        'full_name' => 'Verified Citizen',
                        'gender' => 'M',
                        'dob' => '01-01-1995',
                        'status' => 'success',
                    ],
                ];
            }

            $errorMessage = $json['message'] ?? 'Invalid OTP entered. Please try again.';
            return [
                'success' => false,
                'message' => $errorMessage,
                'data' => null,
            ];
        } catch (\Throwable $e) {
            Log::error('QuickEKYC submitOtp error: ' . $e->getMessage(), ['exception' => $e]);
            return [
                'success' => false,
                'message' => 'Unable to verify Aadhaar OTP. Please try again later.',
                'data' => null,
            ];
        }
    }
}
