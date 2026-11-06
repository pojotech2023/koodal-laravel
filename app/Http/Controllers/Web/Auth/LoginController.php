<?php

namespace App\Http\Controllers\Web\Auth;

use App\Actions\Auth\LoginUser;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function create(): View
    {
        return view('auth.login');
    }

    public function store(LoginRequest $request, LoginUser $loginUser): RedirectResponse
    {
        $credentials = $request->credentials();

        try {
            $result = $loginUser(
                $credentials['column'],
                $credentials['value'],
                $request->input('password'),
                $request->deviceName(),
            );
        } catch (ValidationException $e) {
            // LoginUser signals "right password, unverified mobile" via the
            // `otp` error key rather than a generic credentials failure — the
            // API client routes to its OTP screen on this signal, so the
            // website does too, instead of just bouncing the form.
            if (array_key_exists('otp', $e->errors())) {
                return $this->routeToOtpVerification($request, $credentials);
            }

            throw $e;
        }

        // LoginUser issues a Sanctum token for API callers; the website has
        // no bearer-token client to hand it to and establishes the session
        // guard directly instead. `Auth::guard('web')` is not optional here —
        // the bare `Auth` facade targets the `sanctum` default guard (see
        // PROGRESS.md §6) and would silently fail to set the session cookie.
        Auth::guard('web')->login($result['user'], $request->boolean('remember'));

        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'))
            ->with('status', 'Logged in successfully.');
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home')
            ->header('Cache-Control', 'no-cache, no-store, max-age=0, must-revalidate')
            ->header('Pragma', 'no-cache')
            ->header('Expires', '0');
    }

    /**
     * The pending OTP is always under purpose `registration` — it is the only
     * purpose that both requires `mobile_verified_at` to be null and, on
     * success, sets it. The identifier has to be re-resolved to the account's
     * actual mobile number rather than reusing whatever the member typed:
     * `OtpService::deliver` sends to `$identifier` as a phone number, and a
     * member who logged in with their email would otherwise get an SMS
     * "sent" to an email address.
     */
    protected function routeToOtpVerification(Request $request, array $credentials): RedirectResponse
    {
        $mobile = User::where($credentials['column'], $credentials['value'])->value('mobile');

        $request->session()->put('otp.identifier', $mobile);
        $request->session()->put('otp.purpose', 'registration');

        return redirect()->route('otp.verify')
            ->with('status', 'Verify your mobile number to continue.');
    }
}
