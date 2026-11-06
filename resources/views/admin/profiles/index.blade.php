@extends('admin.layouts.admin')

@section('title', 'Profile approvals — '.config('app.name').' Admin')

@section('admin-body')
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="font-serif text-3xl font-bold text-ink-900">Profile approvals</h1>
            <p class="mt-1 text-sm" style="color:var(--text-muted)">
                Oldest first. Approval is what makes a profile visible in search — until then the member can browse but nobody can find them.
            </p>
        </div>

        @if ($status === 'pending' && $profiles->isNotEmpty())
            <a href="{{ route('admin.profiles.show', $profiles->first()) }}"
               class="rounded-md bg-primary-500 px-4 py-2 text-sm font-medium text-white hover:bg-primary-600">
                Start reviewing ({{ $pendingCount }})
            </a>
        @endif
    </div>

    <div class="mt-4">
        <x-admin.data-table :rows="$profiles" :columns="['Profile ID', 'Name', 'Gender', 'Age', 'Location', 'Photos', 'Completion', 'Waiting since', '']">
            <x-slot:filters>
                <form method="GET" action="{{ route('admin.profiles.index') }}">
                    @include('admin.profiles._filters')
                </form>
            </x-slot:filters>

            @forelse ($profiles as $profile)
                <tr>
                    <td class="whitespace-nowrap px-4 py-2.5">
                        <a href="{{ route('admin.profiles.show', $profile) }}" class="font-mono text-primary-600 hover:underline">
                            {{ $profile->user?->profile_code }}
                        </a>
                    </td>
                    <td class="whitespace-nowrap px-4 py-2.5">{{ $profile->user?->name }}</td>
                    <td class="whitespace-nowrap px-4 py-2.5 capitalize">{{ $profile->user?->gender }}</td>
                    <td class="whitespace-nowrap px-4 py-2.5">{{ $profile->user?->age() }}</td>
                    <td class="whitespace-nowrap px-4 py-2.5">
                        {{ collect([$profile->city?->name, $profile->state?->name])->filter()->join(', ') ?: '—' }}
                    </td>
                    <td class="whitespace-nowrap px-4 py-2.5">{{ $profile->approved_photos_count }} approved</td>
                    <td class="whitespace-nowrap px-4 py-2.5">{{ $profile->completion_percentage }}%</td>
                    <td class="whitespace-nowrap px-4 py-2.5">
                        {{ $profile->created_at->diffForHumans(short: true) }}
                        <span class="block text-xs" style="color:var(--text-muted)">{{ $profile->created_at->format('d M Y') }}</span>
                    </td>
                    <td class="whitespace-nowrap px-4 py-2.5 text-right">
                        <a href="{{ route('admin.profiles.show', $profile) }}" class="text-sm font-medium text-primary-600 hover:underline">
                            {{ $status === 'pending' ? 'Review' : 'View' }}
                        </a>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" class="px-4 py-8 text-center text-sm" style="color:var(--text-muted)">
                        @if ($status === 'pending')
                            Nothing waiting. The queue is clear.
                        @else
                            No {{ $status }} profiles match these filters.
                        @endif
                    </td>
                </tr>
            @endforelse
        </x-admin.data-table>
    </div>
@endsection
