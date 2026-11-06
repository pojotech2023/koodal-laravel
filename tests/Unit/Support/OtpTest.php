<?php

namespace Tests\Unit\Support;

use App\Support\Otp;
use Tests\TestCase;

class OtpTest extends TestCase
{
    public function test_generate_produces_a_zero_padded_code_of_the_requested_length(): void
    {
        $code = Otp::generate(6);

        $this->assertSame(6, strlen($code));
        $this->assertMatchesRegularExpression('/^\d{6}$/', $code);
    }

    public function test_hash_and_matches_round_trip(): void
    {
        $hash = Otp::hash('123456');

        $this->assertTrue(Otp::matches('123456', $hash));
        $this->assertFalse(Otp::matches('654321', $hash));
    }

    public function test_hash_is_not_the_plain_code(): void
    {
        $this->assertNotSame('123456', Otp::hash('123456'));
    }

    public function test_fixed_code_is_used_outside_production(): void
    {
        // APP_ENV=testing per phpunit.xml.
        $this->assertSame('123456', Otp::fixedCodeForNonProduction());
    }

    public function test_fixed_code_is_null_in_production(): void
    {
        $this->app->detectEnvironment(fn () => 'production');

        $this->assertNull(Otp::fixedCodeForNonProduction());

        $this->app->detectEnvironment(fn () => 'testing');
    }
}
