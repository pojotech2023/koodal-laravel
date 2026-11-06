<?php

namespace Tests\Feature\Api\V1\Auth;

use Illuminate\Http\Client\Request as ProviderRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * The pre-registration Aadhaar OTP (`/auth/aadhaar/*`). Same rule as the
 * member endpoints: only the real OTP, for a request the API started, and
 * only for the number that request was sent for.
 */
class AadhaarOtpApiTest extends TestCase
{
    protected const REAL_OTP = '481516';

    protected const NUMBER = '234567890123';

    protected function setUp(): void
    {
        parent::setUp();

        Http::fake(function (ProviderRequest $request) {
            if (str_contains($request->url(), 'generate-otp')) {
                return Http::response(['status' => 'success', 'request_id' => 'REQ-'.$request['id_number'], 'message' => 'OTP sent']);
            }

            return $request['otp'] === self::REAL_OTP
                ? Http::response(['status' => 'success', 'data' => ['full_name' => 'Real Holder']])
                : Http::response(['status' => 'failed', 'message' => 'Invalid OTP. Please try again.'], 422);
        });
    }

    protected function sendOtp(): string
    {
        return $this->postJson('/api/v1/auth/aadhaar/generate-otp', ['id_number' => self::NUMBER])
            ->assertOk()
            ->json('data.request_id');
    }

    public function test_the_test_otp_without_any_request_does_not_verify(): void
    {
        $this->postJson('/api/v1/auth/aadhaar/verify-otp', [
            'request_id' => 'made-up',
            'otp' => '123456',
            'aadhaar_number' => self::NUMBER,
        ])->assertUnprocessable();

        Http::assertNothingSent();
        $this->assertNull(Cache::get('aadhaar_verified_'.self::NUMBER));
    }

    public function test_the_test_otp_is_refused_for_a_real_request(): void
    {
        $requestId = $this->sendOtp();

        $this->postJson('/api/v1/auth/aadhaar/verify-otp', [
            'request_id' => $requestId,
            'otp' => '123456',
        ])->assertStatus(400);

        $this->assertNull(Cache::get('aadhaar_verified_'.self::NUMBER));
    }

    public function test_a_different_number_at_verify_is_refused(): void
    {
        $requestId = $this->sendOtp();

        $this->postJson('/api/v1/auth/aadhaar/verify-otp', [
            'request_id' => $requestId,
            'otp' => self::REAL_OTP,
            'aadhaar_number' => '999988887777',
        ])->assertUnprocessable()->assertJsonValidationErrors('id_number');

        $this->assertNull(Cache::get('aadhaar_verified_999988887777'));
        $this->assertNull(Cache::get('aadhaar_verified_'.self::NUMBER));
    }

    public function test_the_real_otp_verifies_the_number_the_otp_was_sent_for(): void
    {
        $requestId = $this->sendOtp();

        $response = $this->postJson('/api/v1/auth/aadhaar/verify-otp', [
            'request_id' => $requestId,
            'otp' => self::REAL_OTP,
        ])->assertOk()->assertJsonPath('data.aadhaar_number', self::NUMBER);

        $this->assertTrue(Cache::get('aadhaar_verified_'.self::NUMBER));
        $this->assertSame(self::NUMBER, Cache::get('aadhaar_token_'.$response->json('data.verification_token')));
    }
}
