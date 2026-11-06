<?php

namespace Tests\Feature\Api\V1\Profile;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as ProviderRequest;
use Illuminate\Support\Facades\Http;
use Tests\Concerns\SeedsCoreData;
use Tests\TestCase;

/**
 * The member's Aadhaar verification (`/profile/aadhaar/*`). An OTP proves who
 * holds the phone linked to one Aadhaar number, so only the real OTP, for
 * the request this member started and the number it was sent for, verifies.
 */
class AadhaarVerificationApiTest extends TestCase
{
    use RefreshDatabase, SeedsCoreData;

    /** What the provider accepts; everything else it rejects. */
    protected const REAL_OTP = '481516';

    protected const NUMBER = '234567890123';

    /** Whether the provider sends an OTP for the number it is given. */
    protected bool $generateSucceeds = true;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedRoles();
        $this->seedPlans();
        $this->fakeProvider();
    }

    protected function fakeProvider(): void
    {
        $sent = 0;

        Http::fake(function (ProviderRequest $request) use (&$sent) {
            if (str_contains($request->url(), 'generate-otp')) {
                return $this->generateSucceeds
                    ? Http::response(['status' => 'success', 'request_id' => 'REQ-'.(++$sent), 'message' => 'OTP sent'])
                    : Http::response(['status' => 'failed', 'message' => 'Invalid Aadhaar number'], 422);
            }

            return $request['otp'] === self::REAL_OTP
                ? Http::response(['status' => 'success', 'data' => ['full_name' => 'Real Holder']])
                : Http::response(['status' => 'failed', 'message' => 'Invalid OTP. Please try again.'], 422);
        });
    }

    protected function sendOtp(string $number = self::NUMBER): string
    {
        return $this->postJson('/api/v1/profile/aadhaar/generate-otp', ['id_number' => $number])
            ->assertOk()
            ->json('data.request_id');
    }

    protected function verify(string $requestId, string $otp, ?string $number = null)
    {
        return $this->postJson('/api/v1/profile/aadhaar/verify-otp', array_filter([
            'request_id' => $requestId,
            'otp' => $otp,
            'id_number' => $number,
        ]));
    }

    protected function assertNotVerified(User $user): void
    {
        $user->refresh();
        $this->assertFalse((bool) $user->is_aadhaar_verified);
        $this->assertNull($user->aadhaar_number);
        $this->assertFalse((bool) $user->profile->is_verified);
    }

    public function test_the_test_otp_without_any_request_does_not_verify(): void
    {
        $me = $this->actingAsMember();

        $this->verify('made-up', '123456', self::NUMBER)
            ->assertUnprocessable()
            ->assertJsonPath('message', 'This OTP has expired. Please request a new OTP.');

        Http::assertNothingSent();
        $this->assertNotVerified($me);
    }

    public function test_the_test_otp_is_refused_for_a_real_request(): void
    {
        $me = $this->actingAsMember();
        $requestId = $this->sendOtp();

        $this->verify($requestId, '123456')->assertStatus(400);

        $this->assertNotVerified($me);
    }

    public function test_the_real_otp_verifies_the_number_the_otp_was_sent_for(): void
    {
        $me = $this->actingAsMember();
        $requestId = $this->sendOtp();

        $this->verify($requestId, self::REAL_OTP)
            ->assertOk()
            ->assertJsonPath('data.is_aadhaar_verified', true)
            ->assertJsonPath('data.aadhaar_number_masked', 'XXXX XXXX 0123');

        $me->refresh();
        $this->assertTrue((bool) $me->is_aadhaar_verified);
        $this->assertSame(self::NUMBER, $me->aadhaar_number);
        $this->assertTrue((bool) $me->profile->is_verified);
        $this->assertSame(100, $me->profile->completion_percentage);

        // The request is used up.
        $this->verify($requestId, self::REAL_OTP)->assertUnprocessable();
    }

    public function test_a_different_number_at_verify_is_refused(): void
    {
        $me = $this->actingAsMember();
        $requestId = $this->sendOtp();

        $this->verify($requestId, self::REAL_OTP, '999988887777')
            ->assertUnprocessable()
            ->assertJsonValidationErrors('id_number');

        $this->assertNotVerified($me);
    }

    public function test_another_members_request_is_refused(): void
    {
        $this->actingAsMember();
        $requestId = $this->sendOtp();

        $intruder = $this->member();
        $this->app['auth']->forgetGuards();
        $this->actingAsMember($intruder);

        $this->verify($requestId, self::REAL_OTP)->assertUnprocessable();

        $this->assertNotVerified($intruder);
    }

    public function test_a_number_linked_to_another_account_since_the_otp_was_sent_is_refused(): void
    {
        $me = $this->actingAsMember();
        $requestId = $this->sendOtp();

        $this->member(['aadhaar_number' => self::NUMBER, 'is_aadhaar_verified' => true]);

        $this->verify($requestId, self::REAL_OTP)
            ->assertUnprocessable()
            ->assertJsonPath('message', 'This Aadhaar number is already linked to another account.');

        $this->assertNotVerified($me);
    }

    public function test_five_wrong_otps_use_up_the_request(): void
    {
        $me = $this->actingAsMember();
        $requestId = $this->sendOtp();

        foreach (range(1, 4) as $attempt) {
            $this->verify($requestId, '000000')->assertStatus(400);
        }

        $this->verify($requestId, '000000')
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Too many incorrect OTPs. Please request a new OTP.');

        $this->verify($requestId, self::REAL_OTP)->assertUnprocessable();
        $this->assertNotVerified($me);
    }

    public function test_test_numbers_get_no_otp_when_the_provider_refuses_them(): void
    {
        $this->generateSucceeds = false;
        $this->actingAsMember();

        $this->postJson('/api/v1/profile/aadhaar/generate-otp', ['id_number' => '885856780328'])
            ->assertStatus(400);
    }

    public function test_a_test_server_can_turn_the_test_otp_back_on(): void
    {
        config(['services.quickekyc.test_mode' => true]);
        $me = $this->actingAsMember();
        $requestId = $this->sendOtp();

        $this->verify($requestId, '123456')->assertOk();

        $this->assertTrue((bool) $me->refresh()->is_aadhaar_verified);
    }
}
