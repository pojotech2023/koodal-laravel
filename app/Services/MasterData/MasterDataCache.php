<?php

namespace App\Services\MasterData;

use App\Models\Caste;
use App\Models\City;
use App\Models\Community;
use App\Models\Country;
use App\Models\Education;
use App\Models\Gothra;
use App\Models\IncomeRange;
use App\Models\LifestyleOption;
use App\Models\MaritalStatus;
use App\Models\MotherTongue;
use App\Models\Occupation;
use App\Models\Religion;
use App\Models\State;
use App\Models\SubCommunity;
use Illuminate\Support\Facades\Cache;

/**
 * Master data is read on nearly every screen and changes a few times a year,
 * which makes it the clearest caching win in the system.
 *
 * Every payload carries a `version` derived from the newest updated_at across
 * the lookup tables. The Flutter app stores it and skips the download when it
 * has not moved — the difference between a 400 KB first-launch payload and a
 * 60-byte no-op on every subsequent launch.
 */
class MasterDataCache
{
    public const TTL = 86400;

    public const VERSION_KEY = 'master-data.version';

    /** URL segment → model. Keeps the route's `where` constraint honest. */
    public const TYPES = [
        'religions' => Religion::class,
        'communities' => Community::class,
        'sub-communities' => SubCommunity::class,
        'mother-tongues' => MotherTongue::class,
        'educations' => Education::class,
        'occupations' => Occupation::class,
        'income-ranges' => IncomeRange::class,
        'marital-statuses' => MaritalStatus::class,
        'lifestyle-options' => LifestyleOption::class,
        'castes' => Caste::class,
        'gothras' => Gothra::class,
    ];

    /** The single payload the mobile app fetches on first launch. */
    public function all(): array
    {
        return Cache::remember('master-data.all', static::TTL, fn () => [
            'version' => $this->version(),
            'religions' => $this->list('religions'),
            'communities' => $this->list('communities'),
            'sub_communities' => $this->list('sub-communities'),
            'mother_tongues' => $this->list('mother-tongues'),
            'educations' => $this->list('educations'),
            'occupations' => $this->list('occupations'),
            'income_ranges' => $this->list('income-ranges'),
            'marital_statuses' => $this->list('marital-statuses'),
            'lifestyle_options' => $this->list('lifestyle-options'),
            'castes' => $this->list('castes'),
            'gothras' => $this->list('gothras'),
            'countries' => $this->countries(),
        ]);
    }

    public function list(string $type, array $filters = []): array
    {
        $model = static::TYPES[$type] ?? null;

        if (! $model) {
            return [];
        }

        $key = 'master-data.'.$type.($filters ? '.'.md5(serialize($filters)) : '');

        return Cache::remember($key, static::TTL, function () use ($model, $filters) {
            $query = $model::query()->active()->ordered();

            foreach ($filters as $column => $value) {
                if ($value !== null) {
                    $query->where($column, $value);
                }
            }

            return $query->get()->map(fn ($row) => $this->present($row))->all();
        });
    }

    public function countries(): array
    {
        return Cache::remember('master-data.countries', static::TTL, fn () => Country::query()
            ->active()
            ->ordered()
            ->get(['id', 'name', 'slug', 'iso2', 'phone_code'])
            ->toArray());
    }

    public function states(?int $countryId): array
    {
        return Cache::remember("master-data.states.{$countryId}", static::TTL, fn () => State::query()
            ->active()
            ->ofCountry($countryId)
            ->orderBy('name')
            ->get(['id', 'country_id', 'name', 'slug', 'code'])
            ->toArray());
    }

    /**
     * Typeahead results are deliberately uncached: the key space is every
     * prefix a user can type, and caching that fills Redis with single-use
     * entries that are never read twice.
     */
    public function cities(?int $stateId, ?string $search = null, int $limit = 50): array
    {
        $query = City::query()->active()->ofState($stateId)->search($search)->orderBy('name')->limit($limit);

        if ($search) {
            return $query->get(['id', 'state_id', 'name', 'slug'])->toArray();
        }

        return Cache::remember("master-data.cities.{$stateId}", static::TTL,
            fn () => $query->get(['id', 'state_id', 'name', 'slug'])->toArray());
    }

    /**
     * A content hash, not an incrementing counter: two environments seeded with
     * the same data produce the same version, so a client does not redownload
     * for a change that did not happen.
     */
    public function version(): string
    {
        return Cache::remember(static::VERSION_KEY, static::TTL, function () {
            $models = static::TYPES + [
                'countries' => Country::class,
                'states' => State::class,
                'cities' => City::class,
            ];

            $stamps = [];

            foreach ($models as $type => $model) {
                $stamps[] = $type.':'.$model::max('updated_at').':'.$model::count();
            }

            return substr(md5(implode('|', $stamps)), 0, 12);
        });
    }

    /** Called by the observer on any master-data write. */
    public function flush(): void
    {
        Cache::forget('master-data.all');
        Cache::forget('master-data.countries');
        Cache::forget(static::VERSION_KEY);

        foreach (array_keys(static::TYPES) as $type) {
            Cache::forget('master-data.'.$type);
        }
    }

    protected function present(object $row): array
    {
        return array_filter([
            'id' => $row->id,
            'name' => $row->name,
            'slug' => $row->slug,
            'religion_id' => $row->religion_id ?? null,
            'community_id' => $row->community_id ?? null,
            'category' => $row->category ?? null,
            'type' => $row->type ?? null,
            'min_amount' => $row->min_amount ?? null,
            'max_amount' => $row->max_amount ?? null,
        ], fn ($v) => $v !== null);
    }
}
