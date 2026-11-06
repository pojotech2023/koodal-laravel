<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class HealthController extends Controller
{
    /**
     * Checks dependencies, not just that PHP is alive. A load balancer that
     * keeps routing traffic to a node whose database connection died is worse
     * than one that removes it.
     */
    public function __invoke(): JsonResponse
    {
        $checks = [
            'database' => $this->check(fn () => DB::select('select 1')),
            'cache' => $this->check(fn () => Cache::store()->put('health', 1, 5)),
        ];

        $healthy = ! in_array(false, $checks, true);

        return ApiResponse::success([
            'status' => $healthy ? 'ok' : 'degraded',
            'checks' => $checks,
            'version' => config('app.version', '1.0.0'),
            'time' => now()->toIso8601String(),
        ], $healthy ? 'Service is healthy' : 'Service is degraded', $healthy ? 200 : 503);
    }

    /**
     * The only remote kill switch this product has. Raising a minimum build
     * blocks clients with a known payment or security bug — which is why it is
     * a config value read at request time rather than a constant that needs a
     * deploy.
     */
    public function appVersion(Request $request): JsonResponse
    {
        $platform = $request->query('platform', $request->header('X-Client', 'android'));
        $platform = in_array($platform, ['android', 'ios'], true) ? $platform : 'android';

        $minimum = (int) config("matrimony.app.min_build.{$platform}");
        $current = (int) $request->query('build', $request->header('X-App-Build', 0));

        $forceUpgrade = $current > 0 && $current < $minimum;

        return ApiResponse::success([
            'platform' => $platform,
            'minimum_build' => $minimum,
            'force_upgrade' => $forceUpgrade,
            'store_url' => config("matrimony.app.store_url.{$platform}"),
            'message' => $forceUpgrade
                ? 'A newer version of the app is required to continue.'
                : null,
        ], 'App version requirements retrieved successfully');
    }

    protected function check(callable $probe): bool
    {
        try {
            $probe();

            return true;
        } catch (\Throwable) {
            return false;
        }
    }
}
