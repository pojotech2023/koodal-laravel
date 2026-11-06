<?php

namespace App\Models;

use App\Models\Concerns\IsMasterData;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class State extends Model
{
    use HasFactory, IsMasterData;

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    // --- Relations ---------------------------------------------------------

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    public function cities(): HasMany
    {
        return $this->hasMany(City::class);
    }

    // --- Scopes ------------------------------------------------------------

    public function scopeOfCountry(Builder $query, ?int $countryId): Builder
    {
        return $countryId ? $query->where('country_id', $countryId) : $query;
    }
}
