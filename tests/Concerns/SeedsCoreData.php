<?php

namespace Tests\Concerns;

use Database\Seeders\LocationSeeder;
use Database\Seeders\MasterDataSeeder;
use Database\Seeders\MembershipPlanSeeder;
use Database\Seeders\RolePermissionSeeder;

/**
 * Composable seed steps for tests that need real lookup rows rather than the
 * full `DatabaseSeeder` — which also runs `DemoUserSeeder`, built for 100k
 * rows and far too slow to run per test.
 */
trait SeedsCoreData
{
    /** Roles + permissions. Needed by anything that logs a user in. */
    protected function seedRoles(): void
    {
        $this->seed(RolePermissionSeeder::class);
    }

    /** The Free/Silver/Gold plans. Needed by anything touching FeatureGate or subscriptions. */
    protected function seedPlans(): void
    {
        $this->seed(MembershipPlanSeeder::class);
    }

    /** Religions, communities, education, occupation, etc. + countries/states/cities. */
    protected function seedMasterData(): void
    {
        $this->seed([MasterDataSeeder::class, LocationSeeder::class]);
    }
}
