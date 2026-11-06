<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Actions\Auth\ResetPassword;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Models\User;
use App\Services\Auth\OtpService;
use App\Support\ApiResponse;
use App\Support\PhoneNumber;
use Illuminate\Http\JsonResponse;

class PasswordResetController extends Controller
{
    public function __construct(protected OtpService $otp) {}

    /**
     * Always the same response, whether or not the account exists. A "no
     * account found" message here tells an attacker exactly which numbers are
     * registered on a matrimonial site.
     */
    public function sendResetOtp(ForgotPasswordRequest $request): JsonResponse
    {
        $identifier = $request->input('identifier');
        $issuedCode = null;

        if ($user = $this->find($identifier)) {
            $issued = $this->otp->issue($user->mobile, 'password_reset', 'sms', $request->ip());
            $issuedCode = $issued['code'] ?? null;
            $this->otp->deliver($user->mobile, $issuedCode);
        }

        return ApiResponse::success(
            array_filter([
                'otp_expires_in' => 300,
                // Echoed as typed. The account's own mobile is never sent: for
                // an email it would hand anyone that member's phone number,
                // and only for accounts that exist.
                'identifier' => $identifier,
                'code' =>(! app()->environment('production') || config('app.debug')) ? $issuedCode : null,
            ]),
            'If an account exists, a reset code has been sent.',
        );
    }

    public function reset(ResetPasswordRequest $request, ResetPassword $resetPassword): JsonResponse
    {
        $resetPassword($request->input('identifier'), $request->input('code'), $request->input('password'));

        return ApiResponse::success(null, 'Password reset successfully. Please log in.');
    }

    protected function find(string $identifier): ?User
    {
        $normalised = PhoneNumber::normalise($identifier);

        return User::where('mobile', $identifier)
            ->when($normalised, fn ($q) => $q->orWhere('mobile', $normalised))
            ->orWhere('email', $identifier)
            ->first();
    }
}
