@extends('admin.layouts.admin')

@section('title', 'Reports — '.config('app.name').' Admin')

@section('admin-body')
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="font-serif text-3xl font-bold text-ink-900">Reports</h1>
            <p class="mt-1 text-sm" style="color:var(--text-muted)">
                Oldest first. The report that has waited longest is the one nobody has looked at yet — {{ $openCount }} still open.
            </p>
        </div>
    </div>

    <div class="mt-4">
        <x-admin.data-table :rows="$reports" :columns="['Reported', 'History', 'Category', 'Reporter', 'Waiting', 'Status', 'Assigned to', '']">
            <x-slot:filters>
                <form method="GET" action="{{ route('admin.reports.index') }}" class="flex flex-wrap items-end gap-3">
                    <nav class="flex rounded-md border" style="border-color:var(--border-default)" aria-label="Report status">
                        @foreach (['' => 'All', 'open' => 'Open', 'investigating' => 'Investigating', 'action_taken' => 'Actioned', 'dismissed' => 'Dismissed'] as $value => $label)
                            <a href="{{ route('admin.reports.index', array_filter(['status' => $value]) + request()->only('category', 'mine', 'unassigned')) }}"
                               @class([
                                   'px-3 py-2 text-sm font-medium first:rounded-l-md last:rounded-r-md',
                                   'bg-primary-50 text-primary-700' => (string) request('status') === $value,
                               ])>{{ $label }}</a>
                        @endforeach
                    </nav>

                    <input type="hidden" name="status" value="{{ request('status') }}">

                    <x-form.select name="category" label="Category" placeholder="Any category" :value="request('category')"
                        :options="collect($categories)->map(fn ($label, $key) => ['id' => $key, 'name' => $label])->values()->all()" />

                    <label class="flex items-center gap-2 pb-2 text-sm font-medium">
                        <input type="checkbox" name="mine" value="1" @checked(request()->boolean('mine')) class="rounded border" style="border-color:var(--border-default)">
                        Assigned to me
                    </label>

                    <label class="flex items-center gap-2 pb-2 text-sm font-medium">
                        <input type="checkbox" name="unassigned" value="1" @checked(request()->boolean('unassigned')) class="rounded border" style="border-color:var(--border-default)">
                        Unassigned
                    </label>

                    <div class="flex items-end gap-2">
                        <button type="submit" class="rounded-md bg-primary-500 px-4 py-2 text-sm font-medium text-white hover:bg-primary-600">Filter</button>
                        <a href="{{ route('admin.reports.index') }}" class="rounded-md border px-4 py-2 text-sm font-medium" style="border-color:var(--border-default)">Clear</a>
                    </div>
                </form>
            </x-slot:filters>

            @forelse ($reports as $report)
                <tr>
                    <td class="whitespace-nowrap px-4 py-2.5">
                        <a href="{{ route('admin.users.show', $report->reported_user_id) }}" class="font-medium text-primary-600 hover:underline">
                            {{ $report->reportedUser?->name }}
                        </a>
                        <span class="block font-mono text-xs" style="color:var(--text-muted)">{{ $report->reportedUser?->profile_code }}</span>
                    </td>
                    <td class="whitespace-nowrap px-4 py-2.5">
                        {{-- The number that changes the decision, so it sits
                             second, not buried on the detail page. --}}
                        <span @class([
                            'rounded-full px-2 py-0.5 text-xs font-medium',
                            'bg-danger/10 text-danger' => $report->reports_against_count >= 3,
                            'bg-warning/10 text-warning' => $report->reports_against_count === 2,
                        ])>{{ $report->reports_against_count }} total</span>
                    </td>
                    <td class="whitespace-nowrap px-4 py-2.5">{{ $categories[$report->category] ?? ucfirst($report->category) }}</td>
                    <td class="whitespace-nowrap px-4 py-2.5">
                        {{ $report->reporter?->name }}
                        <span class="block font-mono text-xs" style="color:var(--text-muted)">{{ $report->reporter?->profile_code }}</span>
                    </td>
                    <td class="whitespace-nowrap px-4 py-2.5">{{ $report->created_at->diffForHumans(short: true) }}</td>
                    <td class="whitespace-nowrap px-4 py-2.5">
                        <span @class([
                            'rounded-full px-2 py-0.5 text-xs font-medium',
                            'bg-warning/10 text-warning' => $report->status === 'open',
                            'bg-primary-50 text-primary-700' => $report->status === 'investigating',
                            'bg-success/10 text-success' => $report->status === 'action_taken',
                        ])>{{ str_replace('_', ' ', ucfirst($report->status)) }}</span>
                    </td>
                    <td class="whitespace-nowrap px-4 py-2.5">{{ $report->assignee?->name ?? '—' }}</td>
                    <td class="whitespace-nowrap px-4 py-2.5 text-right">
                        <a href="{{ route('admin.reports.show', $report) }}" class="text-sm font-medium text-primary-600 hover:underline">Open</a>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="px-4 py-8 text-center text-sm" style="color:var(--text-muted)">No reports match these filters.</td>
                </tr>
            @endforelse
        </x-admin.data-table>
    </div>
@endsection
