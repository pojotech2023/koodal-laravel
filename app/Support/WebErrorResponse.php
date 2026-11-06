<?php

namespace App\Support;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

/**
 * The `Web\*` counterpart to ApiResponse's error envelope: what a session-guard
 * request should get back when an Action calls `abort()`.
 *
 * Why this exists at all. Every Action in this codebase guards with
 * `abort_unless(...)`/`abort_if(...)` — one line, fail fast, and correct for
 * the API, where bootstrap/app.php turns the resulting Symfony HttpException
 * into the standard JSON envelope. Since session 10 those same Actions are
 * also called by `Web\*` and `Web\Admin\*` controllers, where an HttpException
 * fell through to Laravel's stock, undecorated error page — a moderator who
 * double-clicked "Approve" lost the whole grid and their filters to a bare 409
 * page, and a member accepting an already-answered interest lost the page they
 * were on. PROGRESS.md §6 recorded that gap against five Actions; it was
 * really against all ~20 that a Blade controller can reach.
 *
 * Fixing it centrally rather than per-Action is deliberate. The alternative on
 * the table was giving each guard a self-rendering domain exception the way
 * FeatureLimitException does. That is the right shape when the failure carries
 * *data* a client acts on (FeatureLimitException carries the feature and the
 * quota; ChatNotAllowedException carries whether an upgrade would help), and
 * those stay. It is the wrong shape for "you already did this" — twenty
 * near-identical exception classes whose only job is to be a status and a
 * sentence, and a twenty-first missing one the first time somebody adds an
 * Action without reading this file.
 *
 * --- What is and is not converted --------------------------------------
 *
 * Only a **write** (POST/PUT/PATCH/DELETE) is redirected back. A GET that
 * aborts is a page that cannot be shown — `/profile/{slug}` for a hidden
 * profile, an admin route the viewer lacks the permission for — and the honest
 * answer to that is an error page, not a bounce to wherever they came from.
 *
 * Only 403, 409 and 422 are redirected. These are the three an Action raises,
 * and all three mean "the request was understood and refused for a reason the
 * user can read and act on". Everything else keeps the stock page on purpose:
 *
 *   - 404 must stay indistinguishable from a route that does not exist (the
 *     never-confirm-existence rule — see ProfileVisibility).
 *   - 419 is a CSRF/session expiry, and Laravel's "Page Expired" page already
 *     says the one useful thing: reload and try again. `back()` would loop.
 *   - 429 and 5xx are not user-actionable, and a flash on the page that just
 *     failed reads as if the action succeeded.
 */
class WebErrorResponse
{
    /**
     * Statuses an Action raises to mean "refused, here is why" — as opposed to
     * "this does not exist" or "the server is broken".
     */
    protected const FLASHABLE = [403, 409, 422];

    /**
     * Returns null to mean "not ours" — bootstrap/app.php passes that straight
     * through to Laravel's own error page, which is the correct answer for
     * every case listed in the class docblock above.
     */
    public static function for(HttpExceptionInterface $e, Request $request): JsonResponse|RedirectResponse|null
    {
        if ($request->isMethodSafe() || ! in_array($e->getStatusCode(), static::FLASHABLE, true)) {
            return null;
        }

        $message = $e->getMessage() ?: 'That action could not be completed.';

        // The admin panel's XHR layers — the photo queue's keyboard shortcuts,
        // the bulk bar — POST and read the answer rather than re-rendering the
        // page. Handing them a 302 to an HTML page would leave the card stuck
        // mid-decision with no way to know why. Same envelope
        // `PhotoModerationController::decided()` already answers those callers
        // with: one JSON shape, built in one place, wherever it is emitted.
        if ($request->wantsJson()) {
            return ApiResponse::error($message, [], $e->getStatusCode());
        }

        // `error`, not `status` and not `errors`. Not `status`, because that
        // key is the success box in <x-flash> and a refusal shown in it reads
        // as though the action went through. Not `errors`, because that is the
        // validation bag, keyed by field and rendered under the input it
        // belongs to — a 409 belongs to the request, not to a field.
        return back()->with('error', $message);
    }
}
