<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

/**
 * Keeps `last_active_at` fresh — it powers the "recently active" sort, the
 * dormancy multiplier in match scoring, and the presence fallback.
 *
 * Throttled through the cache to one write per user per five minutes. Writing
 * on every request would put an UPDATE on the users table in front of every
 * single API call for no extra precision.
 */
class RecordUserActivity
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if ($user = $request->user()) {
            Cache::remember("activity:{$user->id}", 300, function () use ($user) {
                $user->forceFill(['last_active_at' => now()])->saveQuietly();

                return true;
            });
        }

        return $response;
    }
}
