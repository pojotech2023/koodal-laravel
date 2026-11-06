<?php

namespace Tests\Unit\Support;

use App\Support\ProfileCode;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\SeedsCoreData;
use Tests\TestCase;

class ProfileCodeTest extends TestCase
{
    use RefreshDatabase;
    use SeedsCoreData;

    public function test_is_valid_accepts_the_expected_shape(): void
    {
        $this->assertTrue(ProfileCode::isValid('MAT2026000123'));
    }

    public function test_is_valid_rejects_a_wrong_prefix_or_digit_count(): void
    {
        $this->assertFalse(ProfileCode::isValid('XYZ2026000123'));
        $this->assertFalse(ProfileCode::isValid('MAT202600012'));
    }

    public function test_generate_produces_a_code_matching_its_own_validity_check(): void
    {
        $this->seedRoles();

        $code = ProfileCode::generate();

        $this->assertTrue(ProfileCode::isValid($code));
        $this->assertStringStartsWith('MAT'.now()->format('Y'), $code);
    }

    public function test_generate_never_repeats_an_existing_code(): void
    {
        $this->seedRoles();

        $first = $this->member(['profile_code' => ProfileCode::generate()]);

        $second = ProfileCode::generate();

        $this->assertNotSame($first->profile_code, $second);
    }
}
