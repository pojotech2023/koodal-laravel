<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

/**
 * Permissions are rows, not constants. Code asks
 * `$user->hasPermission('users.suspend')` and never checks a role name, so
 * reorganising roles is an admin task rather than a deploy.
 */
class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'dashboard' => ['dashboard.view'],
            'users' => ['users.view', 'users.edit', 'users.suspend', 'users.block', 'users.verify', 'users.delete', 'users.export'],
            'profiles' => ['profiles.view', 'profiles.moderate'],
            'photos' => ['photos.moderate'],
            'verification' => ['verification.review'],
            'interests' => ['interests.view'],
            'memberships' => ['memberships.view', 'memberships.manage'],
            'subscriptions' => ['subscriptions.view', 'subscriptions.manage'],
            'payments' => ['payments.view', 'payments.refund', 'payments.export'],
            'reports' => ['reports.view', 'reports.resolve'],
            'chat' => ['chat.moderate'],
            'notifications' => ['notifications.view', 'notifications.broadcast'],
            'cms' => ['cms.view', 'cms.manage'],

            // Separate from `cms` although the enquiry arrives through the CMS
            // controller. Answering support mail is support's job, and the
            // `support` role holds no CMS permission — folding these into
            // `cms.manage` would let a content editor answer enquiries while
            // support could not.
            'enquiries' => ['enquiries.view', 'enquiries.respond'],

            'master_data' => ['master_data.view', 'master_data.manage'],
            'locations' => ['locations.view', 'locations.manage'],
            'staff' => ['staff.view', 'staff.manage'],
            'settings' => ['settings.view', 'settings.manage', 'settings.audit'],
            'analytics' => ['analytics.view'],
        ];

        foreach ($permissions as $group => $names) {
            foreach ($names as $name) {
                Permission::updateOrCreate(['name' => $name], [
                    'group' => $group,
                    'label' => ucfirst(str_replace(['.', '_'], [' ', ' '], $name)),
                ]);
            }
        }

        $all = Permission::pluck('id')->all();

        $roles = [
            'super_admin' => ['label' => 'Super Admin', 'permissions' => $all],
            'admin' => ['label' => 'Admin', 'permissions' => Permission::whereNotIn('group', ['staff', 'settings'])->pluck('id')->all()],
            'moderator' => ['label' => 'Moderator', 'permissions' => Permission::whereIn('group', ['dashboard', 'profiles', 'photos', 'verification', 'reports', 'chat', 'users'])
                ->whereNotIn('name', ['users.delete', 'users.export', 'users.edit'])->pluck('id')->all()],
            // The enquiry pair is the one thing on this list that is support's
            // alone — moderators work reports, support answers the contact form.
            'support' => ['label' => 'Support Staff', 'permissions' => Permission::whereIn('name', ['dashboard.view', 'users.view', 'profiles.view', 'reports.view', 'reports.resolve', 'subscriptions.view', 'payments.view', 'enquiries.view', 'enquiries.respond'])->pluck('id')->all()],
            'user' => ['label' => 'Member', 'permissions' => []],
        ];

        foreach ($roles as $name => $config) {
            $role = Role::updateOrCreate(['name' => $name], [
                'label' => $config['label'],
                'is_system' => true,
            ]);

            $role->permissions()->sync($config['permissions']);
        }
    }
}
