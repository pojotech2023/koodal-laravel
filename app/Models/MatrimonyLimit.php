<?php

namespace App\Models;

class MatrimonyLimit extends Model
{
    protected $table = 'matrimony_limits';

    public const PLANS = [
        'basic' => [
            'name' => 'Basic',
            'limit' => 100,
            'users_label' => '100 Users',
            'price' => 'Rs. 4,999/-',
            'period' => 'Yearly plan',
            'description' => 'Complete Matrimony web platform & administrative control panel',
            'features' => [
                '100 Users',
                'Signup',
                'Login With OTP',
                'Login With Password',
                'Dashboard',
                'Searches',
                'Matches',
                'Interests',
                'Shortlists',
                'Messages',
                'Membership',
                'Profile',
            ],
            'admin_features' => [
                'Admin Login',
                'Dashboard',
                'Members',
                'Profile Approvals',
                'Photo Moderation',
                'ID Verification',
                'Reports',
                'Membership Plans',
                'Subscriptions',
                'Payments',
                'Content Update',
                'Staff & Roles',
                'Settings',
            ],
        ],
        'premium' => [
            'name' => 'Premium',
            'limit' => 250,
            'users_label' => '250 Users',
            'price' => 'Rs. 14,999/-',
            'period' => 'Yearly plan',
            'description' => 'For growing platforms with dedicated Customer Android App',
            'features' => [
                '250 Users',
                'All In Basic Plan',
                'Customer Android App',
            ],
            'admin_features' => [],
        ],
        'advanced' => [
            'name' => 'Advanced',
            'limit' => 500,
            'users_label' => '500 Users',
            'price' => 'Rs. 21,999/-',
            'period' => 'Yearly plan',
            'description' => 'High capacity scale with Customer Android & iOS Apps',
            'features' => [
                '500 Users',
                'All In Basic & Premium Plan',
                'Customer Android & iOS App',
            ],
            'admin_features' => [],
        ],
        'unlimited' => [
            'name' => 'Unlimited',
            'limit' => 999999,
            'users_label' => 'Unlimited Users',
            'price' => 'Unlimited',
            'period' => 'Lifetime',
            'description' => 'Unlimited member registrations with full platform access',
            'features' => [
                'Unlimited Users',
                'All In Basic, Premium & Advanced Plan',
                'Customer Android & iOS App',
            ],
            'admin_features' => [],
        ],
    ];

    protected function casts(): array
    {
        return [
            'available_user_count' => 'integer',
            'used_count' => 'integer',
            'balance_count' => 'integer',
            'requested_at' => 'datetime',
        ];
    }

    /**
     * Check if current plan or capacity is unlimited.
     * Always returns true so the platform is never restricted.
     */
    public function isUnlimited(): bool
    {
        return true;
    }

    /**
     * Get or initialize the active platform limit.
     */
    public static function current(): self
    {
        $limit = static::query()->first();

        $actualMemberCount = User::query()
            ->whereHas('role', fn ($q) => $q->where('name', 'user'))
            ->count();

        if (! $limit) {
            $limit = static::create([
                'plan_type' => 'unlimited',
                'available_user_count' => 999999,
                'used_count' => $actualMemberCount,
                'balance_count' => 999999,
            ]);
        } else {
            // Automatically upgrade any legacy or limited row to unlimited in database
            if ($limit->plan_type !== 'unlimited' || $limit->available_user_count < 999999) {
                $limit->update([
                    'plan_type' => 'unlimited',
                    'available_user_count' => 999999,
                    'balance_count' => 999999,
                    'requested_plan' => null,
                ]);
            }

            if ($limit->used_count !== $actualMemberCount) {
                $limit->update([
                    'used_count' => $actualMemberCount,
                ]);
            }
        }

        return $limit;
    }

    /**
     * Check whether a new member registration is permitted.
     */
    public static function canRegister(): bool
    {
        return true;
    }

    /**
     * Record a newly registered member.
     */
    public static function recordUserRegistered(): void
    {
        $current = static::current();
        $newUsed = $current->used_count + 1;

        $current->update([
            'plan_type' => 'unlimited',
            'available_user_count' => 999999,
            'used_count' => $newUsed,
            'balance_count' => 999999,
        ]);
    }

    /**
     * Record a deleted member.
     */
    public static function recordUserDeleted(): void
    {
        $current = static::current();
        $newUsed = max(0, $current->used_count - 1);

        $current->update([
            'plan_type' => 'unlimited',
            'available_user_count' => 999999,
            'used_count' => $newUsed,
            'balance_count' => 999999,
        ]);
    }

    /**
     * Request a plan upgrade.
     */
    public static function requestUpgrade(string $planType): self
    {
        $current = static::current();

        $current->update([
            'plan_type' => 'unlimited',
            'available_user_count' => 999999,
            'balance_count' => 999999,
            'requested_plan' => null,
            'requested_at' => null,
        ]);

        return $current->fresh();
    }

    /**
     * Upgrade or switch plan.
     */
    public static function upgrade(string $planType): self
    {
        $current = static::current();

        $actualMemberCount = User::query()
            ->whereHas('role', fn ($q) => $q->where('name', 'user'))
            ->count();

        $current->update([
            'plan_type' => 'unlimited',
            'requested_plan' => null,
            'requested_at' => null,
            'available_user_count' => 999999,
            'used_count' => $actualMemberCount,
            'balance_count' => 999999,
        ]);

        return $current->fresh();
    }
}
