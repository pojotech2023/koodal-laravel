<?php

use App\Http\Middleware\EnsureActiveUser;
use App\Http\Middleware\EnsureActiveWebUser;
use App\Http\Middleware\EnsureRole;
use App\Http\Middleware\PreventHistoryCaching;
use App\Http\Middleware\RecordUserActivity;
use App\Support\ApiResponse;
use App\Support\WebErrorResponse;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\Http\Middleware\CheckAbilities;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;

return Application::configure(basePath: dirname(__DIR__))
    // EventServiceProvider's $listen is the map of who hears what. Laravel also
    // scans app/Listeners unless told not to here (that provider's
    // shouldDiscoverEvents() does not reach this scan), which ran every listener
    // twice: two notifications, two pushes, two receipts.
    ->withEvents(discover: false)
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    // NOT passed as `channels:` to withRouting() above: that shortcut calls
    // Broadcast::routes() with NO attributes, which registers its own
    // `/broadcasting/auth` under the bare `web` middleware group — no
    // `auth:web`, no `active.web` — and it silently wins over any route
    // routes/web.php tries to register at that same URI itself. Found by
    // actually booting this app for the first time (session 20; PROGRESS.md
    // §6's "no PHP available to confirm" note): every request to
    // `broadcasting.auth`, member or guest alike, fell through to the app's
    // *default* auth guard — `sanctum`, per config/auth.php, not `web` — so
    // a browser session with no bearer token always resolved to no user and
    // always got a 403, regardless of whether the caller was actually a
    // member of the channel. Calling withBroadcasting() explicitly, with the
    // right guard, is what routes/web.php's comment before session 20 wanted
    // to do but had no way to verify.
    ->withBroadcasting(
        __DIR__.'/../routes/channels.php',
        ['as' => 'broadcasting.auth', 'middleware' => ['web', 'auth:web', 'active.web']],
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'role' => EnsureRole::class,
            'active.user' => EnsureActiveUser::class,
            'active.web' => EnsureActiveWebUser::class,
            'record.activity' => RecordUserActivity::class,
            'no.cache' => PreventHistoryCaching::class,
            // Never auto-registered by Sanctum itself — Laravel 11's
            // bootstrap/app.php has no default aliases at all, and every
            // `abilities:user`/`abilities:admin` route in
            // routes/api_v1_user.php and routes/api_v1_admin.php was
            // resolving this as a literal (nonexistent) class name until a
            // test actually hit one of those routes. That was effectively
            // the entire authenticated API, member and admin alike.
            'abilities' => CheckAbilities::class,
        ]);

        // The webhook's authenticity comes from its HMAC signature, which the
        // controller verifies before touching anything. CSRF is meaningless
        // for a server-to-server POST, and logout endpoints should cleanly
        // terminate sessions without 419 token mismatch errors.
        $middleware->validateCsrfTokens(except: ['api/v1/webhooks/*', 'admin/logout', 'logout']);

        // Laravel 11 replaced the old `RouteServiceProvider::HOME` constant
        // with these two explicit targets. Left unconfigured, `auth`/`guest`
        // fall back to guessing a `login`/`dashboard` named route exists —
        // `auth:web` is guaranteed to (routes/web.php names it `login`), but
        // being explicit here means a Blade route file that's missing or
        // renames it fails loudly in review, not as a silent 500 on first
        // unauthenticated page hit.
        //
        // Both targets are closures, not strings, because the admin surface
        // (session 10+) shares this one `web` guard with the member site but
        // must never bounce a signed-out staff visit to the member `/login`
        // form or a signed-in staff member back to the public homepage — see
        // docs/06-admin-panel-guide.md §1. `is('admin', 'admin/*')` covers
        // `/admin` itself and everything under it.
        $middleware->redirectGuestsTo(
            fn (Request $request) => $request->is('admin', 'admin/*') ? '/admin/login' : '/login',
        );
        $middleware->redirectUsersTo(
            fn (Request $request) => $request->is('admin', 'admin/*') ? '/admin' : '/',
        );
    })
    ->withExceptions(function (Exceptions $exceptions) {
        /*
        |----------------------------------------------------------------------
        | One envelope, everywhere — for API routes only
        |----------------------------------------------------------------------
        | Three clients parse this shape. Mapping exceptions centrally is what
        | stops a stray 500 with an HTML body from crashing a mobile build that
        | shipped months ago.
        |
        | Every renderer below is guarded with `! $request->is('api/*')` before
        | it touches ApiResponse. `routes/web.php` shares this same exception
        | pipeline, and a Blade form's failed validation or an unauthenticated
        | page visit must fall through to Laravel's default behaviour — redirect
        | back with $errors, or redirect to `login` — not a raw JSON body. See
        | docs/05-website-guide.md §4 and PROGRESS.md §6.
        |
        | The HttpExceptionInterface renderer is the one exception to "web
        | falls through untouched": Actions abort() on both front doors, and a
        | stock error page is the wrong answer to a refused form post. It hands
        | those to App\Support\WebErrorResponse, which still returns null —
        | i.e. falls through — for everything that really is a page.
        */

        $exceptions->render(function (ValidationException $e, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            return ApiResponse::error('Validation failed', $e->errors(), 422);
        });

        $exceptions->render(function (AuthenticationException $e, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            return ApiResponse::error(ApiResponse::defaultMessage(401), [], 401);
        });

        $exceptions->render(function (AuthorizationException $e, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            // Laravel's stock sentence reads like an error code; a policy's
            // own message (e.g. "You have blocked this member") is kept.
            $message = $e->getMessage();

            return ApiResponse::error(
                $message && $message !== 'This action is unauthorized.' ? $message : ApiResponse::defaultMessage(403),
                [],
                403,
            );
        });

        // Deliberately identical to a genuine 404: a blocked or hidden profile
        // must not be distinguishable from one that never existed.
        $exceptions->render(function (ModelNotFoundException|NotFoundHttpException $e, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            return ApiResponse::error('Not found.', [], 404);
        });

        $exceptions->render(function (TooManyRequestsHttpException $e, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            return ApiResponse::error(ApiResponse::defaultMessage(429), [], 429);
        });

        $exceptions->render(function (\Illuminate\Session\TokenMismatchException $e, Request $request) {
            if ($request->is('api/*')) {
                return ApiResponse::error('Session expired.', [], 419);
            }

            $request->session()->regenerateToken();

            if ($request->is('register')) {
                return redirect()->route('register')
                    ->withInput($request->except(['password', 'password_confirmation', '_token']))
                    ->withErrors(['password' => 'Your session expired. Please re-enter your password and submit again.']);
            }

            if ($request->is('admin', 'admin/*')) {
                return redirect()->route('admin.login')
                    ->withInput($request->except(['password', '_token']))
                    ->withErrors(['email' => 'Your session has expired. Please sign in again.']);
            }

            if ($request->is('login')) {
                return redirect()->route('login')
                    ->withInput($request->except(['password', '_token']))
                    ->withErrors(['identifier' => 'Your session has expired. Please sign in again.']);
            }

            return back()
                ->withInput($request->except(['password', 'password_confirmation', '_token']))
                ->withErrors(['message' => 'Your session expired. Please try again.']);
        });

        // The one renderer that does NOT simply bail out on a web request.
        // Every Action in this codebase guards with `abort_unless(...)`, and
        // since session 10 those Actions are called by `Web\*` controllers as
        // well as by the API — so a member double-submitting a form, or a
        // moderator double-clicking Approve, was losing their page to Laravel's
        // stock 409 error page. WebErrorResponse decides which of those are
        // recoverable (a write refused with 403/409/422) and flashes them back
        // instead; it returns null for everything that genuinely is a page —
        // a 404, an expired CSRF token, a 5xx — leaving those to Laravel.
        // The reasoning for each is in that class's docblock.
        $exceptions->render(function (HttpExceptionInterface $e, Request $request) {
            if (! $request->is('api/*')) {
                return WebErrorResponse::for($e, $request);
            }

            $status = $e->getStatusCode();

            // A 405's message names the route and the HTTP verb, and a 5xx
            // (maintenance mode included) says "Service Unavailable" — neither
            // is something a member can act on.
            $message = $status === 405 || $status >= 500 ? '' : $e->getMessage();

            return ApiResponse::error($message ?: ApiResponse::defaultMessage($status), [], $status);
        });

        $exceptions->render(function (Throwable $e, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            // A correlation id the user can quote to support, without exposing
            // a stack trace to the internet.
            $reference = (string) str()->uuid();

            report($e);

            return ApiResponse::error(
                ApiResponse::defaultMessage(500),
                array_filter([
                    'reference' => $reference,
                    'debug' => config('app.debug') ? $e->getMessage() : null,
                ]),
                500,
            );
        });
    })
    ->create();
