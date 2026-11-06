<?php

namespace Tests\Unit\Support;

use App\Support\PhoneNumber;
use Tests\TestCase;

class PhoneNumberTest extends TestCase
{
    public function test_normalise_accepts_bare_ten_digit_numbers(): void
    {
        $this->assertSame('+919876543210', PhoneNumber::normalise('9876543210'));
    }

    public function test_normalise_strips_the_trunk_zero(): void
    {
        $this->assertSame('+919876543210', PhoneNumber::normalise('09876543210'));
    }

    public function test_normalise_accepts_a_number_already_carrying_the_country_code(): void
    {
        $this->assertSame('+919876543210', PhoneNumber::normalise('+91 98765 43210'));
        $this->assertSame('+919876543210', PhoneNumber::normalise('919876543210'));
    }

    public function test_normalise_returns_null_for_null_or_empty_input(): void
    {
        $this->assertNull(PhoneNumber::normalise(null));
        $this->assertNull(PhoneNumber::normalise('   '));
    }

    public function test_normalise_rejects_implausibly_short_or_long_digit_strings(): void
    {
        $this->assertNull(PhoneNumber::normalise('123'));
        $this->assertNull(PhoneNumber::normalise(str_repeat('9', 20)));
    }

    public function test_is_valid_requires_an_indian_mobile_prefix_of_6_to_9(): void
    {
        $this->assertTrue(PhoneNumber::isValid('9876543210'));
        $this->assertTrue(PhoneNumber::isValid('6123456789'));
        // Landlines / short codes start below 6 and cannot receive an SMS OTP.
        $this->assertFalse(PhoneNumber::isValid('5876543210'));
        $this->assertFalse(PhoneNumber::isValid('1876543210'));
    }

    public function test_is_valid_rejects_wrong_length(): void
    {
        $this->assertFalse(PhoneNumber::isValid('98765'));
    }

    public function test_mask_hides_the_middle_of_the_number(): void
    {
        // substr(0, -7) . 'XXXXX' . substr(-2): first 6 chars and last 2 kept.
        $this->assertSame('+91987XXXXX10', PhoneNumber::mask('+919876543210'));
    }

    public function test_mask_returns_null_for_null_input(): void
    {
        $this->assertNull(PhoneNumber::mask(null));
    }
}
