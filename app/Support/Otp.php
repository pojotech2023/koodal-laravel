<?php

namespace App\Support;

use Illuminate\Support\Facades\Hash;

/**
 * OTP primitives. A one-time code is a credential: it is stored hashed,
 * compared in constant time, and never logged outside the local `log` driver.
 */
class Otp
{
    public const LENGTH = 6;

    public const TTL_SECONDS = 300;

    /**
     * random_int, not rand or mt_rand. A predictable OTP is no OTP at all, and
     * the non-cryptographic generators are seeded predictably enough to matter.
     */
    public static function generate(int $length = self::LENGTH): string
    {
        $max = (10 ** $length) - 1;

        return str_pad((string) random_int(0, $max), $length, '0', STR_PAD_LEFT);
    }

    public static function hash(string $code): string
    {
        return Hash::make($code);
    }

    public static function matches(string $code, string $hash): bool
    {
        return Hash::check($code, $hash);
    }

    /**
     * Fixed test code in non-production so QA and automated end-to-end runs do
     * not need a live SMS gateway. Guarded on the environment, never on a
     * config flag someone could set in production by accident.
     */
    public static function fixedCodeForNonProduction(): ?string
    {
        if (config('services.sms.driver', 'log') !== 'log') {
            return null;
        }

        return app()->environment('production') ? null : '123456';
    }
}
