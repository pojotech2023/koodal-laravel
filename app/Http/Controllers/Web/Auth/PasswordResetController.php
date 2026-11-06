<?php

namespace App\Http\Controllers\Web\Auth;

use App\Actions\Auth\ResetPassword;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Models\User;
use App\Services\Auth\OtpService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * The `Web\*` sibling of `Api\V1\Auth\PasswordResetController` — same two
 * steps (request a code, then submit code + new password together), the
 * same shared `ForgotPasswordRequest`/`ResetPasswordRequest`, and now the
 * same `ResetPassword` Action both front doors call.
 *
 * Deliberately NOT routed through the generic `otp.verify` screen
 * `Web\Auth\OtpController` renders for registration and login recovery:
 * `ResetPasswordRequest` always carries `code` and `password` in the same
 * submission — the API has never split "verify the code" and "set the
 * password" into two calls — so a Blade-only two-step version of this flow
 * would be a second, divergent shape for one rule. `Web\Auth\
 * OtpController::store`'s `password_reset` branch stays exactly as
 * unreachable from the UI as session 11 left it; this controller does not
 * use it. See PROGRESS.md §6.
 */
class PasswordResetController extends Controller
{
    public function __construct(protected OtpService $otp) {}

    public function create(): View
    {
        return view('auth.forgot-password');
    }

    /**
     * Always the same response, whether or not the account exists — a
     * distinguishable one here would tell an attacker exactly which mobile
     * numbers are registered on a matrimonial site.
     */
    public function sendResetOtp(ForgotPasswordRequest $request): RedirectResponse
    {
        $identifier = $request->input('identifier');
        $issuedCode = null;

        if ($user = User::where('mobile', $identifier)->orWhere('email', $identifier)->first()) {
            $issued = $this->otp->issue($user->mobile, 'password_reset', 'sms', $request->ip());
            $issuedCode = $issued['code'] ?? null;
            $this->otp->deliver($user->mobile, $issuedCode);
        }

        // The identifier the member typed, not the account's own mobile —
        // ResetPasswordRequest re-derives the account the same way ResetPassword
        // does, so what's needed here is only enough to render the next form
        // and to resend, never proof an account exists.
        $request->session()->put('password_reset.identifier', $identifier);

        $statusMsg = 'If an account exists, a reset code has been sent.';
        if (! app()->environment('production') && $issuedCode) {
            $statusMsg .= " (Testing code: {$issuedCode})";
        }

        return redirect()->route('password.reset')
            ->with('status', $statusMsg);
    }

    /**
     * The identifier lives in the session, same rule as `Web\Auth\
     * OtpController` — never a query string this page could be linked to
     * with someone else's number in it.
     */
    public function edit(Request $request): View|RedirectResponse
    {
        $identifier = $request->session()->get('password_reset.identifier');

        if (! $identifier) {
            return redirect()->route('password.forgot');
        }

        return view('auth.reset-password', ['identifier' => $identifier]);
    }

    public function update(ResetPasswordRequest $request, ResetPassword $resetPassword): RedirectResponse
    {
        $resetPassword($request->input('identifier'), $request->input('code'), $request->input('password'));

        $request->session()->forget('password_reset.identifier');

        return redirect()->route('login')->with('status', 'Password reset successfully. Please log in.');
    }
}
