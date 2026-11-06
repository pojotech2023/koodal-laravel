<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StaffRequest;
use App\Http\Requests\Admin\UpdateRoleRequest;
use App\Http\Resources\Admin\StaffResource;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Services\AuditLogger;
use App\Support\ApiResponse;
use App\Support\ProfileCode;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Staff accounts and the role permission matrix. Super Admin only — the route
 * group carries `role:super_admin`, and the guards in here are the ones a role
 * check cannot express.
 *
 * Three of those guards exist because of how badly the alternative fails:
 *
 *   • **Nobody edits, suspends or deletes their own staff account.** Locking
 *     yourself out of the admin panel with the admin panel is a support
 *     incident that cannot be fixed from inside the application.
 *   • **No Super Admin account can be edited or deleted through this API.**
 *     Two people with the same unrestricted power and no tie-break is a race
 *     to lock each other out, and it also removes the "last Super Admin" case
 *     entirely — a platform with no Super Admin has no path back to having
 *     one, and no sequence of calls here can produce that state. Promotion
 *     *to* Super Admin is allowed; the way back is the database, deliberately.
 */
class StaffController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->authorize('staff.view');

        $staff = User::query()
            ->with('role.permissions:id,name')
            ->whereHas('role', fn ($q) => $q->where('name', '!=', 'user'))
            ->orderBy('name')
            ->get();

        return ApiResponse::success(
            StaffResource::collection($staff)->resolve(),
            'Staff retrieved successfully',
        );
    }

    public function store(StaffRequest $request, AuditLogger $audit): JsonResponse
    {
        $this->authorize('staff.manage');

        $role = $this->staffRole($request->integer('role_id'));

        $staff = User::create($request->validated() + [
            'profile_code' => ProfileCode::generate(),
            'status' => 'active',
            // Created by a human who already knows who this is, so both
            // verifications are stamped: sending a staff member through the OTP
            // flow to reach an admin panel they were just given serves nobody.
            'email_verified_at' => now(),
            'mobile_verified_at' => now(),
            'gender' => $request->input('gender', 'male'),
            'date_of_birth' => $request->input('date_of_birth', now()->subYears(30)->toDateString()),
        ]);

        $audit->log(
            'staff.created',
            $staff,
            newValues: ['email' => $staff->email, 'role' => $role->name],
            description: "Staff account created with role {$role->label}",
            actor: $request->user(),
        );

        return ApiResponse::created(
            new StaffResource($staff->load('role.permissions:id,name')),
            'Staff account created',
        );
    }

    public function show(Request $request, User $staff): JsonResponse
    {
        $this->authorize('staff.view');

        abort_unless($staff->isStaff(), 404, 'Not found.');

        return ApiResponse::success(
            new StaffResource($staff->load('role.permissions:id,name')),
            'Staff account retrieved successfully',
        );
    }

    public function update(StaffRequest $request, User $staff, AuditLogger $audit): JsonResponse
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

        // Changing a password or suspending an account has to end the sessions
        // it was protecting. Sanctum checks the token, not the account, on
        // every request but the login.
        if ($staff->wasChanged(['password', 'status', 'role_id'])) {
            $staff->tokens()->delete();
        }

        $audit->logChanges('staff.updated', $staff, 'Staff account edited', $request->user());

        return ApiResponse::success(
            new StaffResource($staff->fresh()->load('role.permissions:id,name')),
            'Staff account updated',
        );
    }

    public function destroy(Request $request, User $staff, AuditLogger $audit): JsonResponse
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

        return ApiResponse::success(null, 'Staff account removed');
    }

    // --- Roles & permissions -----------------------------------------------

    public function roles(Request $request): JsonResponse
    {
        $this->authorize('staff.view');

        $roles = Role::with('permissions:id,name,group,label')
            ->where('name', '!=', 'user')
            ->withCount('users')
            ->orderBy('id')
            ->get()
            ->map(fn (Role $role) => [
                'id' => $role->id,
                'name' => $role->name,
                'label' => $role->label,
                'description' => $role->description,
                'is_system' => (bool) $role->is_system,
                'staff_count' => $role->users_count,
                'permissions' => $role->permissions->pluck('id'),
            ]);

        return ApiResponse::success($roles, 'Roles retrieved successfully');
    }

    /**
     * Replaces a role's permission set wholesale.
     *
     * `super_admin` is refused, and not for safety theatre: `Gate::before`
     * grants that role every dotted ability regardless of what the pivot table
     * says, so editing its checkboxes changes nothing while appearing to
     * change everything. A matrix that lies is worse than one that refuses.
     */
    public function updateRole(UpdateRoleRequest $request, Role $role, AuditLogger $audit): JsonResponse
    {
        $this->authorize('staff.manage');

        abort_if(
            $role->name === 'super_admin',
            422,
            'Super Admin bypasses permission checks entirely, so its matrix has no effect. Edit another role.',
        );

        abort_if($role->name === 'user', 422, 'The member role holds no admin permissions.');

        $before = $role->permissions->pluck('name')->all();

        $role->fill($request->safe()->only(['label', 'description']))->save();
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

        // Staff holding this role keep their tokens: a permission change takes
        // effect on the next request because permissions are read through the
        // role on every check. Revoking their sessions would sign out the whole
        // moderation team to add one checkbox.
        return ApiResponse::success(
            ['permissions' => $after],
            'Role permissions updated',
        );
    }

    /** Grouped exactly as the matrix renders — `permissions.group` is the UI. */
    public function permissions(Request $request): JsonResponse
    {
        $this->authorize('staff.view');

        $permissions = Permission::orderBy('group')->orderBy('name')->get()
            ->groupBy('group')
            ->map(fn ($rows) => $rows->map(fn (Permission $p) => [
                'id' => $p->id,
                'name' => $p->name,
                'label' => $p->label,
            ])->values());

        return ApiResponse::success($permissions, 'Permissions retrieved successfully');
    }

    // --- Internals ---------------------------------------------------------

    protected function staffRole(int $roleId): Role
    {
        $role = Role::findOrFail($roleId);

        // The member role is not a staff role. Assigning it here would create
        // an account that passes `role:super_admin,admin,…` nowhere and holds a
        // profile-less member record forever.
        abort_if($role->name === 'user', 422, 'Choose a staff role.');

        return $role;
    }

    /**
     * The two guards a `role:super_admin` middleware cannot express.
     *
     * Blocking every Super Admin target — rather than only the last one — is
     * what makes "there is always at least one Super Admin" an invariant of
     * this controller instead of a race between two of them.
     */
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
