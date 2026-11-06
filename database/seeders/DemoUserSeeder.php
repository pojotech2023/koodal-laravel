<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Seeds exactly 10 balanced demo profiles (5 female, 5 male) across all marital status categories.
 */
class DemoUserSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->call('matrimony:reset-ten-profiles');
    }
}
