<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Actions\Admin\UpdateUserStatus;
use App\Actions\Profile\RequestAccountDeletion;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Http\Requests\Admin\UserActionRequest;
use App\Http\Resources\Admin\AdminProfileResource;
use App\Http\Resources\Admin\AdminUserResource;
use App\Jobs\ExportUsers;
use App\Models\User;
use App\Models\UserProfile;
use App\Services\AuditLogger;
use App\Support\ApiResponse;
use App\Support\ProfileCode;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserManagementController extends Controller
{
    /**
     * The members table.
     *
     * Search is deliberately *not* `LIKE '%q%'` across four columns. That is a
     * full scan of the largest table in the schema on every keystroke of a
     * typeahead, and it is the query that takes an admin panel down on launch
     * day. Instead the shape of the term picks the column: a profile code or a
     * number is an exact lookup on a unique index, an address with an `@` is an
     * exact lookup on another, and a name is a prefix match that can still use
     * the index. Someone searching for a substring of a surname gets nothing,
     * which is a real limitation and the right trade at this size — the fix is
     * a full-text index, not a leading wildcard.
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', User::class);

        $request->validate([
            'q' => ['nullable', 'string', 'max:120'],
            'status' => ['nullable', 'in:pending,active,suspended,deactivated,deleted'],
            'gender' => ['nullable', 'in:male,female'],
            'approval_status' => ['nullable', 'in:pending,approved,rejected'],
            'verified' => ['nullable', 'boolean'],
            'plan_id' => ['nullable', 'integer'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $users = User::query()
            ->members()
            ->with([
                'role:id,name',
                'profile:id,user_id,approval_status,rejection_reason,is_verified,completion_percentage',
                'activeSubscription:subscriptions.id,subscriptions.user_id,subscriptions.membership_plan_id,subscriptions.status,subscriptions.expires_at',
                'activeSubscription.plan:id,name',
            ])
            ->withCount('photos')
            ->when($request->filled('q'), fn ($q) => $this->applySearch($q, trim($request->string('q')->toString())))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->input('status')))
            ->when($request->filled('gender'), fn ($q) => $q->where('gender', $request->input('gender')))
            ->when($request->filled('approval_status'), fn ($q) => $q->whereHas(
                'profile',
                fn ($p) => $p->where('approval_status', $request->input('approval_status')),
            ))
            ->when($request->filled('verified'), fn ($q) => $q->whereHas(
                'profile',
                fn ($p) => $p->where('is_verified', $request->boolean('verified')),
            ))
            ->when($request->integer('plan_id'), fn ($q, $planId) => $q->whereHas(
                'activeSubscription',
                fn ($s) => $s->where('membership_plan_id', $planId),
            ))
            ->when($request->date('from'), fn ($q, $from) => $q->where('created_at', '>=', $from->startOfDay()))
            ->when($request->date('to'), fn ($q, $to) => $q->where('created_at', '<=', $to->endOfDay()))
            ->latest('id')
            ->paginate($request->integer('per_page', 25));

        return ApiResponse::paginated($users, AdminUserResource::class, 'Members retrieved successfully');
    }

    /**
     * Queued, and answered with 202.
     *
     * A CSV of 128,000 members is a job, not a request: rendering it inline
     * holds a PHP worker for minutes behind a gateway timeout the admin will
     * then retry, producing two of them. The file lands on the private disk
     * and the download link arrives as a notification.
     */
    public function export(Request $request): JsonResponse
    {
        $this->authorize('export', User::class);

        $filters = $request->only(['status', 'gender', 'approval_status', 'verified', 'plan_id', 'from', 'to']);

        ExportUsers::dispatch($request->user()->id, $filters);

        return ApiResponse::success(
            ['queued' => true],
            'Export started. You will be notified when the file is ready.',
            202,
        );
    }

    public function show(Request $request, User $user): JsonResponse
    {
        $this->authorize('view', $user);

        $user->load([
            'role:id,name',
            'activeSubscription.plan:id,name',
            'profile' => fn ($q) => $q->with(UserProfile::WITH_LOOKUPS),
            'photos',
        ]);

        return ApiResponse::success([
            'user' => new AdminUserResource($user),
            // The full profile travels with the drill-down rather than behind a
            // second request: support answering "why can this member not be
            // found in search" needs the approval status, the completion
            // percentage and the photos in front of them at once.
            'profile' => $user->profile ? new AdminProfileResource($user->profile) : null,
        ], 'Member retrieved successfully');
    }

    public function update(UpdateUserRequest $request, User $user, AuditLogger $audit): JsonResponse
    {
        $this->authorize('update', $user);

        $user->fill($request->validated());

        // logChanges records only what actually moved, and returns null when
        // nothing did — a no-op PUT must not put a row in the trail saying an
        // admin edited an account they only looked at.
        $user->save();
        $audit->logChanges('user.updated', $user, 'Member details edited', $request->user());

        return ApiResponse::success(
            new AdminUserResource($user->fresh()->load('role:id,name', 'profile')),
            'Member updated',
        );
    }

    /**
     * Five actions behind one route, each with its own authorisation.
     *
     * `verify` is checked against the class rather than the record because
     * `UserPolicy::verify` asks only whether this staff member may grant
     * badges; the other four are per-record, because "you cannot suspend
     * yourself" and "staff accounts are managed from Staff" are rules about
     * *which* account is being acted on.
     */
    public function action(UserActionRequest $request, User $user, string $action, UpdateUserStatus $update): JsonResponse
    {
        $action === 'verify'
            ? $this->authorize('verify', User::class)
            : $this->authorize('suspend', $user);

        $user = $update($user, $action, $request->user(), $request->input('reason'));

        return ApiResponse::success(
            new AdminUserResource($user->load('role:id,name', 'profile')),
            match ($action) {
                'suspend' => 'Member suspended and signed out of every device',
                'block' => 'Member blocked and signed out of every device',
                'activate', 'unblock' => 'Member reinstated',
                default => 'Member verified',
            },
        );
    }

    /**
     * Soft-delete now, purge after the retention window — the same path a
     * member's own deletion request takes, because there must be exactly one
     * definition of what deleting an account does. The 30-day window is what
     * makes an admin deletion recoverable, and support will need that on the
     * day someone deletes the wrong row.
     */
    public function destroy(UserActionRequest $request, User $user, RequestAccountDeletion $delete): JsonResponse
    {
        $this->authorize('delete', $user);

        $delete($user, $request->input('reason'), $request->user());

        return ApiResponse::success(null, 'Member deleted. The account is recoverable for 30 days.');
    }

    // --- Internals ---------------------------------------------------------

    /** @param  Builder<User>  $query */
    protected function applySearch($query, string $term): void
    {
        if (str_starts_with(strtoupper($term), ProfileCode::PREFIX)) {
            $query->where('profile_code', strtoupper($term));

            return;
        }

        if (str_contains($term, '@')) {
            $query->where('email', strtolower($term));

            return;
        }

        // A run of digits is a mobile number, possibly without its country
        // code. Matching on the tail is the one wildcard worth paying for: it
        // is what a support agent actually types off a phone screen.
        if (preg_match('/^\+?\d[\d\s-]{5,}$/', $term)) {
            $query->where('mobile', 'like', '%'.preg_replace('/\D/', '', $term));

            return;
        }

        $query->where('name', 'like', $term.'%');
    }
}
