<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Coarse gate for whole sections: `->middleware('role:super_admin,admin')`.
 *
 * Fine-grained checks use permissions through Policies. This only keeps a
 * support agent out of the payments area entirely; it cannot know whether
 * *this* admin may act on *this* record.
 */
class EnsureRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        abort_unless($user && $user->hasRole(...$roles), 403, 'You do not have access to this area.');

        return $next($request);
    }
}
