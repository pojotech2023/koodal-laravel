<?php

namespace App\Services\Search;

use App\Models\Community;
use App\Models\Education;
use App\Models\IncomeRange;
use App\Models\LifestyleOption;
use App\Models\MaritalStatus;
use App\Models\MotherTongue;
use App\Models\Occupation;
use App\Models\Religion;
use App\Support\Age;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Translates the query string into predicates.
 *
 * Kept apart from SearchService so the filter set can grow without the query
 * shape being re-reasoned each time, and so `GET /search/filters` can describe
 * the same definitions the query enforces — the client's filter panel and the
 * server's WHERE clause come from one source.
 */
class SearchFilters
{
    /** Filter key → the column it constrains. Scalars and id-arrays only. */
    protected const ID_FILTERS = [
        'religion_id' => 'religion_id',
        'religion_ids' => 'religion_id',
        'community_id' => 'community_id',
        'community_ids' => 'community_id',
        'sub_community_ids' => 'sub_community_id',
        'mother_tongue_id' => 'mother_tongue_id',
        'mother_tongue_ids' => 'mother_tongue_id',
        'marital_status_id' => 'marital_status_id',
        'marital_status_ids' => 'marital_status_id',
        'education_id' => 'education_id',
        'education_ids' => 'education_id',
        'occupation_id' => 'occupation_id',
        'occupation_ids' => 'occupation_id',
        'income_range_id' => 'income_range_id',
        'income_range_ids' => 'income_range_id',
        'country_id' => 'country_id',
        'country_ids' => 'country_id',
        'state_id' => 'state_id',
        'state_ids' => 'state_id',
        'city_id' => 'city_id',
        'city_ids' => 'city_id',
    ];

    protected const EXACT_FILTERS = ['diet', 'smoking', 'drinking', 'manglik', 'physical_status', 'body_type', 'complexion', 'family_type', 'family_status', 'job_type'];

    protected const MULTI_STRING_FILTERS = [
        'complexions' => 'complexion',
        'raasis' => 'raasi',
        'stars' => 'star',
        'castes' => 'caste',
    ];

    public function apply(Builder $query, array $input): Builder
    {
        $this->applyIdFilters($query, $input);
        $this->applyExactFilters($query, $input);
        $this->applyMultiStringFilters($query, $input);
        $this->applyAge($query, $input);
        $this->applyHeight($query, $input);
        $this->applyWeight($query, $input);
        $this->applyIncome($query, $input);
        $this->applyFlags($query, $input);
        $this->applyKeyword($query, $input);

        return $query;
    }

    protected function applyIdFilters(Builder $query, array $input): void
    {
        foreach (static::ID_FILTERS as $key => $column) {
            if (! array_key_exists($key, $input)) {
                continue;
            }

            $values = array_values(array_filter((array) $input[$key], fn ($v) => $v !== null && $v !== ''));

            // An empty array means "no constraint", never "match nothing" —
            // the difference between a full result set and a blank screen when
            // a client sends `community_ids[]=`.
            if ($values === []) {
                continue;
            }

            $query->whereIn("user_profiles.{$column}", $values);
        }
    }

    protected function applyExactFilters(Builder $query, array $input): void
    {
        foreach (static::EXACT_FILTERS as $key) {
            if (filled($input[$key] ?? null) && $input[$key] !== 'any') {
                $query->where("user_profiles.{$key}", $input[$key]);
            }
        }
    }

    protected function applyMultiStringFilters(Builder $query, array $input): void
    {
        foreach (static::MULTI_STRING_FILTERS as $key => $column) {
            if (! array_key_exists($key, $input)) {
                continue;
            }

            $values = array_values(array_filter((array) $input[$key], fn ($v) => $v !== null && $v !== ''));

            if ($values === []) {
                continue;
            }

            $query->whereIn("user_profiles.{$column}", $values);
        }
    }

    /**
     * Age filters become date-of-birth bounds. Computing age in SQL
     * (`TIMESTAMPDIFF(YEAR, date_of_birth, NOW())`) makes the index on
     * users.date_of_birth unusable and turns search into a full scan.
     */
    protected function applyAge(Builder $query, array $input): void
    {
        [$earliest, $latest] = Age::birthdateRange(
            isset($input['age_min']) ? (int) $input['age_min'] : null,
            isset($input['age_max']) ? (int) $input['age_max'] : null,
        );

        if ($earliest) {
            $query->where('users.date_of_birth', '>=', $earliest);
        }

        if ($latest) {
            $query->where('users.date_of_birth', '<=', $latest);
        }
    }

    protected function applyHeight(Builder $query, array $input): void
    {
        if (filled($input['height_min'] ?? null)) {
            $query->where('user_profiles.height_cm', '>=', (int) $input['height_min']);
        }

        if (filled($input['height_max'] ?? null)) {
            $query->where('user_profiles.height_cm', '<=', (int) $input['height_max']);
        }
    }

    protected function applyWeight(Builder $query, array $input): void
    {
        if (filled($input['weight_min'] ?? null)) {
            $query->where('user_profiles.weight_kg', '>=', (int) $input['weight_min']);
        }

        if (filled($input['weight_max'] ?? null)) {
            $query->where('user_profiles.weight_kg', '<=', (int) $input['weight_max']);
        }
    }

