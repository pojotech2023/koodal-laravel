<?php

namespace App\Console\Commands;

use App\Models\Role;
use App\Models\User;
use App\Services\AuditLogger;
use App\Support\PhoneNumber;
use App\Support\ProfileCode;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;

use function Laravel\Prompts\password;
use function Laravel\Prompts\select;
use function Laravel\Prompts\text;

/**
 * `php artisan matrimony:staff` — the first admin on a fresh deployment.
 *
 * `StaffSeeder` cannot do this job: it seeds four accounts with the literal
 * password `Password@123`, and `DatabaseSeeder` refuses to run in production for
 * exactly that reason. So without this command a production deployment has a
 * fully built admin panel and no way in, and the workaround is someone typing an
 * INSERT with a bcrypt hash they generated in a REPL.
 *
 * The password is prompted for, never passed as an argument. A `--password` flag
 * would put the credential in the shell history, in the process list while it
 * runs, and in whatever CI log invoked it.
 */
class CreateStaffUser extends Command
{
    protected $signature = 'matrimony:staff
                            {--role= : Role name, e.g. super_admin (prompted if omitted)}
                            {--name= : Full name}
                            {--email= : Login email, unique}
                            {--mobile= : Mobile in E.164, e.g. +919000000001}';

    protected $description = 'Create a staff account interactively (the first super admin on a new deployment)';

    public function handle(AuditLogger $audit): int
    {
        $roles = Role::query()->where('name', '!=', 'user')->orderBy('name')->pluck('label', 'name');

        if ($roles->isEmpty()) {
            $this->components->error('No staff roles exist. Run `php artisan db:seed --class=RolePermissionSeeder` first.');

            return self::FAILURE;
        }

        $role = $this->option('role') ?: select('Role', $roles->all());

        if (! $roles->has($role)) {
            $this->components->error("Unknown role [{$role}]. One of: ".$roles->keys()->implode(', '));

            return self::FAILURE;
        }

        $attributes = [
            'name' => $this->option('name') ?: text('Full name', required: true),
            'email' => $this->option('email') ?: text('Email', required: true),
            'mobile' => $this->option('mobile') ?: text('Mobile (E.164)', placeholder: '+919000000001', required: true),
        ];

        // Normalised before the uniqueness check, not after. `9000000001` and
        // `+919000000001` are the same number, and letting both in produces two
        // staff accounts that cannot both log in by mobile.
        $attributes['mobile'] = PhoneNumber::normalise($attributes['mobile']) ?? $attributes['mobile'];

        $validator = Validator::make($attributes, [
            'name' => ['required', 'string', 'min:2', 'max:120'],
            'email' => ['required', 'email', 'max:190', 'unique:users,email'],
            'mobile' => ['required', 'string', 'max:16', 'unique:users,mobile'],
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->components->error($error);
            }

            return self::FAILURE;
        }

        // The same rules the member-facing reset enforces. A weaker password on
        // the account that can suspend anybody would be the wrong way round.
        $password = password('Password', validate: fn (string $value) => match (true) {
            strlen($value) < 12 => 'Use at least 12 characters — this account can suspend anyone.',
            ! preg_match('/[A-Z]/', $value) => 'Include an uppercase letter.',
            ! preg_match('/[a-z]/', $value) => 'Include a lowercase letter.',
            ! preg_match('/\d/', $value) => 'Include a digit.',
            default => null,
        });

        $user = User::create($attributes + [
            'profile_code' => ProfileCode::generate(),
            'password' => $password,
            'role_id' => Role::where('name', $role)->value('id'),
            'status' => 'active',

            // Staff never go through the OTP flow, so nothing else would ever
            // stamp these — and `EnsureActiveUser` would hold the account at the
            // door with no way to release it.
            'email_verified_at' => now(),
            'mobile_verified_at' => now(),
            // A fourth value beyond the migration's `web, android, ios`. Safe
            // because every reader of this column — `ExportUsers`, the analytics
            // `by_source` breakdown — is scoped through `User::members()`, which
            // matches on `role.name = 'user'` and so never sees a staff row.
            'registration_source' => 'console',

            // `users` carries these NOT NULL for members. Staff have no profile
            // and no matrimonial meaning, so they are placeholders — and the
            // admin surface never reads them, because `User::members()` scopes
            // every member query away from staff rows.
            'gender' => 'male',
            'date_of_birth' => now()->subYears(30)->toDateString(),
        ]);

        // Actor is the account itself: there is no authenticated user in a
        // console context, and a null actor on the row that created a super
        // admin would be the least useful audit entry in the table.
        $audit->log(
            'staff.created',
            $user,
            newValues: ['role' => $role, 'email' => $user->email, 'via' => 'console'],
            description: "Staff account created from the console with role {$role}",
            actor: $user,
        );

        $this->components->info("Created {$user->email} ({$role}), profile code {$user->profile_code}.");

        return self::SUCCESS;
    }
}
