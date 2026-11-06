<?php

namespace Tests\Feature\Api\V1\Auth;

use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\Concerns\SeedsCoreData;
use Tests\TestCase;

class RegistrationAndOtpTest extends TestCase
{
    use RefreshDatabase;
    use SeedsCoreData;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedRoles();

        // Password::uncompromised() calls the real HIBP k-anonymity API.
        // Faking it keeps the suite hermetic and fast rather than depending
        // on outbound network access every run.
        Http::fake(['api.pwnedpasswords.com/*' => Http::response('', 200)]);
    }

    protected function validRegistrationPayload(array $overrides = []): array
    {
        // No `email` by default: it is optional, and RegisterRequest validates
        // it with a real `dns` check, which would make this suite depend on
        // outbound DNS resolution for a field the flow does not exercise.
        return array_merge([
            'name' => 'Asha Kumar',
            'mobile' => '9876543210',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'gender' => 'female',
            'date_of_birth' => now()->subYears(25)->toDateString(),
            'profile_created_by' => 'self',
        ], $overrides);
    }

    public function test_registering_creates_a_pending_account_and_issues_no_token(): void
    {
        $response = $this->postJson('/api/v1/auth/register', $this->validRegistrationPayload());

        $this->assertEnvelope($response);
        $response->assertCreated();
        $response->assertJsonPath('data.otp_required', true);
        $response->assertJsonMissingPath('data.token');

        $user = User::where('mobile', '+919876543210')->firstOrFail();

        $this->assertSame('pending', $user->status);
        $this->assertNull($user->mobile_verified_at);
        $this->assertNotNull(UserProfile::where('user_id', $user->id)->first());
    }

    public function test_registering_normalises_whatever_mobile_shape_the_client_sent(): void
    {
        $this->postJson('/api/v1/auth/register', $this->validRegistrationPayload(['mobile' => '09876543210']));

        $this->assertDatabaseHas('users', ['mobile' => '+919876543210']);
    }

    public function test_registering_rejects_a_duplicate_mobile_number(): void
    {
        $this->postJson('/api/v1/auth/register', $this->validRegistrationPayload());

        $response = $this->postJson('/api/v1/auth/register', $this->validRegistrationPayload());

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('mobile');
    }

    public function test_registering_under_the_legal_minimum_age_is_rejected(): void
    {
        // Legal minimum for a female registrant is 18; 16 must fail.
        $response = $this->postJson('/api/v1/auth/register', $this->validRegistrationPayload([
            'date_of_birth' => now()->subYears(16)->toDateString(),
        ]));

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('date_of_birth');
    }

    public function test_the_full_registration_to_login_journey(): void
    {
        $this->postJson('/api/v1/auth/register', $this->validRegistrationPayload());

        $user = User::where('mobile', '+919876543210')->firstOrFail();

        // Fixed OTP outside production (App\Support\Otp::fixedCodeForNonProduction).
        $verify = $this->postJson('/api/v1/auth/verify-otp', [
            'identifier' => '+919876543210',
            'purpose' => 'registration',
            'code' => '123456',
        ]);

        $this->assertEnvelope($verify);
        $verify->assertOk();
        $verify->assertJsonStructure(['data' => ['token', 'token_type', 'user']]);

        $user->refresh();
        $this->assertSame('active', $user->status);
        $this->assertNotNull($user->mobile_verified_at);

        // Now the password login path, which the just-verified account should pass.
        $login = $this->postJson('/api/v1/auth/login', [
            'identifier' => '+919876543210',
            'password' => 'Password123!',
        ]);

        $this->assertEnvelope($login);
        $login->assertOk();
        $login->assertJsonStructure(['data' => ['token', 'token_type']]);
    }

    public function test_verify_otp_rejects_an_incorrect_code(): void
    {
        $this->postJson('/api/v1/auth/register', $this->validRegistrationPayload());

        $response = $this->postJson('/api/v1/auth/verify-otp', [
            'identifier' => '+919876543210',
            'purpose' => 'registration',
            'code' => '000000',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('otp');
    }

    public function test_login_before_mobile_verification_is_refused(): void
    {
        $this->postJson('/api/v1/auth/register', $this->validRegistrationPayload());

        $response = $this->postJson('/api/v1/auth/login', [
            'identifier' => '+919876543210',
            'password' => 'Password123!',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('otp');
    }

    public function test_login_with_a_wrong_password_gives_a_generic_error_not_an_enumeration_hint(): void
    {
        $this->postJson('/api/v1/auth/register', $this->validRegistrationPayload());
        $user = User::where('mobile', '+919876543210')->firstOrFail();
        $user->forceFill(['mobile_verified_at' => now(), 'status' => 'active'])->save();

        $wrongPassword = $this->postJson('/api/v1/auth/login', [
            'identifier' => '+919876543210',
            'password' => 'NotThePassword1',
        ]);

        $noSuchAccount = $this->postJson('/api/v1/auth/login', [
            'identifier' => '+919999999999',
            'password' => 'NotThePassword1',
        ]);

        $wrongPassword->assertStatus(422);
        $noSuchAccount->assertStatus(422);
        $this->assertSame(
            $wrongPassword->json('message') ?? $wrongPassword->json('errors.identifier.0'),
            $noSuchAccount->json('message') ?? $noSuchAccount->json('errors.identifier.0'),
        );
        $this->assertSame(
            'Incorrect mobile number or password. Please check and try again.',
            $wrongPassword->json('errors.identifier.0'),
        );
        $this->assertSame($wrongPassword->json('errors'), $noSuchAccount->json('errors'));
    }

    public function test_login_with_an_email_names_the_email_in_its_error(): void
    {
        $this->postJson('/api/v1/auth/login', [
            'identifier' => 'nobody@example.com',
            'password' => 'NotThePassword1',
        ])
            ->assertStatus(422)
            ->assertJsonPath('errors.identifier.0', 'Incorrect email or password. Please check and try again.');
    }
}
