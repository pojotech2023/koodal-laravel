<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // A seeded admin with a known password on a live database is a breach
        // waiting to happen. Refuse rather than rely on discipline.
        if (app()->isProduction()) {
            $this->command->error('Seeding is disabled in production.');

            return;
        }

        $this->call([
            RolePermissionSeeder::class,
            MasterDataSeeder::class,
            LocationSeeder::class,
            MembershipPlanSeeder::class,
            CmsSeeder::class,
            SettingsSeeder::class,
            StaffSeeder::class,
            DemoUserSeeder::class,
        ]);

        $this->command->info('Seeded. Admin: admin@matrimony.test / Password@123');
    }
}
