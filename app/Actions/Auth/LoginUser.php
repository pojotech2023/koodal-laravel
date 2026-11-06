<?php

namespace App\Actions\Auth;

use App\Models\User;
use App\Support\PhoneNumber;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

/**
 * Password login.
 *
 * The failure message is identical whether the account is missing or the
 * password is wrong, and a dummy hash is computed for a missing account so the
 * two paths take the same time. Both details exist to stop this endpoint being
 * used to enumerate who has an account here — which on a matrimonial site is
 * itself sensitive information.
 */
class LoginUser
{
    /** Bcrypt hash of a value nothing can match; keeps timing flat. */
    protected const DUMMY_HASH = '$2y$12$mB.OiKPzGbQeAG3wQOEqSuCe0kZAJgFbWFOgKDe1FOsSMlSCXtSlq';

    public function __construct(protected IssueToken $issueToken) {}

    /**
     * @return array{user: User, token: string}
     */
    public function __invoke(string $column, string $value, string $password, string $deviceName): array
    {
        $norm = ($column === 'mobile') ? PhoneNumber::normalise($value) : null;

        $user = User::with(['role', 'profile', 'activeSubscription.plan'])
            ->where($column, $value)
            ->when($norm, fn ($q) => $q->orWhere('mobile', $norm))
            ->first();

        if (! Hash::check($password, $user?->password ?? static::DUMMY_HASH) || ! $user) {
            Log::channel('security')->info('Failed login attempt', ['identifier' => $value, 'ip' => request()->ip()]);

            // Says what to check without saying which of the two was wrong.
            $this->fail($column === 'email'
                ? 'Incorrect email or password. Please check and try again.'
                : 'Incorrect mobile number or password. Please check and try again.');
        }

        if (in_array($user->status, ['suspended', 'deleted'], true)) {
            $this->fail('Your account has been suspended. Please contact support.');
        }

        // An unverified mobile is a recoverable state, not a rejection — the
        // client is told to route to the OTP screen rather than the login form.
        if ($user->mobile_verified_at === null && config('matrimony.profile.approval_required')) {
            throw ValidationException::withMessages([
                'otp' => ['Verify your mobile number to continue.'],
            ])->status(422);
        }

        // Logging in reverses a self-deactivation. Making people call support
        // to undo their own choice is hostile.
        if ($user->status === 'deactivated') {
            $user->update(['status' => 'active']);
        }

        $token = ($this->issueToken)($user, $deviceName);

        return ['user' => $user->refresh(), 'token' => $token->plainTextToken];
    }

    protected function fail(string $message): never
    {
        throw ValidationException::withMessages(['identifier' => [$message]]);
    }
}
