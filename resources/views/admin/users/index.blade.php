@extends('admin.layouts.admin')

@section('title', 'Members — '.config('app.name').' Admin')

@section('admin-body')
    <div class="flex flex-wrap items-center justify-between gap-3">
        <h1 class="font-serif text-3xl font-bold text-ink-900">Members</h1>

        @can('export', \App\Models\User::class)
            {{-- Carries the structured filters, so the CSV is the list on
                 screen rather than the whole table. Queued: the file arrives
                 as a notification with a signed download, and completing it
                 writes an audit row naming whoever asked — this is the single
                 most damaging artefact the platform emits. --}}
            <form method="POST" action="{{ route('admin.users.export') }}" class="text-right">
                @csrf
                @foreach (array_filter(request()->only('status', 'gender', 'approval_status', 'verified'), fn ($v) => $v !== null && $v !== '') as $key => $value)
                    <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                @endforeach
                <button type="submit" class="rounded-md border border-primary-500 px-4 py-2 text-sm font-medium text-primary-600 hover:bg-primary-50">
                    Export these results
                </button>

                {{-- `ExportUsers::query()` has no free-text search, so a term
                     typed above is not carried into the CSV. Said out loud
                     rather than silently exporting a wider set. --}}
                @if (request()->filled('q'))
                    <p class="mt-1 text-xs" style="color:var(--text-muted)">
                        The search term is not carried — the export applies the filters only.
                    </p>
                @endif
            </form>
        @endcan
    </div>

    <div class="mt-4">
        <x-admin.data-table :rows="$users" :columns="['Profile ID', 'Name', 'Gender', 'Age', 'Location', 'Membership', 'Verification', 'Status', 'Registered', '']">
            <x-slot:filters>
                <form method="GET" action="{{ route('admin.users.index') }}">
                    @include('admin.users._filters')
                </form>
            </x-slot:filters>

            @forelse ($users as $user)
                <tr>
                    <td class="whitespace-nowrap px-4 py-2.5">
                        <a href="{{ route('admin.users.show', $user) }}" class="font-mono text-primary-600 hover:underline">
                            {{ $user->profile_code }}
                        </a>
                    </td>
                    <td class="whitespace-nowrap px-4 py-2.5">{{ $user->name }}</td>
                    <td class="whitespace-nowrap px-4 py-2.5 capitalize">{{ $user->gender }}</td>
                    <td class="whitespace-nowrap px-4 py-2.5">{{ $user->age() }}</td>
                    <td class="whitespace-nowrap px-4 py-2.5">
                        {{ collect([$user->profile?->city?->name, $user->profile?->state?->name])->filter()->join(', ') ?: '—' }}
                    </td>
                    <td class="whitespace-nowrap px-4 py-2.5">{{ $user->activeSubscription?->plan?->name ?? 'Free' }}</td>
                    <td class="whitespace-nowrap px-4 py-2.5">
                        @if ($user->profile?->is_verified)
                            <span class="rounded-full bg-success/10 px-2 py-0.5 text-xs font-medium text-success">Verified</span>
                        @else
                            <span class="rounded-full px-2 py-0.5 text-xs font-medium" style="background-color:var(--bg-page);color:var(--text-muted)">Unverified</span>
                        @endif
                    </td>
                    <td class="whitespace-nowrap px-4 py-2.5">
                        <span @class([
                            'rounded-full px-2 py-0.5 text-xs font-medium',
                            'bg-success/10 text-success' => $user->status === 'active',
                            'bg-danger/10 text-danger' => in_array($user->status, ['suspended', 'deleted'], true),
                            'bg-warning/10 text-warning' => in_array($user->status, ['pending', 'deactivated'], true),
                        ])>
                            {{ ucfirst($user->status) }}
                        </span>
                    </td>
                    <td class="whitespace-nowrap px-4 py-2.5">{{ $user->created_at->format('d M Y') }}</td>
                    <td class="whitespace-nowrap px-4 py-2.5 text-right">
                        <a href="{{ route('admin.users.show', $user) }}" class="text-sm font-medium text-primary-600 hover:underline">View</a>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="10" class="px-4 py-8 text-center text-sm" style="color:var(--text-muted)">
                        No members match these filters.
                    </td>
                </tr>
            @endforelse
        </x-admin.data-table>
    </div>
@endsection
