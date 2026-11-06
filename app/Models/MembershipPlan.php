<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class MembershipPlan extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'features' => 'array',
            'price' => 'decimal:2',
            'is_default' => 'boolean',
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
        ];
    }

    // --- Relations ---------------------------------------------------------

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    // --- Accessors ---------------------------------------------------------

    public function feature(string $key): mixed
    {
        return $this->features[$key] ?? null;
    }

    public function isFree(): bool
    {
        return (float) $this->price === 0.0;
    }

    /** Razorpay works in paise; convert once, at the boundary. */
    public function priceInPaise(): int
    {
        return (int) round($this->price * 100);
    }

    /**
     * The comparison-table rows a pricing page renders — the raw stored
     * `features` map expanded against `config('matrimony.membership.features')`'s
     * schema, so a new feature appears on every caller without a release.
     * Shared between `PlanResource` (API) and `Web\MembershipController`'s
     * `plans.blade.php`, same reasoning as `UserProfile::displayNameFor()`
     * this session — one implementation, not one per front door.
     *
     * @return array<int, array{key: string, label: string, type: string, value: mixed, display: string}>
     */
    public function featureRows(): array
    {
        $rows = [];

        foreach (config('matrimony.membership.features') as $key => $schema) {
            $value = $this->feature($key);

            $rows[] = [
                'key' => $key,
                'label' => $schema['label'],
                'type' => $schema['type'],
                'value' => $value,
                'display' => $this->featureDisplay($schema['type'], $value),
            ];
        }

        return $rows;
    }

    protected function featureDisplay(string $type, mixed $value): string
    {
        return match (true) {
            $type === 'capability' => $value ? 'Included' : 'Not included',
            $value === -1 => 'Unlimited',
            $value === null => '—',
            default => (string) $value,
        };
    }

    // --- Scopes ------------------------------------------------------------

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true)->orderBy('sort_order');
    }
}
