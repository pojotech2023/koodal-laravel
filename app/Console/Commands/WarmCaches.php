<?php

namespace App\Console\Commands;

use App\Models\Setting;
use App\Services\Analytics\DashboardMetrics;
use App\Services\MasterData\MasterDataCache;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

/**
 * `php artisan matrimony:warm` — fill the caches before traffic does.
 *
 * A deploy that clears the cache leaves the first request after it paying for
 * everything: `/master-data` builds nine lookup tables, `Setting::all_cached()`
 * reads the whole settings table, the dashboard runs a dozen aggregates.
 * Individually small, but they all land on whoever arrives first, and at launch
 * that is a queue of people rather than one.
 *
 * Run it after `config:cache` in the deploy script and before the load balancer
 * is pointed at the new release. It is idempotent and safe at any time.
 *
 * Deliberately narrow: only caches whose keys are **not** per-user. Warming a
 * per-member cache means guessing which members will arrive, which is either
 * wrong or the whole table.
 */
class WarmCaches extends Command
{
    protected $signature = 'matrimony:warm {--flush : Clear these caches first, then rebuild}';

    protected $description = 'Warm the master-data, settings and dashboard caches';

    public function handle(MasterDataCache $masterData, DashboardMetrics $dashboard): int
    {
        if ($this->option('flush')) {
            // Only meaningful on a release that changed the *shape* of a cached
            // payload — a new field on a master-data row will otherwise not
            // appear until the 24-hour TTL lapses, and `rememberForever` on the
            // settings cache means never.
            // `MasterDataCache::flush()` covers the lookup tables, the country
            // list and the version stamp. The settings cache is a separate key
            // on the model and `rememberForever`, so a stale one there outlives
            // every other cache in the system — it has to be forgotten by hand.
            $masterData->flush();
            Cache::forget(Setting::CACHE_KEY);

            $this->components->info('Flushed the master-data and settings caches.');
        }

        // Each step calls the same accessor the request path calls, rather than
        // writing a cache key itself. A warmer that builds its own key warms a
        // cache nothing reads, and it stays that way silently — the request path
        // just keeps missing.
        $steps = [
            'master data' => fn () => count($masterData->all()),
            'countries' => fn () => count($masterData->countries()),

            // `rememberForever`, so this one only ever gets built once — and if
            // it is built by the first real request instead, that request pays
            // for a full table read.
            'settings' => fn () => count(Setting::all_cached()),

            'dashboard metrics' => fn () => count($dashboard->stats()),
        ];

        foreach ($steps as $label => $step) {
            $started = microtime(true);

            try {
                $count = $step();

                $this->components->twoColumnDetail(
                    $label,
                    sprintf('%d entries · %.0f ms', $count, (microtime(true) - $started) * 1000),
                );
            } catch (\Throwable $e) {
                // One failing cache must not abort the warm-up. A missing
                // Redis is a real problem, but so is a deploy script that stops
                // half way and leaves the rest cold with no message about it.
                $this->components->error("{$label}: {$e->getMessage()}");
            }
        }

        $this->components->info('Warm. Version stamp for clients: '.$masterData->version());

        return self::SUCCESS;
    }
}
