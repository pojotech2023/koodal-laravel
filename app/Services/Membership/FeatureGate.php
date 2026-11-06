<?php

namespace App\Services\Membership;

use App\Exceptions\FeatureLimitException;
use App\Models\MembershipPlan;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * One service answers every "is this allowed" question in the product.
 *
 * Features are read from the subscription's frozen snapshot, never from the
 * live plan — an admin repricing Gold must not retroactively take away what
 * existing subscribers paid for. The live plan is consulted only for the free
 * tier, which nobody paid for.
 *
 * -1 always means unlimited.
 */
class FeatureGate
{
    /** Resolved feature map for a user: snapshot first, free plan as fallback. */
    public function features(User $user): array
    {
        return Cache::remember("features:{$user->id}", 300, function () use ($user) {
            if ($subscription = $user->activeSubscription) {
                return $subscription->features_snapshot;
            }

            return MembershipPlan::where('is_default', true)->value('features') ?? [];
        });
    }

    public function allows(User $user, string $key): bool
    {
        return (bool) ($this->features($user)[$key] ?? false);
    }

    public function limit(User $user, string $key): int
    {
        return (int) ($this->features($user)[$key] ?? 0);
    }

    /** Units left this period. PHP_INT_MAX when unlimited. */
    public function remaining(User $user, string $key): int
    {
        $limit = $this->limit($user, $key);

        if ($limit === -1) {
            return PHP_INT_MAX;
        }

        return max(0, $limit - $this->used($user, $key));
    }

    public function used(User $user, string $key): int
    {
        return (int) DB::table('usage_counters')
            ->where(['user_id' => $user->id, 'key' => $key, 'period' => $this->period($key)])
            ->value('used');
    }

    /**
     * Guard for capability features.
     *
     * @throws FeatureLimitException rendered by the handler as the standard
     *                               upgrade envelope
     */
    public function assertAllowed(User $user, string $key): void
    {
        if (! $this->allows($user, $key)) {
            throw FeatureLimitException::capability($key);
        }
    }

    /** Guard for quota features. Checks only — call consume() after the write. */
    public function assertQuota(User $user, string $key, int $units = 1): void
    {
        $limit = $this->limit($user, $key);

        if ($limit === -1) {
            return;
        }

        $used = $this->used($user, $key);

        if ($used + $units > $limit) {
            throw FeatureLimitException::quota($key, $used, $limit, $this->resetsAt($key));
        }
    }

    /**
     * Atomic increment — an upsert with `used = used + n` rather than a
     * read-modify-write, so two requests racing for the last unit of a quota
     * cannot both succeed.
     */
    public function consume(User $user, string $key, int $units = 1): void
    {
        DB::table('usage_counters')->upsert(
            [[
                'user_id' => $user->id,
                'key' => $key,
                'period' => $this->period($key),
                'used' => $units,
                'created_at' => now(),
                'updated_at' => now(),
            ]],
            ['user_id', 'key', 'period'],
            ['used' => DB::raw('usage_counters.used + '.$units)],
        );
    }

    public function flush(User $user): void
    {
        Cache::forget("features:{$user->id}");
    }

    protected function period(string $key): string
    {
        return match (config("matrimony.membership.features.{$key}.period")) {
            'day' => now()->format('Y-m-d'),
            'month' => now()->format('Y-m'),
            default => 'lifetime',
        };
    }

    protected function resetsAt(string $key): ?string
    {
        return match (config("matrimony.membership.features.{$key}.period")) {
            'day' => now()->endOfDay()->toIso8601String(),
            'month' => now()->endOfMonth()->toIso8601String(),
            default => null,
        };
    }
}
