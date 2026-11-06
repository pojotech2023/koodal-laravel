<?php

namespace App\Providers;

use App\Services\Profile\ProfileVisibility;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Singleton for its per-request memo: a profile list resolves it once
        // and reuses the cached visibility decisions across every row and every
        // resource that asks. A fresh instance per row would throw the memo
        // away and re-run two block lookups per question.
        $this->app->singleton(ProfileVisibility::class);
    }

    public function boot(): void
    {
        /*
        |----------------------------------------------------------------------
        | Rate limiters
        |----------------------------------------------------------------------
        | The OTP bucket is keyed on the identifier rather than the IP: an
        | attacker rotating IPs against one mobile number is the actual threat,
        | and an IP-keyed limit does nothing about it.
        */

        RateLimiter::for('auth', function (Request $r) {
            $limits = [
                Limit::perMinute(30)->by($r->ip()),
            ];

            $identifier = trim((string) $r->input('identifier', $r->input('mobile', '')));
            if ($identifier !== '') {
                $limits[] = Limit::perMinute(10)->by(strtolower($identifier));
            }

            return $limits;
        });

        RateLimiter::for('otp', function (Request $r) {
            $identifier = trim((string) $r->input('identifier', $r->input('mobile', '')));
            $key = $identifier !== '' ? strtolower($identifier) : $r->ip();

            return app()->isProduction()
                ? Limit::perMinutes(10, 10)->by($key)
                : Limit::perMinute(60)->by($key);
        });

        RateLimiter::for('search', fn (Request $r) => Limit::perMinute(60)->by($r->user()?->id ?: $r->ip()));

        // The signed-out `/browse` preview. Tighter than `search` and keyed on
        // IP only, because there is never a user to key on: the page reports a
        // (bucketed) count of members matching a filter set, and repeated
        // probing of that figure is the one thing the rounding does not by
        // itself prevent. See RunPreviewSearch::countMatches.
        RateLimiter::for('preview-search', fn (Request $r) => Limit::perMinute(20)->by($r->ip()));

        // The public contact form. Matches the `throttle:5,10` the API's
        // contact route already carries — one budget per IP across both front
        // doors, rather than a spammer getting a fresh allowance by switching
        // from the JSON endpoint to the HTML one.
        RateLimiter::for('contact', fn (Request $r) => Limit::perMinutes(10, 5)->by($r->ip()));
        RateLimiter::for('interest', fn (Request $r) => Limit::perMinute(20)->by($r->user()?->id ?: $r->ip()));
        RateLimiter::for('message', fn (Request $r) => Limit::perMinute(60)->by($r->user()?->id ?: $r->ip()));
        RateLimiter::for('api', fn (Request $r) => Limit::perMinute(120)->by($r->user()?->id ?: $r->ip()));
        RateLimiter::for('games', fn (Request $r) => Limit::perMinute(300)->by($r->user()?->id ?: $r->ip()));

        /*
        |----------------------------------------------------------------------
        | Development guards
        |----------------------------------------------------------------------
        | Lazy loading is the single most common cause of a slow profile list.
        | Failing loudly outside production means it is caught in CI rather
        | than in an APM trace six weeks later.
        */

        Model::preventLazyLoading(! app()->isProduction());
        Model::preventSilentlyDiscardingAttributes(! app()->isProduction());
    }
}
