<?php

namespace Tests\Feature\Web;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as ProviderRequest;
use Illuminate\Support\Facades\Http;
use Tests\Concerns\SeedsCoreData;
use Tests\TestCase;

/**
 * The website's Aadhaar steps: the profile wizard, registration, and the OTP
 * screen. Same rule as the API — only the real OTP, for the request this
 * member or browser started, for the number it was sent to. The test OTPs
 * only work with QUICKEKYC_TEST_MODE on.
 */
class AadhaarWebTest extends TestCase
{
    use RefreshDatabase, SeedsCoreData;

    protected const REAL_OTP = '481516';

    protected const NUMBER = '234567890123';

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedRoles();
        $this->seedPlans();

        $sent = 0;
        Http::fake(function (ProviderRequest $request) use (&$sent) {
            if (str_contains($request->url(), 'generate-otp')) {
                return Http::response(['status' => 'success', 'request_id' => 'REQ-'.(++$sent), 'message' => 'OTP sent']);
            }

            return $request['otp'] === self::REAL_OTP
                ? Http::response(['status' => 'success', 'data' => ['full_name' => 'Real Holder']])
                : Http::response(['status' => 'failed', 'message' => 'Invalid OTP. Please try again.'], 422);
        });
    }

    // --- Profile wizard (step 7) ---------------------------------------------

    protected function wizardSendOtp(): string
    {
        return $this->postJson(route('profile.wizard.aadhaar.generate-otp'), ['id_number' => self::NUMBER])
            ->assertOk()
            ->json('request_id');
    }

    protected function wizardVerify(string $requestId, string $otp, ?string $number = self::NUMBER)
    {
        return $this->postJson(route('profile.wizard.aadhaar.verify-otp'), array_filter([
            'request_id' => $requestId,
            'otp' => $otp,
            'aadhaar_number' => $number,
        ]));
    }

    public function test_wizard_test_otp_without_a_request_does_not_verify(): void
    {
        $me = $this->member();
        $this->actingAs($me, 'web');

        $this->wizardVerify('made-up', '123456')->assertUnprocessable()->assertJsonPath('success', false);

        Http::assertNothingSent();
        $this->assertFalse((bool) $me->refresh()->is_aadhaar_verified);
    }

    public function test_wizard_test_otp_is_refused_when_test_mode_is_off(): void
    {
        $me = $this->member();
        $this->actingAs($me, 'web');
        $requestId = $this->wizardSendOtp();

        $this->wizardVerify($requestId, '123456')->assertStatus(400);

        $this->assertFalse((bool) $me->refresh()->is_aadhaar_verified);
    }

    public function test_wizard_a_different_number_is_refused(): void
    {
        $me = $this->member();
        $this->actingAs($me, 'web');
        $requestId = $this->wizardSendOtp();

        $this->wizardVerify($requestId, self::REAL_OTP, '999988887777')->assertUnprocessable();

        $this->assertFalse((bool) $me->refresh()->is_aadhaar_verified);
    }

    public function test_wizard_real_otp_verifies_the_number_it_was_sent_to(): void
    {
        $me = $this->member();
        $this->actingAs($me, 'web');
        $requestId = $this->wizardSendOtp();

        $this->wizardVerify($requestId, self::REAL_OTP, null)
            ->assertOk()
            ->assertJsonPath('aadhaar_masked', 'XXXX XXXX 0123');

        $me->refresh();
        $this->assertTrue((bool) $me->is_aadhaar_verified);
        $this->assertSame(self::NUMBER, $me->aadhaar_number);
    }

    public function test_wizard_test_otp_works_in_test_mode(): void
    {
        config(['services.quickekyc.test_mode' => true]);
        $me = $this->member();
        $this->actingAs($me, 'web');
        $requestId = $this->wizardSendOtp();

        $this->wizardVerify($requestId, '123456')->assertOk();

        $this->assertTrue((bool) $me->refresh()->is_aadhaar_verified);
    }

    public function test_wizard_shows_the_test_hint_only_in_test_mode(): void
    {
        $this->actingAs($this->member(), 'web');

        $this->withHeaders(['Accept' => 'text/html'])
            ->get(route('profile.wizard.step', 'aadhaar'))
            ->assertOk()
            ->assertDontSee('603810');

        config(['services.quickekyc.test_mode' => true]);

        $this->withHeaders(['Accept' => 'text/html'])
            ->get(route('profile.wizard.step', 'aadhaar'))
            ->assertSee('603810');
    }

    // --- Registration ---------------------------------------------------------

    public function test_registration_verify_needs_a_request_this_browser_started(): void
    {
        $this->postJson(route('auth.aadhaar.verify-otp'), [
            'request_id' => 'made-up',
            'otp' => '123456',
            'aadhaar_number' => self::NUMBER,
        ])->assertUnprocessable();

        $this->assertNull(session('aadhaar_verified'));
    }

    public function test_registration_real_otp_puts_the_bound_number_in_the_session(): void
    {
        $requestId = $this->postJson(route('auth.aadhaar.generate-otp'), ['id_number' => self::NUMBER])
            ->assertOk()
            ->json('request_id');

        $this->postJson(route('auth.aadhaar.verify-otp'), [
            'request_id' => $requestId,
            'otp' => self::REAL_OTP,
        ])->assertOk();

        $this->assertTrue(session('aadhaar_verified'));
        $this->assertSame(self::NUMBER, session('aadhaar_number'));
    }

    // --- OTP screen -----------------------------------------------------------

    public function test_otp_screen_cannot_verify_an_account_this_browser_is_not_verifying(): void
    {
        $other = $this->member(['mobile_verified_at' => null]);

        $requestId = $this->postJson(route('auth.aadhaar.generate-otp'), ['id_number' => self::NUMBER])
            ->json('request_id');

        $this->postJson(route('otp.verify-aadhaar'), [
            'identifier' => $other->mobile,
            'request_id' => $requestId,
            'otp' => self::REAL_OTP,
        ])->assertUnprocessable();

        $this->assertGuest('web');
        $this->assertFalse((bool) $other->refresh()->is_aadhaar_verified);
    }

    public function test_otp_screen_verifies_the_account_this_browser_registered(): void
    {
        $me = $this->member(['mobile_verified_at' => null]);
        $this->withSession(['otp.identifier' => $me->mobile, 'otp.purpose' => 'registration']);

        $requestId = $this->postJson(route('auth.aadhaar.generate-otp'), ['id_number' => self::NUMBER])
            ->assertOk()
            ->json('request_id');

        $this->postJson(route('otp.verify-aadhaar'), [
            'identifier' => $me->mobile,
            'request_id' => $requestId,
            'otp' => self::REAL_OTP,
        ])->assertOk();

        $this->assertAuthenticatedAs($me, 'web');
        $this->assertSame(self::NUMBER, $me->refresh()->aadhaar_number);
    }
}