    /**
     * Income is a band, not a number. "At least ₹8L" means every band whose
     * ceiling reaches ₹8L — filtering on min_amount alone would drop the band
     * that actually contains the figure the user asked for.
     */
    protected function applyIncome(Builder $query, array $input): void
    {
        $min = filled($input['income_min'] ?? null) ? (int) $input['income_min'] : null;
        $max = filled($input['income_max'] ?? null) ? (int) $input['income_max'] : null;

        if ($min === null && $max === null) {
            return;
        }

        $bandIds = IncomeRange::query()
            ->get()
            ->filter(fn (IncomeRange $band) => $band->overlaps($min, $max))
            ->pluck('id');

        // Bands are a dozen rows and fully cached, so resolving them in PHP is
        // one cheap lookup rather than a correlated subquery per profile row.
        $query->whereIn('user_profiles.income_range_id', $bandIds);
    }

    protected function applyFlags(Builder $query, array $input): void
    {
        if (filled($input['has_photo'] ?? null) && filter_var($input['has_photo'], FILTER_VALIDATE_BOOLEAN)) {
            $query->whereNotNull('user_profiles.primary_photo_id');
        }

        if (filled($input['is_verified'] ?? null) && filter_var($input['is_verified'], FILTER_VALIDATE_BOOLEAN)) {
            $query->where('user_profiles.is_verified', true);
        }

        if (filled($input['min_completion'] ?? null)) {
            $query->where('user_profiles.completion_percentage', '>=', (int) $input['min_completion']);
        }

        if (filled($input['active_within_days'] ?? null)) {
            $query->where('users.last_active_at', '>=', now()->subDays((int) $input['active_within_days']));
        }

        // "Hide profiles I have already acted on" — a NOT EXISTS rather than a
        // materialised id list, so a user with 4,000 shortlists does not ship
        // 4,000 integers into the query text.
        if (filled($input['exclude_actioned'] ?? null) && filter_var($input['exclude_actioned'], FILTER_VALIDATE_BOOLEAN)) {
            $viewerId = (int) $input['_viewer_id'];

            $query->whereNotExists(fn ($q) => $q
                ->select(DB::raw(1))
                ->from('interests')
                ->whereColumn('interests.receiver_id', 'user_profiles.user_id')
                ->where('interests.sender_id', $viewerId)
                ->whereNull('interests.deleted_at'));
        }
    }

    /** Free-text across the two fields users actually type into. */
    protected function applyKeyword(Builder $query, array $input): void
    {
        if (! filled($input['q'] ?? null)) {
            return;
        }

        $term = trim((string) $input['q']);

        $query->where(fn (Builder $q) => $q
            ->where('users.profile_code', $term)
            ->orWhere('users.name', 'like', $term.'%'));
    }

    /**
     * Definitions for `GET /search/filters`, so the client renders its panel
     * from the same source the query enforces. A filter that exists in one and
     * not the other is a support ticket about "the filter does nothing".
     */
    public function definitions(): array
    {
        return [
            [
                'key' => 'age', 'label' => 'Age', 'type' => 'range',
                'min' => 18, 'max' => config('matrimony.profile.max_age'), 'unit' => 'years',
            ],
            [
                'key' => 'height', 'label' => 'Height', 'type' => 'range',
                'min' => 120, 'max' => 220, 'unit' => 'cm',
            ],
            [
                'key' => 'marital_status_ids', 'label' => 'Marital status', 'type' => 'multi',
                'options' => MaritalStatus::active()->ordered()->get(['id', 'name']),
            ],
            [
                'key' => 'religion_ids', 'label' => 'Religion', 'type' => 'multi',
                'options' => Religion::active()->ordered()->get(['id', 'name']),
            ],
            [
                'key' => 'community_ids', 'label' => 'Community', 'type' => 'multi',
                'depends_on' => 'religion_ids',
                'options' => Community::active()->ordered()->get(['id', 'name', 'religion_id']),
            ],
            [
                'key' => 'mother_tongue_ids', 'label' => 'Mother tongue', 'type' => 'multi',
                'options' => MotherTongue::active()->ordered()->get(['id', 'name']),
            ],
            [
                'key' => 'education_ids', 'label' => 'Education', 'type' => 'multi',
                'options' => Education::active()->ordered()->get(['id', 'name', 'category']),
            ],
            [
                'key' => 'occupation_ids', 'label' => 'Occupation', 'type' => 'multi',
                'options' => Occupation::active()->ordered()->get(['id', 'name', 'category']),
            ],
            [
                'key' => 'income', 'label' => 'Annual income', 'type' => 'range',
                'options' => IncomeRange::active()->ordered()->get(['id', 'name', 'min_amount', 'max_amount']),
            ],
            [
                'key' => 'location', 'label' => 'Location', 'type' => 'cascade',
                'levels' => ['country_ids', 'state_ids', 'city_ids'],
                'source' => '/api/v1/locations',
            ],
            [
                'key' => 'diet', 'label' => 'Diet', 'type' => 'single',
                'options' => LifestyleOption::active()->ofType('diet')->ordered()->get(['id', 'name', 'slug']),
            ],
            [
                'key' => 'smoking', 'label' => 'Smoking', 'type' => 'single',
                'options' => LifestyleOption::active()->ofType('smoking')->ordered()->get(['id', 'name', 'slug']),
            ],
            [
                'key' => 'drinking', 'label' => 'Drinking', 'type' => 'single',
                'options' => LifestyleOption::active()->ofType('drinking')->ordered()->get(['id', 'name', 'slug']),
            ],
            ['key' => 'has_photo', 'label' => 'With photo only', 'type' => 'boolean'],
            ['key' => 'is_verified', 'label' => 'Verified profiles only', 'type' => 'boolean'],
        ];
    }
}
