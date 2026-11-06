<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use App\Support\ProfileCode;
use Illuminate\Database\Seeder;

/**
 * Staff accounts for local development.
 *
 * DatabaseSeeder refuses to run in production, which is the real guard — a
 * known password on a live database is a breach, not a convenience.
 */
class StaffSeeder extends Seeder
{
    public function run(): void
    {
        $staff = [
            ['super_admin', 'Super Admin', 'admin@matrimony.test', '+919000000001'],
            ['admin', 'Platform Admin', 'manager@matrimony.test', '+919000000002'],
            ['moderator', 'Content Moderator', 'moderator@matrimony.test', '+919000000003'],
            ['support', 'Support Agent', 'support@matrimony.test', '+919000000004'],
        ];

        foreach ($staff as [$roleName, $name, $email, $mobile]) {
            $role = Role::where('name', $roleName)->first();

            if (! $role) {
                continue;
            }

            User::updateOrCreate(
                ['email' => $email],
                [
                    'profile_code' => ProfileCode::generate(),
                    'name' => $name,
                    'mobile' => $mobile,
                    'password' => 'Password@123',
                    'gender' => 'male',
                    'date_of_birth' => now()->subYears(35)->toDateString(),
                    'role_id' => $role->id,
                    'status' => 'active',
                    'email_verified_at' => now(),
                    'mobile_verified_at' => now(),
                    'registration_source' => 'web',
                ],
            );
        }
    }
}
