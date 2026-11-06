@extends('admin.layouts.admin')

@section('title', 'ID verification — '.config('app.name').' Admin')

@section('admin-body')
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="font-serif text-3xl font-bold text-ink-900">ID verification</h1>
            <p class="mt-1 text-sm" style="color:var(--text-muted)">
                Government IDs. Opening a document is itself recorded in the audit trail, with your name on it.
            </p>
        </div>
    </div>

    <div class="mt-4">
        <x-admin.data-table :rows="$documents" :columns="['Profile ID', 'Member', 'Document', 'Submitted', 'Status', 'Reviewed by', '']">
            <x-slot:filters>
                <form method="GET" action="{{ route('admin.verification.index') }}" class="flex flex-wrap items-end gap-3">
                    <nav class="flex rounded-md border" style="border-color:var(--border-default)" aria-label="Queue status">
                        @foreach (['pending' => 'Pending', 'resubmit' => 'Resubmit asked', 'approved' => 'Approved', 'rejected' => 'Rejected'] as $value => $label)
                            <a href="{{ route('admin.verification.index', ['status' => $value] + request()->only('type')) }}"
                               @class([
                                   'px-4 py-2 text-sm font-medium first:rounded-l-md last:rounded-r-md',
                                   'bg-primary-50 text-primary-700' => $status === $value,
                               ])
                               @if ($status === $value) aria-current="page" @endif>
                                {{ $label }}
                                @if ($value === 'pending' && $pendingCount > 0)
                                    <span class="ml-1 rounded-full bg-warning/15 px-1.5 py-0.5 text-xs text-warning">{{ $pendingCount }}</span>
                                @endif
                            </a>
                        @endforeach
                    </nav>

                    <input type="hidden" name="status" value="{{ $status }}">

                    <x-form.select name="type" label="Document type" placeholder="Any type" :value="request('type')"
                        :options="collect($types)->map(fn ($label, $key) => ['id' => $key, 'name' => $label])->values()->all()" />

                    <div class="flex items-end gap-2">
                        <button type="submit" class="rounded-md bg-primary-500 px-4 py-2 text-sm font-medium text-white hover:bg-primary-600">Filter</button>
                        <a href="{{ route('admin.verification.index') }}" class="rounded-md border px-4 py-2 text-sm font-medium" style="border-color:var(--border-default)">Clear</a>
                    </div>
                </form>
            </x-slot:filters>

            @forelse ($documents as $document)
                <tr>
                    <td class="whitespace-nowrap px-4 py-2.5">
                        <a href="{{ route('admin.users.show', $document->user_id) }}" class="font-mono text-primary-600 hover:underline">
                            {{ $document->user?->profile_code }}
                        </a>
                    </td>
                    <td class="whitespace-nowrap px-4 py-2.5">{{ $document->user?->name }}</td>
                    <td class="whitespace-nowrap px-4 py-2.5">{{ $types[$document->type] ?? ucfirst($document->type) }}</td>
                    <td class="whitespace-nowrap px-4 py-2.5">
                        {{ $document->created_at->diffForHumans(short: true) }}
                        <span class="block text-xs" style="color:var(--text-muted)">{{ $document->created_at->format('d M Y') }}</span>
                    </td>
                    <td class="whitespace-nowrap px-4 py-2.5">
                        <span @class([
                            'rounded-full px-2 py-0.5 text-xs font-medium',
                            'bg-warning/10 text-warning' => in_array($document->status, ['pending', 'resubmit'], true),
                            'bg-success/10 text-success' => $document->status === 'approved',
                            'bg-danger/10 text-danger' => $document->status === 'rejected',
                        ])>{{ ucfirst($document->status) }}</span>
                    </td>
                    <td class="whitespace-nowrap px-4 py-2.5">
                        {{ $document->reviewer?->name ?? '—' }}
                        @if ($document->reviewed_at)
                            <span class="block text-xs" style="color:var(--text-muted)">{{ $document->reviewed_at->format('d M Y') }}</span>
                        @endif
                    </td>
                    <td class="whitespace-nowrap px-4 py-2.5 text-right">
                        {{-- "Open" rather than "Review": following this link
                             mints a signed URL and writes an audit row, and
                             the label should say so before it is clicked. --}}
                        <a href="{{ route('admin.verification.show', $document) }}" class="text-sm font-medium text-primary-600 hover:underline">
                            Open document
                        </a>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="px-4 py-8 text-center text-sm" style="color:var(--text-muted)">
                        @if ($status === 'pending')
                            Nothing waiting. The queue is clear.
                        @else
                            No documents in this state.
                        @endif
                    </td>
                </tr>
            @endforelse
        </x-admin.data-table>
    </div>
@endsection
