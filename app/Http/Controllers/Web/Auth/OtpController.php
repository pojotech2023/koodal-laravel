<?php

namespace App\Http\Controllers\Web\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ResendOtpRequest;
use App\Http\Requests\Auth\VerifyOtpRequest;
use App\Models\User;
use App\Services\Auth\OtpService;
use App\Services\Verification\AadhaarOtpRequests;
use App\Support\PhoneNumber;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Verification screen shared by registration, login-recovery and mobile
 * change — {@see VerifyOtpRequest}'s `purpose` enum drives the branch, same
 * as {@see \App\Http\Controllers\Api\V1\Auth\OtpController}. The identifier
 * and purpose live in the session (set by {@see RegisterController} or
 * {@see LoginController}), never a query string this page could be linked
 * to with someone else's number in it.
 */
class OtpController extends Controller
{
    public function __construct(protected OtpService $otp) {}

    public function create(Request $request): View|RedirectResponse
    {
        $identifier = $request->session()->get('otp.identifier');
        $purpose = $request->session()->get('otp.purpose');

        // Reached cold — a bookmarked or refreshed URL after the flow already
        // finished. There is nothing left to resume it with.
        if (! $identifier || ! $purpose) {
            return redirect()->route('login');
        }

        $user = User::where('mobile', $identifier)->orWhere('email', $identifier)->first();

        return view('auth.verify-otp', [
            'identifier' => $identifier,
            'purpose' => $purpose,
            'maskedIdentifier' => str_contains($identifier, '@') ? $identifier : PhoneNumber::mask($identifier),
            'user' => $user,
        ]);
    }

    public function store(VerifyOtpRequest $request): RedirectResponse
    {
        $identifier = $request->input('identifier');
        $purpose = $request->input('purpose');

        $this->otp->verify($identifier, $purpose, $request->input('code'));

        $user = User::where('mobile', $identifier)->orWhere('email', $identifier)->firstOrFail();

        if (in_array($purpose, ['registration', 'mobile_change'], true)) {
            $user->forceFill([
                'mobile_verified_at' => now(),
                // Registration is the only place `pending` becomes `active`.
                'status' => $user->status === 'pending' ? 'active' : $user->status,
            ])->save();
        }

        $request->session()->forget(['otp.identifier', 'otp.purpose']);

        // password_reset hands back no session, same rule as the API: the
        // reset step still needs the verified code on its own, and logging
        // the browser in here would let a leaked SMS become a full session
        // takeover rather than a single password change.
        if ($purpose === 'password_reset') {
            return redirect()->route('login')
                ->with('status', 'Code verified. Contact support to complete your password reset.');
        }

        Auth::guard('web')->login($user);
        $request->session()->regenerate();

        return redirect()->route('dashboard')->with('status', 'Mobile number verified successfully.');
    }

    /**
     * Throttled on the identifier by {@see ResendOtpRequest}'s route rate
     * limiter, same as the API — an attacker rotating IPs against one number
     * is the threat an IP-keyed bucket does nothing about.
     */
    public function resend(ResendOtpRequest $request): RedirectResponse
    {
        $issued = $this->otp->issue($request->input('identifier'), $request->input('purpose'), 'sms', $request->ip());
        $this->otp->deliver($request->input('identifier'), $issued['code']);

        return back()->with('status', 'A new code has been sent.');
    }

    /**
     * Complete account verification via Aadhaar OTP on the OTP verification screen.
     *
     * Success logs this browser in, so it only works for the account this
     * browser is already verifying — the one it just registered, or logged in
     * to with the right password (the session's `otp.identifier`) — never an
     * account named in the request. The OTP must be for the Aadhaar request
     * this browser started (`auth/aadhaar/generate-otp`), and the number saved
     * is the one that request was sent for.
     */
    public function verifyAadhaar(Request $request, AadhaarOtpRequests $requests): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'identifier' => ['required', 'string'],
            'request_id' => ['required', 'string'],
            'otp' => ['required', 'string', 'size:6'],
            'aadhaar_number' => ['nullable', 'string'],
        ]);

        $fail = fn (string $message, int $status) => $request->wantsJson()
            ? response()->json(['success' => false, 'message' => $message], $status)
            : back()->withErrors(['aadhaar_otp' => $message]);

        $identifier = $request->hasSession() ? (string) $request->session()->get('otp.identifier', '') : '';

        if ($identifier === '' || ! hash_equals($identifier, $validated['identifier'])) {
            return $fail('Your verification session has expired. Please log in again.', 422);
        }

        $user = User::where('mobile', $identifier)->orWhere('email', $identifier)->firstOrFail();

        $requestId = (string) $validated['request_id'];
        $key = AadhaarOtpRequests::requestKey($requestId);
        $startedHere = hash_equals((string) $request->session()->get('aadhaar_pending_request_id', ''), $requestId);
        $pending = $startedHere ? $requests->find($key, $requestId) : null;

        if (! $pending) {
            return $fail(AadhaarOtpRequests::EXPIRED, 422);
        }

        if (! $requests->claims($pending, $validated['aadhaar_number'] ?? null)) {
            return $fail(AadhaarOtpRequests::OTHER_NUMBER, 422);
        }

        $cleanAadhaar = $pending['aadhaar'];

        if (User::where('aadhaar_number', $cleanAadhaar)->where('id', '!=', $user->id)->exists()) {
            $requests->forget($key);

            return $fail(AadhaarOtpRequests::LINKED, 422);
        }

        $result = $requests->submit($key, $pending, preg_replace('/\D/', '', $validated['otp']));

        if (! $result['success']) {
            return $fail($result['message'], $result['status']);
        }

        // Successfully verified Aadhaar: activate user and mark verified
        $user->forceFill([
            'aadhaar_number' => $cleanAadhaar,
            'is_aadhaar_verified' => true,
            'aadhaar_verified_at' => now(),
            'mobile_verified_at' => now(),
            'status' => 'active',
        ])->save();

        if ($user->profile) {
            $user->profile->update(['is_verified' => true]);
        }

        $request->session()->forget(['otp.identifier', 'otp.purpose', 'aadhaar_pending_number', 'aadhaar_pending_request_id']);
        Auth::guard('web')->login($user);
        if ($request->hasSession()) {
            $request->session()->regenerate();
        }

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Aadhaar verified successfully! ✓',
                'redirect' => route('dashboard'),
            ]);
        }

        return redirect()->route('dashboard')->with('status', 'Aadhaar verified successfully! Welcome to '.config('app.name'));
    }
}
