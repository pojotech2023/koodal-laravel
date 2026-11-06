<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StaffRequest;
use App\Http\Requests\Admin\UpdateRoleFormRequest;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Services\AuditLogger;
use App\Support\ProfileCode;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

/**
 * The `Web\Admin` sibling of {@see \App\Http\Controllers\Api\V1\Admin\
 * StaffController} — staff accounts and the role permission matrix. Super
 * Admin only: the route group carries `role:super_admin`, exactly as the API's
 * does, and the guards below are the ones a role check cannot express.
 *
 * The three that matter, carried over verbatim because each one is a support
 * incident if it is missing:
 *
 *   • **Nobody edits, suspends or deletes their own staff account here.**
 *     Locking yourself out of the admin panel with the admin panel cannot be
 *     fixed from inside the application.
 *   • **No Super Admin account can be edited or deleted through this
 *     surface.** Blocking every Super Admin target, rather than only the last
 *     one, is what makes "there is always at least one Super Admin" an
 *     invariant instead of a race between two of them. Promotion *to* Super
 *     Admin is allowed; the way back is the database, deliberately.
 *   • **The `super_admin` row is refused by the matrix.** `Gate::before`
 *     grants that role every dotted ability regardless of the pivot table, so
 *     its checkboxes would change nothing while appearing to change
 *     everything. A matrix that lies is worse than one that refuses.
 */
