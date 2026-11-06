<?php

namespace App\Support;

use Illuminate\Support\Carbon;

/**
 * Age is always derived, never stored — a stored age is wrong within a year
 * and silently corrupts every search that filters on it.
 *
 * Filters convert to date-of-birth boundaries so MySQL uses the index on
 * users.date_of_birth. Computing YEAR(NOW()) - YEAR(dob) in the WHERE clause
 * would make the index unusable and turn search into a table scan.
 */
class Age
{
    public static function from(?Carbon $dateOfBirth): ?int
    {
        return $dateOfBirth?->age;
    }

    /** Oldest date of birth still counted as `$age` — the lower bound of a range. */
    public static function maxBirthdateFor(int $age): Carbon
    {
        return Carbon::today()->subYears($age);
    }

    /** Youngest date of birth still counted as `$age`. */
    public static function minBirthdateFor(int $age): Carbon
    {
        return Carbon::today()->subYears($age + 1)->addDay();
    }

    /**
     * @return array{0: Carbon|null, 1: Carbon|null} [earliest dob, latest dob]
     */
    public static function birthdateRange(?int $minAge, ?int $maxAge): array
    {
        return [
            $maxAge !== null ? static::minBirthdateFor($maxAge) : null,
            $minAge !== null ? static::maxBirthdateFor($minAge) : null,
        ];
    }

    public static function isEligible(?Carbon $dateOfBirth, string $gender): bool
    {
        if ($dateOfBirth === null) {
            return false;
        }

        $age = $dateOfBirth->age;
        $min = config("matrimony.profile.min_age.{$gender}", 21);

        return $age >= $min && $age <= config('matrimony.profile.max_age', 75);
    }
}
