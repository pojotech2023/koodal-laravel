<?php

namespace App\Exceptions;

use App\Support\ApiResponse;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * 422, not 400: the request was well-formed, the assertion it made was false.
 * The message is deliberately vague — telling a caller *which* check failed
 * (signature, capture, amount) is a probing oracle.
 *
 * Guarded the same way as `FeatureLimitException` — a self-rendering
 * exception's `render()` runs before bootstrap/app.php's guarded closures
 * ever see it, so the `api/*` check has to live here too. Not yet reachable
 * from any `Web\*` route (checkout is unbuilt — PROGRESS.md §4); fixed ahead
 * of that session so it isn't rediscovered the same way
 * `FeatureLimitException` was this session.
 */
class PaymentVerificationException extends Exception
{
    public function render(Request $request): JsonResponse|RedirectResponse
    {
        if (! $request->is('api/*')) {
            return back()->with('status', $this->getMessage());
        }

        return ApiResponse::error($this->getMessage(), ['payment' => [$this->getMessage()]], 422);
    }
}
