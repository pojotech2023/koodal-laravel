@extends('admin.layouts.admin')

@section('title', 'Audit log — '.config('app.name').' Admin')

@section('admin-body')
    <h1 class="font-serif text-3xl font-bold text-ink-900">Audit log</h1>
    <p class="mt-1 text-sm" style="color:var(--text-muted)">
        Read-only, and nothing in this panel can edit or delete a row. Entries age out after {{ config('matrimony.retention.audit_logs') }} days by the retention sweep, and by nothing else.
    </p>

    <div class="mt-4">
        <x-admin.data-table :rows="$logs" :columns="['When', 'Who', 'Action', 'Subject', 'Detail', 'IP']">
            <x-slot:filters>
                <form method="GET" action="{{ route('admin.audit') }}" class="flex flex-wrap items-end gap-3">
                    <x-form.select name="action" label="Action" placeholder="Any action" :value="request('action')"
                        :options="$actions->map(fn ($a) => ['id' => $a, 'name' => $a])->all()" />

                    <x-form.input name="auditable_type" label="Subject type" :value="request('auditable_type')"
                        hint="Short class name — User, Payment, UserProfile" />

                    <x-form.input name="auditable_id" label="Subject id" type="number" min="1" :value="request('auditable_id')" />
                    <x-form.input name="user_id" label="Staff user id" type="number" min="1" :value="request('user_id')" />
                    <x-form.input name="from" label="From" type="date" :value="request('from')" />
                    <x-form.input name="to" label="To" type="date" :value="request('to')" />

                    <div class="flex items-end gap-2">
                        <button type="submit" class="rounded-md bg-primary-500 px-4 py-2 text-sm font-medium text-white hover:bg-primary-600">Filter</button>
                        <a href="{{ route('admin.audit') }}" class="rounded-md border px-4 py-2 text-sm font-medium" style="border-color:var(--border-default)">Clear</a>
                    </div>
                </form>
            </x-slot:filters>

            @forelse ($logs as $log)
                <tr>
                    <td class="whitespace-nowrap px-4 py-2.5">
                        {{ $log->created_at->format('d M Y') }}
                        <span class="block text-xs" style="color:var(--text-muted)">{{ $log->created_at->format('H:i:s') }}</span>
                    </td>
                    <td class="whitespace-nowrap px-4 py-2.5">
                        {{-- Null means the system did it — a queued job or a
                             webhook, not a person. Saying so beats an empty
                             cell that reads as missing data. --}}
                        {{ $log->user?->name ?? 'System' }}
                        @if ($log->user)
                            <span class="block text-xs" style="color:var(--text-muted)">{{ $log->user->email }}</span>
                        @endif
                    </td>
                    <td class="whitespace-nowrap px-4 py-2.5 font-mono text-xs">{{ $log->action }}</td>
                    <td class="whitespace-nowrap px-4 py-2.5">
                        @if ($log->auditable_type)
                            {{ class_basename($log->auditable_type) }} #{{ $log->auditable_id }}
                        @else
                            —
                        @endif
                    </td>
                    <td class="px-4 py-2.5">
                        <p>{{ $log->description ?: '—' }}</p>

                        @if ($log->old_values || $log->new_values)
                            {{-- Collapsed by default: a plan edit's diff is a
                                 dozen keys and every row would push the next
                                 one off the screen. <details> rather than
                                 Alpine — no JS needed to read an audit
                                 trail. --}}
                            <details class="mt-1">
                                <summary class="cursor-pointer text-xs" style="color:var(--text-muted)">Changed values</summary>
                                <dl class="mt-1 space-y-0.5 text-xs">
                                    @foreach (array_keys(($log->new_values ?? []) + ($log->old_values ?? [])) as $key)
                                        <div class="flex flex-wrap gap-1">
                                            <dt class="font-medium">{{ $key }}:</dt>
                                            <dd style="color:var(--text-muted)">
                                                <span class="line-through">{{ json_encode(data_get($log->old_values, $key)) }}</span>
                                                &rarr; {{ json_encode(data_get($log->new_values, $key)) }}
                                            </dd>
                                        </div>
                                    @endforeach
                                </dl>
                            </details>
                        @endif
                    </td>
                    <td class="whitespace-nowrap px-4 py-2.5 font-mono text-xs" style="color:var(--text-muted)">{{ $log->ip_address ?? '—' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="px-4 py-8 text-center text-sm" style="color:var(--text-muted)">No entries match these filters.</td>
                </tr>
            @endforelse
        </x-admin.data-table>
    </div>
@endsection
