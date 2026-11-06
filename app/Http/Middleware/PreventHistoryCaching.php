<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Without this, the browser's back/forward cache (bfcache) restores a fully
 * rendered, still-interactive copy of the last authenticated page after
 * logout — no request ever reaches the server, so the invalidated session
 * never gets a chance to redirect to the login screen. The page looks exactly
 * as it did while signed in, and its forms still carry the CSRF token from
 * that now-dead session, so acting on it (e.g. clicking "Log out" again)
 * fails with a 419 instead of doing anything useful.
 *
 * `no-store` is the one directive that reliably disables bfcache in every
 * current browser; `Pragma`/`Expires` are the HTTP/1.0 fallback. Attached to
 * both the member area's and the admin panel's `[auth:web, active.web]`
 * route groups — anywhere session state decides what the page shows.
 */
class PreventHistoryCaching
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->set('Cache-Control', 'no-cache, no-store, max-age=0, must-revalidate, private');
        $response->headers->set('Pragma', 'no-cache');
        $response->headers->set('Expires', '0');

        return $response;
    }
}
