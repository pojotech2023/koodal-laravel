@extends('admin.layouts.admin')

@section('title', 'Staff &amp; roles — '.config('app.name').' Admin')

@section('admin-body')
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="font-serif text-3xl font-bold text-ink-900">Staff &amp; roles</h1>
            <p class="mt-1 text-sm" style="color:var(--text-muted)">
                Super Admin only. You cannot edit your own account here, and no Super Admin account can be changed from this screen at all — that is what keeps at least one of them always able to get in.
            </p>
        </div>

        <div class="flex gap-2">
            <a href="{{ route('admin.staff.roles') }}" class="rounded-md border px-4 py-2 text-sm font-medium" style="border-color:var(--border-default)">
                Role permissions
            </a>
            @can('staff.manage')
                <a href="{{ route('admin.staff.create') }}" class="rounded-md bg-primary-500 px-4 py-2 text-sm font-medium text-white hover:bg-primary-600">
                    Add staff
                </a>
            @endcan
        </div>
    </div>

    <div class="mt-4">
        <x-admin.data-table :rows="$staff" :columns="['Name', 'Email', 'Mobile', 'Role', 'Status', 'Last login', '']">
            @forelse ($staff as $member)
                @php($isSelf = $member->is(Auth::guard('web')->user()))

                <tr>
                    <td class="px-4 py-2.5">
                        {{ $member->name }}
                        @if ($isSelf)
                            <span class="ml-1 rounded-full bg-primary-50 px-2 py-0.5 text-xs font-medium text-primary-700">You</span>
                        @endif
                    </td>
                    <td class="whitespace-nowrap px-4 py-2.5">{{ $member->email }}</td>
                    <td class="whitespace-nowrap px-4 py-2.5">{{ $member->mobile }}</td>
                    <td class="whitespace-nowrap px-4 py-2.5">{{ $member->role?->label }}</td>
                    <td class="whitespace-nowrap px-4 py-2.5">
                        <span @class([
                            'rounded-full px-2 py-0.5 text-xs font-medium',
                            'bg-success/10 text-success' => $member->status === 'active',
                            'bg-danger/10 text-danger' => $member->status !== 'active',
                        ])>{{ ucfirst($member->status) }}</span>
                    </td>
                    <td class="whitespace-nowrap px-4 py-2.5">{{ $member->last_login_at?->format('d M Y, H:i') ?? 'Never' }}</td>
                    <td class="whitespace-nowrap px-4 py-2.5 text-right">
                        @can('staff.manage')
                            @if ($isSelf || $member->isSuperAdmin())
                                {{-- Both guards are enforced server-side in
                                     `assertActionable()`; this only avoids
                                     offering a button that always refuses. --}}
                                <span class="text-xs" style="color:var(--text-muted)">
                                    {{ $isSelf ? 'Your own account' : 'Super Admin — database only' }}
                                </span>
                            @else
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ route('admin.staff.edit', $member) }}" class="text-sm font-medium text-primary-600 hover:underline">Edit</a>

                                    <x-admin.confirm-dialog
                                        id="delete-staff-{{ $member->id }}" trigger="Remove" variant="danger"
                                        title="Remove {{ $member->name }}'s staff account?"
                                        :action="route('admin.staff.destroy', $member)" method="DELETE"
                                        :identifier="$member->email" confirm-label="Remove account"
                                    >
                                        <p style="color:var(--text-muted)">
                                            Their API tokens are revoked and the account is soft-deleted. Everything they did stays in the audit trail under their name.
                                        </p>
                                    </x-admin.confirm-dialog>
                                </div>
                            @endif
                        @endcan
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="px-4 py-8 text-center text-sm" style="color:var(--text-muted)">
                        No staff accounts. `StaffSeeder` normally creates the first Super Admin.
                    </td>
                </tr>
            @endforelse
        </x-admin.data-table>
    </div>
@endsection
