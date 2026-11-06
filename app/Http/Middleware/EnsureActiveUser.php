<?php

namespace App\Http\Middleware;

use App\Support\ApiResponse;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * A suspended user holding a valid token must not keep browsing. Checked per
 * request rather than at login, because suspension happens while people are
 * already logged in — that is the entire point of suspension.
 */
class EnsureActiveUser
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user?->status === 'suspended') {
            return ApiResponse::error(
                'Your account has been suspended. Please contact support.',
                ['account_suspended' => true, 'reason' => $user->suspension_reason],
                403,
            );
        }

        if ($user?->status === 'deactivated') {
            return ApiResponse::error(
                'Your account is deactivated. Reactivate it to continue.',
                ['account_deactivated' => true],
                403,
            );
        }

        return $next($request);
    }
}
