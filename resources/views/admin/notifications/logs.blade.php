@extends('admin.layouts.admin')

@section('title', 'Push Notification Logs — ' . config('app.name') . ' Admin')

@section('admin-body')
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="font-serif text-3xl font-bold text-ink-900">Push Notification Logs</h1>
            <p class="mt-1 text-sm" style="color:var(--text-muted)">
                Audit trail of outgoing Firebase push deliveries, statuses, payload data, and error details.
            </p>
        </div>
        <div>
            <a href="{{ route('admin.firebase.index') }}" class="rounded-xl bg-pink-600 px-4 py-2 text-sm font-bold text-white shadow hover:bg-pink-700 transition">
                ⚙️ Firebase Settings & Test
            </a>
        </div>
    </div>

    {{-- Filter Bar --}}
    <form method="GET" action="{{ route('admin.notification-logs.index') }}" class="mt-6 rounded-2xl border p-4 card" style="border-color:var(--border-default)">
        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 md:grid-cols-5">
            <div>
                <label for="filter-type" class="block text-xs font-bold uppercase tracking-wider text-ink-700">Type</label>
                <select id="filter-type" name="type" class="mt-1 w-full rounded-xl border px-3 py-2 text-xs" style="border-color:var(--border-default)">
                    <option value="">All Types</option>
                    @foreach ($types as $type)
                        <option value="{{ $type }}" @selected($selectedType === $type)>{{ $type }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="filter-status" class="block text-xs font-bold uppercase tracking-wider text-ink-700">Status</label>
                <select id="filter-status" name="status" class="mt-1 w-full rounded-xl border px-3 py-2 text-xs" style="border-color:var(--border-default)">
                    <option value="">All Statuses</option>
                    <option value="sent" @selected($selectedStatus === 'sent')>Sent</option>
                    <option value="failed" @selected($selectedStatus === 'failed')>Failed</option>
                </select>
            </div>

            <div>
                <label for="filter-user" class="block text-xs font-bold uppercase tracking-wider text-ink-700">User / Profile ID</label>
                <input id="filter-user" type="text" name="user" value="{{ $selectedUser }}" placeholder="Name, code, or mobile..."
                       class="mt-1 w-full rounded-xl border px-3 py-2 text-xs" style="border-color:var(--border-default)">
            </div>

            <div>
                <label for="filter-from" class="block text-xs font-bold uppercase tracking-wider text-ink-700">From Date</label>
                <input id="filter-from" type="date" name="from" value="{{ $fromDate }}"
                       class="mt-1 w-full rounded-xl border px-3 py-2 text-xs" style="border-color:var(--border-default)">
            </div>

            <div>
                <label for="filter-to" class="block text-xs font-bold uppercase tracking-wider text-ink-700">To Date</label>
                <input id="filter-to" type="date" name="to" value="{{ $toDate }}"
                       class="mt-1 w-full rounded-xl border px-3 py-2 text-xs" style="border-color:var(--border-default)">
            </div>
        </div>

        <div class="mt-3 flex items-center justify-end gap-2 border-t pt-3" style="border-color:var(--border-default)">
            <a href="{{ route('admin.notification-logs.index') }}" class="rounded-lg border px-3 py-1.5 text-xs font-medium" style="border-color:var(--border-default)">
                Reset
            </a>
            <button type="submit" class="rounded-lg bg-pink-600 px-4 py-1.5 text-xs font-bold text-white hover:bg-pink-700 transition">
                Filter Logs
            </button>
        </div>
    </form>

    {{-- Log Table --}}
    <div class="mt-6">
        <x-admin.data-table :rows="$logs" :columns="['ID', 'User', 'Type', 'Title & Body', 'Data Payload', 'Status', 'Timestamp']">
            @forelse ($logs as $log)
                <tr>
                    <td class="whitespace-nowrap px-4 py-3 font-mono text-xs text-ink-500">
                        #{{ $log->id }}
                    </td>
                    <td class="px-4 py-3 text-xs">
                        @if ($log->user)
                            <div class="font-semibold text-ink-900">{{ $log->user->name }}</div>
                            <div class="font-mono text-[11px] text-pink-700">{{ $log->user->profile_code }}</div>
                        @else
                            <span class="text-slate-400 italic">No User / Direct Token</span>
                        @endif
                    </td>
                    <td class="whitespace-nowrap px-4 py-3">
                        <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-700">
                            {{ $log->type }}
                        </span>
                    </td>
                    <td class="px-4 py-3 text-xs max-w-xs">
                        <div class="font-bold text-ink-900">{{ $log->title }}</div>
                        @if ($log->body)
                            <div class="mt-0.5 line-clamp-2 text-ink-600" style="color:var(--text-muted)">{{ $log->body }}</div>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-xs max-w-xs font-mono text-[11px]">
                        @if (!empty($log->data))
                            <details class="cursor-pointer">
                                <summary class="text-pink-600 hover:underline">View JSON ({{ count($log->data) }} keys)</summary>
                                <pre class="mt-1 rounded bg-slate-100 p-2 text-[10px] overflow-x-auto">{{ json_encode($log->data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                            </details>
                        @else
                            <span class="text-slate-400">-</span>
                        @endif
                    </td>
                    <td class="whitespace-nowrap px-4 py-3">
                        @if ($log->status === 'sent')
                            <span class="inline-flex items-center gap-1 rounded-full bg-emerald-100 px-2.5 py-0.5 text-xs font-bold text-emerald-800">
                                <span class="h-1.5 w-1.5 rounded-full bg-emerald-600"></span>
                                Sent
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1 rounded-full bg-rose-100 px-2.5 py-0.5 text-xs font-bold text-rose-800" title="{{ $log->error }}">
                                <span class="h-1.5 w-1.5 rounded-full bg-rose-600"></span>
                                Failed
                            </span>
                            @if ($log->error)
                                <div class="mt-1 max-w-xs text-[11px] text-rose-700 line-clamp-2 font-mono" title="{{ $log->error }}">
                                    {{ $log->error }}
                                </div>
                            @endif
                        @endif
                    </td>
                    <td class="whitespace-nowrap px-4 py-3 text-xs text-ink-500 font-mono">
                        {{ $log->created_at?->format('d M Y, h:i A') }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="px-4 py-8 text-center text-sm" style="color:var(--text-muted)">
                        No notification logs found. Test notifications or app activities will appear here.
                    </td>
                </tr>
            @endforelse
        </x-admin.data-table>
    </div>
@endsection
