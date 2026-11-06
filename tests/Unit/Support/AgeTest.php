<?php

namespace Tests\Unit\Support;

use App\Support\Age;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class AgeTest extends TestCase
{
    public function test_from_computes_age_from_date_of_birth(): void
    {
        Carbon::setTestNow('2026-08-25');

        $this->assertSame(25, Age::from(Carbon::parse('2001-01-15')));
        $this->assertSame(0, Age::from(Carbon::parse('2026-01-15')));

        Carbon::setTestNow();
    }

    public function test_from_returns_null_for_null_input(): void
    {
        $this->assertNull(Age::from(null));
    }

    public function test_max_birthdate_for_is_the_lower_bound_of_a_range(): void
    {
        Carbon::setTestNow('2026-08-25');

        // Someone turning 30 today was born exactly 30 years ago today.
        $this->assertTrue(Age::maxBirthdateFor(30)->isSameDay(Carbon::parse('1996-08-25')));

        Carbon::setTestNow();
    }

    public function test_min_birthdate_for_is_the_youngest_still_counted_as_that_age(): void
    {
        Carbon::setTestNow('2026-08-25');

        // One day short of turning 31 is still 30.
        $this->assertTrue(Age::minBirthdateFor(30)->isSameDay(Carbon::parse('1995-08-26')));

        Carbon::setTestNow();
    }

    public function test_birthdate_range_is_null_when_the_bound_is_null(): void
    {
        [$earliest, $latest] = Age::birthdateRange(null, null);

        $this->assertNull($earliest);
        $this->assertNull($latest);
    }

    public function test_birthdate_range_orders_earliest_before_latest(): void
    {
        Carbon::setTestNow('2026-08-25');

        [$earliest, $latest] = Age::birthdateRange(25, 35);

        $this->assertTrue($earliest->lessThan($latest));

        Carbon::setTestNow();
    }

    public function test_is_eligible_enforces_the_gendered_minimum_age(): void
    {
        Carbon::setTestNow('2026-08-25');

        // 19-year-old: below the male minimum (21), at/above the female minimum (18).
        $nineteen = Carbon::parse('2007-08-24');

        $this->assertFalse(Age::isEligible($nineteen, 'male'));
        $this->assertTrue(Age::isEligible($nineteen, 'female'));

        Carbon::setTestNow();
    }

    public function test_is_eligible_rejects_above_the_configured_maximum(): void
    {
        Carbon::setTestNow('2026-08-25');

        $this->assertFalse(Age::isEligible(Carbon::parse('1900-01-01'), 'male'));

        Carbon::setTestNow();
    }

    public function test_is_eligible_returns_false_for_null_date_of_birth(): void
    {
        $this->assertFalse(Age::isEligible(null, 'male'));
    }
}
