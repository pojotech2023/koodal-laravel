<?php

namespace App\Observers;

use App\Models\City;
use App\Models\MembershipPlan;
use App\Models\Setting;
use App\Models\State;
use App\Services\MasterData\MasterDataCache;
use App\Support\SuggestedPlans;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * One observer for every table that is cached hard and written rarely.
 *
 * These four share a shape: read on nearly every request, changed a handful of
 * times a year by an admin, and — critically — changed through a UI where the
 * person editing has no idea a cache exists. A TTL alone is not enough. An
 * admin who fixes a misspelt city name and still sees it misspelt twelve hours
 * later files a bug against the admin panel, not against the cache.
 *
 * Registered against City, State, Setting and MembershipPlan in
 * EventServiceProvider. Every model gets `saved` and `deleted`, because a
 * deactivated city has to leave the dropdown as surely as a renamed one has to
 * change in it.
 */
class CacheBustingObserver
{
    public function saved(Model $model): void
    {
        $this->flush($model);
    }

    public function deleted(Model $model): void
    {
        $this->flush($model);
    }

    /** Restoring a soft-deleted row puts it back in every list it left. */
    public function restored(Model $model): void
    {
        $this->flush($model);
    }

    protected function flush(Model $model): void
    {
        match (true) {
            $model instanceof City => $this->flushCity($model),
            $model instanceof State => $this->flushState($model),
            $model instanceof Setting => Cache::forget(Setting::CACHE_KEY),
            $model instanceof MembershipPlan => $this->flushPlans(),
            default => null,
        };
    }

    /**
     * Cities are cached per state, so only that one key moves — flushing the
     * whole master-data cache because one city was renamed would re-warm nine
     * lookup tables to fix a dropdown nobody else is looking at.
     *
     * The version hash does have to move: the mobile app decides whether to
     * re-download from it, and a stale version means a client that never sees
     * the change.
     */
    protected function flushCity(City $city): void
    {
        foreach (array_unique([$city->state_id, $city->getOriginal('state_id')]) as $stateId) {
            Cache::forget("master-data.cities.{$stateId}");
        }

        Cache::forget(MasterDataCache::VERSION_KEY);
        Cache::forget('master-data.all');
    }

    /** Same, one level up — plus the cities beneath it, whose list is keyed on this row. */
    protected function flushState(State $state): void
    {
        foreach (array_unique([$state->country_id, $state->getOriginal('country_id')]) as $countryId) {
            Cache::forget("master-data.states.{$countryId}");
        }

        Cache::forget("master-data.cities.{$state->id}");
        Cache::forget(MasterDataCache::VERSION_KEY);
        Cache::forget('master-data.all');
    }

    /**
     * A plan edit changes what every paywall offers. SuggestedPlans caches per
     * feature, so the whole set goes — the alternative is an upgrade sheet
     * quoting a price that no longer exists, which is a chargeback waiting to
     * happen.
     *
     * Per-user entitlement caches (`features:{id}`) are deliberately NOT
     * flushed here: a subscription's `features_snapshot` is frozen at purchase,
     * so an admin editing a plan does not change what an existing subscriber
     * already bought.
     *
     * When Phase 4 adds a cached public plan list, its key belongs here too.
     */
    protected function flushPlans(): void
    {
        SuggestedPlans::flush();
    }
}
