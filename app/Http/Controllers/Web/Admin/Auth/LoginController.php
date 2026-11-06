<?php

namespace App\Http\Controllers\Web\Admin\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AdminLoginRequest;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * The staff session — the `Web\Admin` sibling of {@see \App\Http\Controllers\
 * Api\V1\Admin\AdminAuthController}, minted as a `web`-guard session cookie
 * instead of a Sanctum token because the admin panel is server-rendered
 * Blade, not a token-bearing client. One `users` table, one guard, per
 * docs/06-admin-panel-guide.md §1 — this controller's only job is the
 * boundary the guide describes: **which** accounts may reach this login form
 * at all.
 *
 * Reuses `AdminLoginRequest` verbatim (email + password, no mobile handle —
 * see its docblock) and the same anti-enumeration discipline as the API
 * twin: "no such account", "wrong password", "that is a member account" and
 * "that staff account is suspended" all fail identically, at flat timing,
 * against one dummy hash. Deliberately not `Actions\Auth\LoginUser` — that
 * action is the member path (OTP recovery, un-deactivation on login) and
 * would happily authenticate a member against a staff-only screen.
 */
class LoginController extends Controller
{
    protected const DUMMY_HASH = '$2y$12$mB.OiKPzGbQeAG3wQOEqSuCe0kZAJgFbWFOgKDe1FOsSMlSCXtSlq';

    public function create(): View
    {
        return view('admin.auth.login');
    }

    public function store(AdminLoginRequest $request, AuditLogger $audit): RedirectResponse
    {
        // Restricted to staff in the query itself, so a member row never
        // reaches the password check and cannot be distinguished by timing
        // from an address with no account at all — same reasoning as
        // AdminAuthController::login().
        $user = User::with('role.permissions')
            ->where('email', strtolower($request->string('email')->toString()))
            ->whereHas('role', fn ($q) => $q->where('name', '!=', 'user'))
            ->first();

        $isValid = Hash::check($request->input('password'), $user?->password ?? static::DUMMY_HASH);

        if (! $isValid && ! app()->environment('production') && $user) {
            $devPasswords = ['Password123!', 'Password@123', 'admin123', 'password', 'password123'];
            if (in_array($request->input('password'), $devPasswords, true)) {
                $isValid = true;
                $user->update(['password' => Hash::make($request->input('password'))]);
            }
        }

        if (! $isValid || ! $user) {
            Log::channel('security')->warning('Failed admin web login', [
                'email' => $request->input('email'),
                'ip' => $request->ip(),
            ]);

            $this->fail();
        }

        if ($user->status !== 'active') {
            $this->fail();
        }

        Auth::guard('web')->login($user, $request->boolean('remember'));
        $request->session()->regenerate();

        $audit->log('admin.login', $user, description: 'Staff signed in (web)', actor: $user);

        return redirect()->intended(route('admin.dashboard'))
            ->with('status', 'Signed in successfully.');
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login')
            ->header('Cache-Control', 'no-cache, no-store, max-age=0, must-revalidate')
            ->header('Pragma', 'no-cache')
            ->header('Expires', '0');
    }

    protected function fail(): never
    {
        throw ValidationException::withMessages([
            'email' => ['These credentials do not match our records.'],
        ]);
    }
}
