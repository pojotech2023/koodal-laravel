<?php

namespace App\Http\Controllers\Web\Admin;

use App\Actions\Admin\UpdateUserStatus;
use App\Actions\Profile\RequestAccountDeletion;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UserActionRequest;
use App\Jobs\ExportUsers;
use App\Models\User;
use App\Models\UserProfile;
use App\Support\ProfileCode;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * The `Web\Admin` sibling of {@see \App\Http\Controllers\Api\V1\Admin\
 * UserManagementController} — same query, same search-term dispatch, same
 * `UserPolicy` abilities, same `UpdateUserStatus`/`RequestAccountDeletion`
 * Actions (both already write their own `AuditLogger` row, so this
 * controller writes none directly — see PROGRESS.md §5's "counters inside
 * it, event() after commit" rule: the audit write belongs to the Action that
 * knows what changed, not to every caller of it).
 *
 * No `AdminUserResource`/`AdminProfileResource` — a Blade view has no trust
 * boundary to gate fields across, so this hands the Eloquent models straight
 * to the view, per docs/05-website-guide.md §4 and PROGRESS.md §6
 * ("FormRequests are shared, Resources are not").
 */
class UserController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', User::class);

        $request->validate([
            'q' => ['nullable', 'string', 'max:120'],
            'status' => ['nullable', 'in:pending,active,suspended,deactivated,deleted'],
            'gender' => ['nullable', 'in:male,female'],
            'approval_status' => ['nullable', 'in:pending,approved,rejected'],
            'verified' => ['nullable', 'boolean'],
        ]);

        $users = User::query()
            ->members()
            ->with([
                'role:id,name',
                'profile:id,user_id,city_id,state_id,approval_status,is_verified,completion_percentage',
                'profile.city:id,name',
                'profile.state:id,name',
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
            ->latest('id')
            ->paginate(25)
            ->withQueryString();

        return view('admin.users.index', ['users' => $users]);
    }

    public function show(User $user): View
    {
        $this->authorize('view', $user);

        $user->load([
            'role:id,name',
            'activeSubscription.plan:id,name',
            'profile' => fn ($q) => $q->with(UserProfile::WITH_LOOKUPS),
            'photos',
        ]);

        return view('admin.users.show', ['user' => $user]);
    }

    /**
     * One route, five actions — mirrors the API's `action()` one-for-one,
     * including which ability gates which action. `verify` is a class-level
     * check (may this staff member grant badges at all); the other four are
     * per-record, because "you cannot suspend yourself" and "staff accounts
     * are managed from Staff" are rules about *which* account, not who is
     * asking — see `UserPolicy::suspend()`.
     */
    public function action(UserActionRequest $request, User $user, string $action, UpdateUserStatus $update): RedirectResponse
    {
        $action === 'verify'
            ? $this->authorize('verify', User::class)
            : $this->authorize('suspend', $user);

        $update($user, $action, $request->user(), $request->input('reason'));

        return back()->with('status', match ($action) {
            'suspend' => 'Member suspended and signed out of every device.',
            'block' => 'Member blocked and signed out of every device.',
            'activate', 'unblock' => 'Member reinstated.',
            default => 'Member verified.',
        });
    }

    /**
     * Soft-delete, 30-day grace — the same `RequestAccountDeletion` a member
     * uses on their own account, so there is exactly one definition of what
     * deleting an account does. See `UserManagementController::destroy()`.
     */
    public function destroy(UserActionRequest $request, User $user, RequestAccountDeletion $delete): RedirectResponse
    {
        $this->authorize('delete', $user);

        $delete($user, $request->input('reason'), $request->user());

        return redirect()->route('admin.users.index')
            ->with('status', 'Member deleted. The account is recoverable for 30 days.');
    }

    /**
     * Queue the member CSV. Mirrors `UserManagementController::export()` — same
     * `export` ability, same `ExportUsers` job, same fire-and-notify shape; the
     * file arrives as an `export_ready` notification with a signed download,
     * never as a response to this request.
     *
     * Two deliberate differences from the API's one-liner, both forced by the
     * fact that this is submitted by a *browser form* rather than composed by
     * a client:
     *
     * 1. **Empty strings are stripped before dispatch.** A GET filter form
     *    submits every select, so "Any" arrives as `verified => ''`. The API
     *    is handed a JSON object where an unused filter is simply absent, but
     *    `ExportUsers::query()` gates that one filter on `isset()` rather than
     *    truthiness (it has to — `verified => '0'` is a real filter), so an
     *    empty string would silently export *unverified members only*. The
     *    `'0'`-preserving filter below is why this is not `array_filter()`.
     * 2. **`q` is not carried.** `ExportUsers::query()` has no free-text
     *    search — the dispatch in `applySearch()` below runs against Eloquent
     *    on this side only. The view says so next to the button rather than
     *    quietly exporting a wider set than the list on screen.
     */
    public function export(Request $request): RedirectResponse
    {
        $this->authorize('export', User::class);

        $filters = array_filter(
            $request->only(['status', 'gender', 'approval_status', 'verified', 'plan_id', 'from', 'to']),
            fn ($value) => $value !== null && $value !== '',
        );

        ExportUsers::dispatch($request->user()->id, $filters);

        return back()->with('status', 'Export started. You will be notified when the file is ready to download.');
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

        if (preg_match('/^\+?\d[\d\s-]{5,}$/', $term)) {
            $query->where('mobile', 'like', '%'.preg_replace('/\D/', '', $term));

            return;
        }

        $query->where('name', 'like', $term.'%');
    }
}
