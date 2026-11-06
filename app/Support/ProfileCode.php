<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * The public identifier users quote to support and search for.
 *
 * Generated from a sequence rather than a random string so it is short enough
 * to read over the phone, and never reused — even after a deletion — because a
 * recycled code would resurface in someone's old screenshots and chat history
 * pointing at a different person.
 */
class ProfileCode
{
    public const PREFIX = 'MAT';

    public static function generate(): string
    {
        $year = now()->format('Y');

        // withDeleted / withTrashed is deliberate: the sequence must advance
        // past deleted accounts, not step back into them.
        $sequence = (int) DB::table('users')->max('id') + 1;

        do {
            $code = sprintf('%s%s%06d', static::PREFIX, $year, $sequence);
            $sequence++;
        } while (User::withTrashed()->where('profile_code', $code)->exists());

        return $code;
    }

    public static function isValid(string $code): bool
    {
        return (bool) preg_match('/^'.static::PREFIX.'\d{10}$/', $code);
    }
}
