@extends('admin.layouts.admin')

@section('title', 'Payments — '.config('app.name').' Admin')

@section('admin-body')
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="font-serif text-3xl font-bold text-ink-900">Payments</h1>
            <p class="mt-1 text-sm" style="color:var(--text-muted)">
                No totals on this page, deliberately — a sum over one page is revenue that is wrong by whatever did not fit. Revenue lives on the dashboard, over the whole window.
            </p>
        </div>

        @can('export', \App\Models\Payment::class)
            {{-- Carries the current filters, so the CSV is the list on screen
                 rather than the whole table. Queued: the file arrives as a
                 notification with a signed download. --}}
            <form method="POST" action="{{ route('admin.payments.export') }}">
                @csrf
                @foreach (request()->only('status', 'plan_id', 'from', 'to') as $key => $value)
                    <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                @endforeach
                <button type="submit" class="rounded-md border border-primary-500 px-4 py-2 text-sm font-medium text-primary-600 hover:bg-primary-50">
                    Export these results
                </button>
            </form>
        @endcan
    </div>

    <div class="mt-4">
        <x-admin.data-table :rows="$payments" :columns="['Invoice', 'Member', 'Plan', 'Amount', 'Method', 'Status', 'Date', '']">
            <x-slot:filters>
                <form method="GET" action="{{ route('admin.payments.index') }}" class="flex flex-wrap items-end gap-3">
                    <div>
                        <label for="q" class="block text-sm font-medium">Search</label>
                        <input id="q" name="q" type="text" value="{{ request('q') }}" maxlength="120"
                               placeholder="Invoice, Razorpay id, name or MAT code"
                               class="mt-1 w-64 rounded-md border px-3 py-2 text-sm" style="border-color:var(--border-default)">
                    </div>

                    <x-form.select name="status" label="Status" placeholder="Any status" :value="request('status')" :options="[
                        ['id' => 'created', 'name' => 'Created'],
                        ['id' => 'attempted', 'name' => 'Attempted'],
                        ['id' => 'paid', 'name' => 'Paid'],
                        ['id' => 'failed', 'name' => 'Failed'],
                        ['id' => 'refunded', 'name' => 'Refunded'],
                    ]" />

                    <x-form.select name="plan_id" label="Plan" placeholder="Any plan" :value="request('plan_id')"
                        :options="$plans->map(fn ($p) => ['id' => $p->id, 'name' => $p->name])->all()" />

                    <x-form.input name="from" label="From" type="date" :value="request('from')" />
                    <x-form.input name="to" label="To" type="date" :value="request('to')" />

                    <div class="flex items-end gap-2">
                        <button type="submit" class="rounded-md bg-primary-500 px-4 py-2 text-sm font-medium text-white hover:bg-primary-600">Filter</button>
                        <a href="{{ route('admin.payments.index') }}" class="rounded-md border px-4 py-2 text-sm font-medium" style="border-color:var(--border-default)">Clear</a>
                    </div>
                </form>
            </x-slot:filters>

            @forelse ($payments as $payment)
                <tr>
                    <td class="whitespace-nowrap px-4 py-2.5 font-mono">{{ $payment->invoice_number ?? '—' }}</td>
                    <td class="px-4 py-2.5">
                        @if ($payment->user)
                            <a href="{{ route('admin.users.show', $payment->user_id) }}" class="font-medium text-primary-600 hover:underline">
                                {{ $payment->user->name }}
                            </a>
                            <span class="block font-mono text-xs" style="color:var(--text-muted)">{{ $payment->user->profile_code }}</span>
                        @else
                            {{-- nullOnDelete on the FK: a deleted account must
                                 not erase the financial record. --}}
                            <span style="color:var(--text-muted)">Deleted account</span>
                        @endif
                    </td>
                    <td class="whitespace-nowrap px-4 py-2.5">{{ $payment->plan?->name ?? '—' }}</td>
                    <td class="whitespace-nowrap px-4 py-2.5">
                        {{ $payment->currency }} {{ number_format((float) $payment->amount, 2) }}
                        @if ($payment->refunded_amount)
                            <span class="block text-xs text-danger">−{{ number_format((float) $payment->refunded_amount, 2) }} refunded</span>
                        @endif
                    </td>
                    <td class="whitespace-nowrap px-4 py-2.5 capitalize">{{ $payment->method ?? '—' }}</td>
                    <td class="whitespace-nowrap px-4 py-2.5">
                        <span @class([
                            'rounded-full px-2 py-0.5 text-xs font-medium',
                            'bg-success/10 text-success' => $payment->status === 'paid',
                            'bg-danger/10 text-danger' => in_array($payment->status, ['failed', 'refunded'], true),
                            'bg-warning/10 text-warning' => in_array($payment->status, ['created', 'attempted'], true),
                        ])>{{ ucfirst($payment->status) }}</span>
                    </td>
                    <td class="whitespace-nowrap px-4 py-2.5">{{ $payment->created_at->format('d M Y') }}</td>
                    <td class="whitespace-nowrap px-4 py-2.5 text-right">
                        <a href="{{ route('admin.payments.show', $payment) }}" class="text-sm font-medium text-primary-600 hover:underline">View</a>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="px-4 py-8 text-center text-sm" style="color:var(--text-muted)">No payments match these filters.</td>
                </tr>
            @endforelse
        </x-admin.data-table>
    </div>
@endsection
