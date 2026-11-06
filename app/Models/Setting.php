<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Cache;

/**
 * Runtime knobs an admin may change without a deploy. Cached whole and busted
 * by an observer on write — this is read on requests that must not pay for a
 * query.
 */
class Setting extends Model
{
    use HasFactory;

    public const CACHE_KEY = 'settings.all';

    protected function casts(): array
    {
        return ['is_public' => 'boolean'];
    }

    // --- Accessors ---------------------------------------------------------

    public static function get(string $key, mixed $default = null): mixed
    {
        return static::all_cached()[$key] ?? $default;
    }

    /** @return array<string, mixed> */
    public static function all_cached(): array
    {
        return Cache::rememberForever(static::CACHE_KEY, fn () => static::query()
            ->get()
            ->mapWithKeys(fn (self $s) => [$s->key => $s->typed()])
            ->all());
    }

    /** @return array<string, mixed> */
    public static function publicValues(): array
    {
        return static::query()
            ->where('is_public', true)
            ->get()
            ->mapWithKeys(fn (self $s) => [$s->key => $s->typed()])
            ->all();
    }

    /** The `type` column is what stops "false" arriving at a client as truthy. */
    public function typed(): mixed
    {
        return match ($this->type) {
            'int' => (int) $this->value,
            'bool' => filter_var($this->value, FILTER_VALIDATE_BOOLEAN),
            'json' => json_decode((string) $this->value, true),
            default => $this->value,
        };
    }

    // --- Scopes ------------------------------------------------------------

    public function scopeGroup(Builder $query, ?string $group): Builder
    {
        return $group ? $query->where('group', $group) : $query;
    }
}
