<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Actions\Auth\IssueToken;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AdminLoginRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\AuditLogger;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

/**
 * The staff session.
 *
 * Deliberately not `Actions\Auth\LoginUser`. That action is the member path:
 * it reverses a self-deactivation on login, routes an unverified mobile to the
 * OTP screen, and — the part that matters here — it would happily authenticate
 * a member against this endpoint. The token it minted would be inert against
 * `abilities:admin`, but the client would already have drawn an admin shell
 * around a 403, and the endpoint would have confirmed a working member
 * credential to whoever was probing it.
 *
 * So this path answers identically for "no such account", "wrong password",
 * "that is a member account" and "that staff account is suspended". Four
 * different truths, one sentence, and the timing is flat across all of them.
 */
class AdminAuthController extends Controller
{
    /**
     * Bcrypt hash of a value nothing can match. Duplicated from
     * `Actions\Auth\LoginUser` rather than shared: the two login paths must be
     * able to diverge without one silently changing the other's timing
     * behaviour, and this is a constant, not logic.
     */
    protected const DUMMY_HASH = '$2y$12$mB.OiKPzGbQeAG3wQOEqSuCe0kZAJgFbWFOgKDe1FOsSMlSCXtSlq';

    public function login(AdminLoginRequest $request, IssueToken $issueToken, AuditLogger $audit): JsonResponse
    {
        // Restricted to staff in the query itself, so a member row never
        // reaches the password check and cannot be distinguished by timing
        // from an address that has no account at all.
        $user = User::with('role.permissions')
            ->where('email', strtolower($request->string('email')->toString()))
            ->whereHas('role', fn ($q) => $q->where('name', '!=', 'user'))
            ->first();

        if (! Hash::check($request->input('password'), $user?->password ?? static::DUMMY_HASH) || ! $user) {
            Log::channel('security')->warning('Failed admin login', [
                'email' => $request->input('email'),
                'ip' => $request->ip(),
            ]);

            $this->fail();
        }

        if ($user->status !== 'active') {
            $this->fail();
        }

        $token = $issueToken($user, $request->deviceName(), ['admin']);

        // Staff sign-ins are audited, and successful ones more than failures:
        // the question an incident asks is "who was logged in when this
        // happened", and the answer has to be in the same table as the actions.
        $audit->log('admin.login', $user, description: 'Staff signed in', actor: $user);

        return ApiResponse::success(
            $this->sessionPayload($user, $token->plainTextToken),
            'Signed in successfully',
        );
    }

    /**
     * The permission list is the payload that matters here.
     *
     * The admin UI is permission-driven — every nav item, column and button is
     * rendered from this array — so it is returned in full rather than left to
     * be inferred from the role name. A UI that guesses at capabilities from a
     * role shows a support agent a Refund button that 403s when they press it.
     */
    public function me(Request $request): JsonResponse
    {
        $user = $request->user()->load('role.permissions');

        return ApiResponse::success($this->sessionPayload($user, null), 'Account retrieved successfully');
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return ApiResponse::success(null, 'Signed out successfully');
    }

    // --- Internals ---------------------------------------------------------

    /** @return array<string, mixed> */
    protected function sessionPayload(User $user, ?string $token): array
    {
        return array_filter([
            'token' => $token,
            'token_type' => $token ? 'Bearer' : null,
            'user' => new UserResource($user),
            'role' => [
                'name' => $user->role?->name,
                'label' => $user->role?->label,
            ],
            'permissions' => $user->isSuperAdmin()
                // A super admin bypasses every dotted ability in Gate::before,
                // so enumerating their row's permissions would understate what
                // they can actually do. `*` is what the client checks against.
                ? ['*']
                : $user->role?->permissions->pluck('name')->all() ?? [],
        ], fn ($v) => $v !== null);
    }

    protected function fail(): never
    {
        throw ValidationException::withMessages([
            'email' => ['These credentials do not match our records.'],
        ]);
    }
}
