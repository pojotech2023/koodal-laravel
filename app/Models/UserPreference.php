<?php

namespace App\Models;

use App\Models\Concerns\BelongsToUser;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * The right-hand side of every match calculation. Scalars and ranges are
 * columns so they can be range-queried; multi-selects are JSON arrays because
 * they are always read whole and pivot tables would mean a ten-way join on the
 * hottest query in the product.
 */
class UserPreference extends Model
{
    use BelongsToUser, HasFactory;

    protected function casts(): array
    {
        return [
            'marital_status_ids' => 'array',
            'religion_ids' => 'array',
            'community_ids' => 'array',
            'mother_tongue_ids' => 'array',
            'education_ids' => 'array',
            'occupation_ids' => 'array',
            'income_range_ids' => 'array',
            'country_ids' => 'array',
            'state_ids' => 'array',
            'city_ids' => 'array',
            'complexions' => 'array',
            'raasis' => 'array',
            'stars' => 'array',
            'castes' => 'array',
            'age_min' => 'integer',
            'age_max' => 'integer',
            'height_min_cm' => 'integer',
            'height_max_cm' => 'integer',
            'weight_min_kg' => 'integer',
            'weight_max_kg' => 'integer',
            'income_min' => 'integer',
            'income_max' => 'integer',
        ];
    }

    // --- Accessors ---------------------------------------------------------

    /**
     * A brand-new user has no stated preferences. Treating that as "wants
     * nobody" would show them an empty search on their first session, so the
     * absence of a preference is always the absence of a constraint.
     */
    public function isEmpty(): bool
    {
        foreach ($this->getAttributes() as $key => $value) {
            if (in_array($key, ['id', 'user_id', 'created_at', 'updated_at', 'about_partner'], true)) {
                continue;
            }

            if ($value !== null && $value !== '' && $value !== '[]') {
                return false;
            }
        }

        return true;
    }

    public function ids(string $key): array
    {
        return array_filter((array) ($this->{$key} ?? []));
    }

    public function accepts(string $key, mixed $value): bool
    {
        $allowed = $this->ids($key);

        return $allowed === [] || in_array($value, $allowed);
    }
}
