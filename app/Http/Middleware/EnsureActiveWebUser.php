<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * The `web` sibling of {@see EnsureActiveUser}.
 *
 * A suspended or deactivated member holding a valid session cookie must not
 * keep browsing the member area, same rule as the API — but a browser needs a
 * redirect with a flashed message, never a JSON body. Also ends the session
 * outright rather than merely blocking the one request: leaving it live would
 * let the member navigate straight back to whichever page they were on.
 *
 * Attached to both the member area and the admin panel's `auth:web` group
 * (docs/06-admin-panel-guide.md §1 — one guard, no second auth system), so a
 * suspended staff account is signed out here too. The two front doors have
 * different homes to return to, same reasoning as `bootstrap/app.php`'s
 * `redirectGuestsTo`/`redirectUsersTo` closures.
 */
class EnsureActiveWebUser
{
    public function handle(Request $request, Closure $next): RedirectResponse|Response
    {
        $user = Auth::guard('web')->user();

        if ($user?->status === 'suspended') {
            return $this->signOut($request, 'Your account has been suspended. Please contact support.');
        }

        if ($user?->status === 'deactivated') {
            return $this->signOut($request, 'Your account is deactivated. Log in again to reactivate it.');
        }

        return $next($request);
    }

    protected function signOut(Request $request, string $message): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        $loginRoute = $request->is('admin', 'admin/*') ? 'admin.login' : 'login';

        return redirect()->route($loginRoute)->with('status', $message);
    }
}
