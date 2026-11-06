<?php

namespace App\Support;

/**
 * Mobile numbers are the primary login handle, so "the same number" must mean
 * the same string in the database. Indian users type +91 98765 43210,
 * 09876543210 and 9876543210 interchangeably; without normalisation at the
 * door those become three accounts and a support ticket.
 */
class PhoneNumber
{
    public const DEFAULT_COUNTRY_CODE = '91';

    /** Normalises anything user-typed into E.164. Returns null if implausible. */
    public static function normalise(?string $input, string $countryCode = self::DEFAULT_COUNTRY_CODE): ?string
    {
        if ($input === null) {
            return null;
        }

        $digits = preg_replace('/\D+/', '', $input) ?? '';

        if ($digits === '') {
            return null;
        }

        // 0-prefixed trunk form: 09876543210
        if (str_starts_with($digits, '0')) {
            $digits = ltrim($digits, '0');
        }

        // Already carries the country code.
        if (str_starts_with($digits, $countryCode) && strlen($digits) > 10) {
            return '+'.$digits;
        }

        if (strlen($digits) < 6 || strlen($digits) > 15) {
            return null;
        }

        return '+'.$countryCode.$digits;
    }

    public static function isValid(?string $input, string $countryCode = self::DEFAULT_COUNTRY_CODE): bool
    {
        $normalised = static::normalise($input, $countryCode);

        if ($normalised === null) {
            return false;
        }

        // Indian mobile numbers are ten digits starting 6–9. Landlines and
        // short codes cannot receive an OTP, so they are rejected at the door
        // rather than failing silently at send time.
        if ($countryCode === self::DEFAULT_COUNTRY_CODE) {
            return (bool) preg_match('/^\+91[6-9]\d{9}$/', $normalised);
        }

        return true;
    }

    /** Masked for display to anyone who has not earned the full number. */
    public static function mask(?string $number): ?string
    {
        if (! $number) {
            return null;
        }

        return substr($number, 0, -7).'XXXXX'.substr($number, -2);
    }
}
