@extends('admin.layouts.admin')

@section('title', $user->name.' — '.config('app.name').' Admin')

@section('admin-body')
    <a href="{{ route('admin.users.index') }}" class="text-sm font-medium text-primary-600 hover:underline">&larr; Members</a>

    <div class="mt-3 flex flex-wrap items-start justify-between gap-4">
        <div>
            <h1 class="font-serif text-3xl font-bold text-ink-900">{{ $user->name }} <span class="font-mono text-base" style="color:var(--text-muted)">{{ $user->profile_code }}</span></h1>
            <p class="mt-1 text-sm" style="color:var(--text-muted)">
                {{ ucfirst($user->gender) }} · {{ $user->age() }} yrs ·
                <span @class([
                    'font-medium',
                    'text-success' => $user->status === 'active',
                    'text-danger' => in_array($user->status, ['suspended', 'deleted'], true),
                    'text-warning' => in_array($user->status, ['pending', 'deactivated'], true),
                ])>{{ ucfirst($user->status) }}</span>
            </p>
        </div>

        {{-- Every action here mirrors Api\V1\Admin\UserManagementController's
             `action()`/`destroy()` one-for-one — same UserPolicy abilities,
             same UpdateUserStatus/RequestAccountDeletion Actions, so the same
             "cannot suspend yourself", "staff accounts are managed from
             Staff" rules apply and simply fail with a flashed error if
             tripped. See Web\Admin\UserController's docblock. --}}
        <div class="flex flex-wrap gap-2">
            @can('verify', \App\Models\User::class)
                @if (! $user->profile?->is_verified)
                    <form method="POST" action="{{ route('admin.users.action', [$user, 'verify']) }}">
                        @csrf
                        @method('PATCH')
                        <button type="submit" class="rounded-md border border-primary-500 px-3 py-1.5 text-sm font-medium text-primary-600 hover:bg-primary-50">
                            Verify profile
                        </button>
                    </form>
                @endif
            @endcan

            @can('suspend', $user)
                @if ($user->status === 'active')
                    <x-admin.confirm-dialog
                        id="suspend-{{ $user->id }}" trigger="Suspend"
                        title="Suspend {{ $user->name }}?"
                        :action="route('admin.users.action', [$user, 'suspend'])" method="PATCH"
                        :identifier="$user->profile_code" confirm-label="Suspend member"
                    >
                        <p style="color:var(--text-muted)">The member is signed out of every device immediately and cannot sign back in until reinstated.</p>
                        <label class="block font-medium">Reason</label>
                        <textarea name="reason" required minlength="5" maxlength="255" rows="2"
                                  class="mt-1 w-full rounded-md border px-3 py-2 text-sm" style="border-color:var(--border-default)"></textarea>
                    </x-admin.confirm-dialog>

                    <x-admin.confirm-dialog
                        id="block-{{ $user->id }}" trigger="Block"
                        title="Block {{ $user->name }}?"
                        :action="route('admin.users.action', [$user, 'block'])" method="PATCH"
                        :identifier="$user->profile_code" confirm-label="Block member"
                    >
                        <p style="color:var(--text-muted)">For policy violations rather than a temporary hold — recorded separately from Suspend in the audit trail.</p>
                        <label class="block font-medium">Reason</label>
                        <textarea name="reason" required minlength="5" maxlength="255" rows="2"
                                  class="mt-1 w-full rounded-md border px-3 py-2 text-sm" style="border-color:var(--border-default)"></textarea>
                    </x-admin.confirm-dialog>
                @else
                    <form method="POST" action="{{ route('admin.users.action', [$user, 'activate']) }}">
                        @csrf
                        @method('PATCH')
                        <button type="submit" class="rounded-md border border-primary-500 px-3 py-1.5 text-sm font-medium text-primary-600 hover:bg-primary-50">
                            Reinstate account
                        </button>
                    </form>
                @endif
            @endcan

            @can('delete', $user)
                <x-admin.confirm-dialog
                    id="delete-{{ $user->id }}" trigger="Delete" variant="danger"
                    title="Delete {{ $user->name }}'s account?"
                    :action="route('admin.users.destroy', $user)" method="DELETE"
                    :identifier="$user->profile_code" confirm-label="Delete account"
                >
                    <p style="color:var(--text-muted)">Soft-deleted, recoverable for 30 days — the same path a member's own deletion request takes.</p>
                    <label class="block font-medium">Reason</label>
                    <textarea name="reason" required minlength="5" maxlength="255" rows="2"
                              class="mt-1 w-full rounded-md border px-3 py-2 text-sm" style="border-color:var(--border-default)"></textarea>
                </x-admin.confirm-dialog>
            @endcan
        </div>
    </div>

    <div class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-3">
        <section class="card p-5 lg:col-span-2">
            <h2 class="card-title">Account</h2>
            <dl class="mt-3 grid grid-cols-1 gap-x-4 gap-y-2 text-sm sm:grid-cols-2">
                <x-profile.detail-row label="Email" :value="$user->email" />
                <x-profile.detail-row label="Mobile" :value="$user->mobile" />
                <x-profile.detail-row label="Email verified" :value="$user->email_verified_at ? 'Yes' : 'No'" />
                <x-profile.detail-row label="Mobile verified" :value="$user->mobile_verified_at ? 'Yes' : 'No'" />
                <x-profile.detail-row label="Registration source" :value="ucfirst($user->registration_source ?? '—')" />
                <x-profile.detail-row label="Registered" :value="$user->created_at->format('d M Y, H:i')" />
                <x-profile.detail-row label="Last login" :value="$user->last_login_at?->format('d M Y, H:i') ?? '—'" />
                <x-profile.detail-row label="Last active" :value="$user->last_active_at?->format('d M Y, H:i') ?? '—'" />
                @if ($user->suspension_reason)
                    <x-profile.detail-row label="Suspension reason" :value="$user->suspension_reason" />
                @endif
            </dl>
        </section>

        <section class="card p-5">
            <h2 class="card-title">Membership</h2>
            <dl class="mt-3 space-y-2 text-sm">
                <x-profile.detail-row label="Plan" :value="$user->activeSubscription?->plan?->name ?? 'Free'" />
                <x-profile.detail-row label="Subscription status" :value="$user->activeSubscription?->status ? ucfirst($user->activeSubscription->status) : '—'" />
                <x-profile.detail-row label="Expires" :value="$user->activeSubscription?->expires_at?->format('d M Y') ?? '—'" />
            </dl>
        </section>

        @if ($user->profile)
            <section class="card p-5 lg:col-span-2">
                <h2 class="card-title">Profile</h2>
                <dl class="mt-3 grid grid-cols-1 gap-x-4 gap-y-2 text-sm sm:grid-cols-2">
                    <x-profile.detail-row label="Approval status" :value="ucfirst($user->profile->approval_status)" />
                    <x-profile.detail-row label="Completion" :value="$user->profile->completion_percentage.'%'" />
                    <x-profile.detail-row label="Verified" :value="$user->profile->is_verified ? 'Yes' : 'No'" />
                    <x-profile.detail-row label="Religion" :value="$user->profile->religion?->name ?? '—'" />
                    <x-profile.detail-row label="Community" :value="$user->profile->community?->name ?? '—'" />
                    <x-profile.detail-row label="Mother tongue" :value="$user->profile->motherTongue?->name ?? '—'" />
                    <x-profile.detail-row label="Location" :value="collect([$user->profile->city?->name, $user->profile->state?->name])->filter()->join(', ') ?: '—'" />
                    @if ($user->profile->rejection_reason)
                        <x-profile.detail-row label="Rejection reason" :value="$user->profile->rejection_reason" />
                    @endif
                </dl>
            </section>

            @if ($user->photos->isNotEmpty())
                <section class="card p-5">
                    <h2 class="card-title">Photos ({{ $user->photos->count() }})</h2>
                    <div class="mt-3 grid grid-cols-3 gap-2">
                        @foreach ($user->photos as $photo)
                            <img src="{{ $photo->urlFor('thumbnail') }}" alt="" class="aspect-square w-full rounded-md object-cover">
                        @endforeach
                    </div>
                </section>
            @endif
        @endif
    </div>
@endsection
