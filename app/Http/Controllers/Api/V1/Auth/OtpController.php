<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Actions\Auth\IssueToken;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ResendOtpRequest;
use App\Http\Requests\Auth\VerifyOtpRequest;
use App\Http\Resources\MembershipResource;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\Auth\OtpService;
use App\Services\Membership\FeatureGate;
use App\Support\ApiResponse;
use App\Support\Otp;
use App\Support\PhoneNumber;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class OtpController extends Controller
{
    public function __construct(protected OtpService $otp) {}

    /**
     * Verification is what activates an account and issues the first token —
     * the point at which we know the mobile number reaches this person.
     */
    public function verify(VerifyOtpRequest $request, IssueToken $issueToken, FeatureGate $features): JsonResponse
    {
        $identifier = $request->input('identifier');
        $purpose = $request->input('purpose');

        $user = $this->account($identifier)?->load(['role', 'profile', 'activeSubscription.plan']);

        $this->otp->verify($this->sentTo($identifier, $purpose, $user), $purpose, $request->input('code'));

        if (! $user) {
            throw ValidationException::withMessages([
                'identifier' => ['Account not found.'],
            ]);
        }

        if (in_array($purpose, ['registration', 'mobile_change'], true)) {
            $user->forceFill([
                'mobile_verified_at' => now(),
                // Registration is the only place `pending` becomes `active`.
                'status' => $user->status === 'pending' ? 'active' : $user->status,
            ])->save();
        }

        // password_reset hands back no token: the reset endpoint still needs
        // the code, and issuing a session here would let a leaked SMS become a
        // full login rather than a single password change.
        if ($purpose === 'password_reset') {
            return ApiResponse::success(['verified' => true], 'Code verified. You can now set a new password.');
        }

        $token = $issueToken($user, $request->input('device_name', 'mobile'));

        return ApiResponse::success([
            'token' => $token->plainTextToken,
            'token_type' => 'Bearer',
            'user' => new UserResource($user->refresh()),
            'profile' => [
                'completion_percentage' => $user->profile?->completion_percentage ?? 0,
                'approval_status' => $user->profile?->approval_status ?? 'pending',
                'is_verified' => (bool) ($user->profile?->is_verified ?? false),
            ],
            'subscription' => new MembershipResource($user->activeSubscription, $features->features($user)),
        ], 'Mobile number verified successfully');
    }

    /**
     * Throttled on the identifier rather than the IP — an attacker rotating
     * IPs against one number is the threat an IP bucket does nothing about.
     */
    public function resend(ResendOtpRequest $request): JsonResponse
    {
        $identifier = $request->input('identifier');
        $purpose = $request->input('purpose');

        // A reset or login code belongs to an account. With none there is
        // nobody to text, and the answer stays the same so it cannot be used
        // to find out which numbers are registered.
        if ($this->belongsToAccount($purpose)) {
            if ($user = $this->account($identifier)) {
                $issued = $this->otp->issue($user->mobile, $purpose, 'sms', $request->ip());
                $this->otp->deliver($user->mobile, $issued['code']);
            }

            return ApiResponse::success(['otp_expires_in' => Otp::TTL_SECONDS], 'A new code has been sent.');
        }

        $issued = $this->otp->issue($identifier, $purpose, 'sms', $request->ip());
        $this->otp->deliver($identifier, $issued['code']);

        return ApiResponse::success(
            ['otp_expires_in' => $issued['expires_in']],
            'A new code has been sent.',
        );
    }

    /**
     * Reset and login codes are issued to the account's own mobile however
     * the member found it (`PasswordResetController`, `requestOtpLogin`) — an
     * email address cannot receive an SMS. Registration and mobile-change
     * codes go to the number being verified, which is the one typed.
     */
    protected function sentTo(string $identifier, string $purpose, ?User $user): string
    {
        return $this->belongsToAccount($purpose) && $user ? $user->mobile : $identifier;
    }

    protected function belongsToAccount(string $purpose): bool
    {
        return in_array($purpose, ['password_reset', 'login'], true);
    }

    protected function account(string $identifier): ?User
    {
        $normalised = PhoneNumber::normalise($identifier);

        return User::where('mobile', $identifier)
            ->when($normalised, fn ($q) => $q->orWhere('mobile', $normalised))
            ->orWhere('email', $identifier)
            ->first();
    }
}
