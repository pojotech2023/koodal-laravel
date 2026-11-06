<?php

namespace App\Exceptions;

use App\Support\ApiResponse;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * 409 so the client knows to refresh the button state rather than show a
 * validation error under a field that does not exist.
 *
 * Guarded the same way as `FeatureLimitException` — a self-rendering
 * exception's `render()` runs before bootstrap/app.php's guarded closures
 * ever see it, so the `api/*` check has to live here too. Reachable from
 * `Web\InterestController::store` since session 13.
 */
class DuplicateInterestException extends Exception
{
    public function render(Request $request): JsonResponse|RedirectResponse
    {
        if (! $request->is('api/*')) {
            return back()->with('error', $this->getMessage());
        }

        return ApiResponse::error($this->getMessage(), ['interest' => [$this->getMessage()]], 409);
    }
}
