<?php

namespace App\Actions\Auth;

use App\Models\User;
use App\Services\Auth\OtpService;
use App\Support\PhoneNumber;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

/**
 * The single place a forgotten password is actually changed. Extracted this
 * session from `Api\V1\Auth\PasswordResetController::reset()` — which had it
 * inline, the one Action-less business rule left in the auth surface — the
 * moment a second caller (`Web\Auth\PasswordResetController`) needed the
 * exact same code+password verification together, per docs/05-website-guide.md
 * §0: "no second implementation of any business rule anywhere."
 *
 * Deliberately one step, not two: `ResetPasswordRequest` always carries
 * `identifier`, `code` AND `password` together, so the OTP is verified and
 * consumed in the same call that sets the new password. Unlike registration
 * or login recovery, there is no separate "code verified, now what" screen —
 * a leaked SMS code is only ever worth one password change, never a second
 * chance to retry the reset with a different password.
 */
class ResetPassword
{
    public function __construct(protected OtpService $otp) {}

    public function __invoke(string $identifier, string $code, string $password): void
    {
        $normalised = PhoneNumber::normalise($identifier);

        $user = User::where('mobile', $identifier)
            ->when($normalised, fn ($q) => $q->orWhere('mobile', $normalised))
            ->orWhere('email', $identifier)
            ->first();

        // Verified against the account's own mobile, not the submitted
        // identifier — someone may have started the flow with their email.
        // When $user is null this still calls verify() against the raw
        // identifier, which fails with the same "expired/invalid code"
        // message a real account would get for a wrong code (no code was
        // ever issued for an account that doesn't exist — see
        // ForgotPasswordRequest's sendResetOtp), so this never becomes an
        // account-enumeration oracle.
        $this->otp->verify($user?->mobile ?? $normalised ?? $identifier, 'password_reset', $code);

        if (! $user) {
            throw ValidationException::withMessages([
                'identifier' => ['No account found for this mobile number or email.'],
            ]);
        }

        $user->update(['password' => $password]);

        // Every token dies. A password reset is the standard response to a
        // compromise, and leaving an attacker's token alive defeats it.
        $user->tokens()->delete();

        Log::channel('security')->info('Password reset completed', [
            'user_id' => $user->id,
            'ip' => request()->ip(),
        ]);
    }
}