class StaffController extends Controller
{
    public function index(): View
    {
        $this->authorize('staff.view');

        return view('admin.staff.index', [
            'staff' => User::query()
                ->with('role:id,name,label')
                ->whereHas('role', fn ($q) => $q->where('name', '!=', 'user'))
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function create(): View
    {
        $this->authorize('staff.manage');

        return view('admin.staff.form', [
            'staff' => new User,
            'roles' => $this->assignableRoles(),
        ]);
    }

    public function store(StaffRequest $request, AuditLogger $audit): RedirectResponse
    {
        $this->authorize('staff.manage');

        $role = $this->staffRole($request->integer('role_id'));

        $staff = User::create($request->validated() + [
            'profile_code' => ProfileCode::generate(),
            'status' => 'active',

            // Created by a human who already knows who this is, so both
            // verifications are stamped: sending a staff member through the
            // OTP flow to reach an admin panel they were just given serves
            // nobody.
            'email_verified_at' => now(),
            'mobile_verified_at' => now(),

            // Not null on the users table and meaningless for staff, so the
            // panel does not ask. See StaffRequest.
            'gender' => 'male',
            'date_of_birth' => now()->subYears(30)->toDateString(),
        ]);

        $audit->log(
            'staff.created',
            $staff,
            newValues: ['email' => $staff->email, 'role' => $role->name],
            description: "Staff account created with role {$role->label}",
            actor: $request->user(),
        );

        return redirect()->route('admin.staff.index')->with('status', "{$staff->name} can now sign in as {$role->label}.");
    }

    public function edit(Request $request, User $staff): View
    {
        $this->authorize('staff.manage');

        abort_unless($staff->isStaff(), 404, 'Not found.');

        $this->assertActionable($request->user(), $staff);

        return view('admin.staff.form', [
            'staff' => $staff->load('role:id,name,label'),
            'roles' => $this->assignableRoles(),
        ]);
    }

    public function update(StaffRequest $request, User $staff, AuditLogger $audit): RedirectResponse
    {
        $this->authorize('staff.manage');

        $this->assertActionable($request->user(), $staff);

        if ($request->filled('role_id')) {
            $this->staffRole($request->integer('role_id'));
        }

        $attributes = $request->validated();

        // An empty password field on an edit form means "leave it alone", not
        // "set the password to nothing" — and the cast would hash the empty
        // string into a perfectly valid credential.
        if (blank($attributes['password'] ?? null)) {
            unset($attributes['password']);
        }

        $staff->fill($attributes);
        $staff->save();

        // Sanctum checks the token, not the account, on every request but the
        // login, so a suspended staff member with a live token keeps working
        // until it is revoked. The *browser* session needs no equivalent here:
        // `EnsureActiveWebUser` runs on every `admin/*` request and signs a
        // suspended account out on its next click — which is why the session
        // driver being redis rather than database costs nothing.
        if ($staff->wasChanged(['password', 'status', 'role_id'])) {
            $staff->tokens()->delete();
        }

        $audit->logChanges('staff.updated', $staff, 'Staff account edited', $request->user());

        return redirect()->route('admin.staff.index')->with('status', "{$staff->name}'s account updated.");
    }

    public function destroy(Request $request, User $staff, AuditLogger $audit): RedirectResponse
    {
        $this->authorize('staff.manage');

        abort_unless($staff->isStaff(), 404, 'Not found.');

        $this->assertActionable($request->user(), $staff);

        $audit->log(
            'staff.deleted',
            $staff,
            oldValues: ['email' => $staff->email, 'role_id' => $staff->role_id],
            description: 'Staff account removed',
            actor: $request->user(),
        );

        $staff->tokens()->delete();
        $staff->delete();

        return redirect()->route('admin.staff.index')->with('status', 'Staff account removed.');
    }

    // --- Roles & permissions -----------------------------------------------

    public function roles(): View
    {
        $this->authorize('staff.view');

        return view('admin.staff.roles', [
            'roles' => Role::query()
                ->with('permissions:id,name')
                ->whereNotIn('name', ['user', 'moderator', 'admin'])
                ->withCount('users')
                ->orderBy('id')
                ->get(),

            // Grouped exactly as the matrix renders it — `permissions.group`
            // *is* the UI, so a permission added to the seeder appears in the
            // right section with no view change.
            'permissionGroups' => Permission::query()
                ->orderBy('group')
                ->orderBy('name')
                ->get()
                ->groupBy('group'),
        ]);
    }

    public function updateRole(UpdateRoleFormRequest $request, Role $role, AuditLogger $audit): RedirectResponse
    {
        $this->authorize('staff.manage');

        if (in_array($role->name, ['super_admin', 'moderator'])) {
            return back()->withErrors([
                'permissions' => 'This role cannot be edited.',
            ]);
        }

        if ($role->name === 'user') {
            return back()->withErrors(['permissions' => 'The member role holds no admin permissions.']);
        }

        $before = $role->permissions->pluck('name')->all();

        $role->fill($request->safe()->only(['label', 'description']))->save();

        // The whole set, every time — not a diff. The matrix submits what was
        // on screen; a diff makes two admins disagree about that, and the
        // loser silently re-grants a permission the other just revoked.
        $role->permissions()->sync($request->input('permissions'));

        $after = $role->load('permissions')->permissions->pluck('name')->all();

        $audit->log(
            'role.permissions_updated',
            $role,
            oldValues: ['permissions' => $before],
            newValues: ['permissions' => $after],
            description: "Permissions changed for {$role->label}",
            actor: $request->user(),
        );

        // Staff holding this role keep their sessions: permissions are read
        // through the role on every check, so the change is live on their next
        // request. Signing them out would eject the whole moderation team to
        // add one checkbox.
        return back()->with('status', "Permissions updated for {$role->label}.");
    }

    // --- Internals ---------------------------------------------------------

    /** @return Collection<int, Role> */
    protected function assignableRoles()
    {
        // Only Super Admin and Support Staff are offered for staff assignment.
        return Role::query()
            ->whereIn('name', ['super_admin', 'support'])
            ->orderBy('id')
            ->get(['id', 'name', 'label']);
    }

    protected function staffRole(int $roleId): Role
    {
        $role = Role::findOrFail($roleId);

        abort_unless(
            in_array($role->name, ['super_admin', 'support']),
            422,
            'Choose a valid staff role (Super Admin or Support Staff).'
        );

        return $role;
    }

    protected function assertActionable(User $actor, User $staff): void
    {
        abort_if($actor->is($staff), 422, 'You cannot change your own staff account here.');

        abort_if(
            $staff->isSuperAdmin(),
            403,
            'A Super Admin account can only be changed from the database.',
        );
    }
}
