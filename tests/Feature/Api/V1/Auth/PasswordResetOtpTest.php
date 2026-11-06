<?php

namespace Tests\Feature\Api\V1\Auth;

use App\Models\OtpVerification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as SmsRequest;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Tests\Concerns\SeedsCoreData;
use Tests\TestCase;

/**
 * The app's forgot-password flow: forgot-password → verify-otp → reset-password.
 * A real (random) code is used throughout: the `log` SMS driver's fixed
 * 123456 is also accepted by OtpService as a test code, which would make
 * every assertion here pass whatever the flow did.
 */
class PasswordResetOtpTest extends TestCase
{
    use RefreshDatabase, SeedsCoreData;

    protected User $member;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedRoles();
        config(['services.sms.driver' => 'gateway', 'services.sms.endpoint' => 'https://sms.test/send']);
        Http::fake(['sms.test/*' => Http::response('ok')]);

        $this->member = $this->member(['mobile' => '+919876543210', 'email' => 'asha@example.com']);
    }

    public function test_a_reset_started_with_an_email_verifies_and_resets(): void
    {
        $code = $this->postJson('/api/v1/auth/forgot-password', ['identifier' => 'asha@example.com'])
            ->assertOk()
            // The account's mobile is never echoed back for an email.
            ->assertJsonMissingPath('data.mobile')
            ->json('data.code');

        $this->assertSmsSentTo('919876543210');

        $this->postJson('/api/v1/auth/verify-otp', [
            'identifier' => 'asha@example.com', 'code' => $code, 'purpose' => 'password_reset',
        ])->assertOk()->assertJsonPath('data.verified', true);

        $this->postJson('/api/v1/auth/reset-password', [
            'identifier' => 'asha@example.com', 'code' => $code,
            'password' => 'NewPassword123!', 'password_confirmation' => 'NewPassword123!',
        ])->assertOk();

        $this->assertTrue(Hash::check('NewPassword123!', $this->member->refresh()->password));
    }

    public function test_resending_a_reset_code_for_an_email_texts_the_accounts_mobile(): void
    {
        $this->postJson('/api/v1/auth/resend-otp', ['identifier' => 'asha@example.com', 'purpose' => 'password_reset'])
            ->assertOk();

        $this->assertSmsSentTo('919876543210');
        $this->assertSame(1, OtpVerification::where('identifier', '+919876543210')->count());
    }

    public function test_resending_a_reset_code_for_an_unknown_number_texts_nobody(): void
    {
        $this->postJson('/api/v1/auth/resend-otp', ['identifier' => '9000011111', 'purpose' => 'password_reset'])
            ->assertOk()
            ->assertJsonPath('message', 'A new code has been sent.');

        Http::assertNothingSent();
        $this->assertSame(0, OtpVerification::count());
    }

    public function test_one_wrong_code_does_not_expire_the_right_one(): void
    {
        $code = $this->postJson('/api/v1/auth/forgot-password', ['identifier' => '9876543210'])->json('data.code');
        $expiresAt = OtpVerification::sole()->expires_at;
        $wrong = $code === '000000' ? '111111' : '000000';

        $this->postJson('/api/v1/auth/verify-otp', ['identifier' => '9876543210', 'code' => $wrong, 'purpose' => 'password_reset'])
            ->assertUnprocessable()
            ->assertJsonPath('errors.otp.0', 'The code you entered is incorrect.');

        $this->assertEquals($expiresAt, OtpVerification::sole()->expires_at);

        $this->postJson('/api/v1/auth/verify-otp', ['identifier' => '9876543210', 'code' => $code, 'purpose' => 'password_reset'])
            ->assertOk();
    }

    protected function assertSmsSentTo(string $number): void
    {
        Http::assertSent(fn (SmsRequest $request) => ($request->data()['numbers'] ?? null) === $number);
    }
}
