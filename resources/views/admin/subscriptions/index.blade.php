@extends('admin.layouts.admin')

@section('title', 'Subscriptions — '.config('app.name').' Admin')

@section('admin-body')
    <h1 class="font-serif text-3xl font-bold text-ink-900">Subscriptions</h1>
    <p class="mt-1 text-sm" style="color:var(--text-muted)">
        What each member actually holds. Extending or cancelling here changes the entitlement only — money is the payments module's job.
    </p>

    <div class="mt-4">
        <x-admin.data-table :rows="$subscriptions" :columns="['Member', 'Plan', 'Started', 'Expires', 'Payment', 'Status', '']">
            <x-slot:filters>
                <form method="GET" action="{{ route('admin.subscriptions.index') }}" class="flex flex-wrap items-end gap-3">
                    <div>
                        <label for="q" class="block text-sm font-medium">Member</label>
                        <input id="q" name="q" type="text" value="{{ request('q') }}" maxlength="120" placeholder="Name or MAT code"
                               class="mt-1 w-56 rounded-md border px-3 py-2 text-sm" style="border-color:var(--border-default)">
                    </div>

                    <x-form.select name="status" label="Status" placeholder="Any status" :value="request('status')" :options="[
                        ['id' => 'pending', 'name' => 'Pending'],
                        ['id' => 'active', 'name' => 'Active'],
                        ['id' => 'expired', 'name' => 'Expired'],
                        ['id' => 'cancelled', 'name' => 'Cancelled'],
                        ['id' => 'superseded', 'name' => 'Superseded'],
                    ]" />

                    <x-form.select name="plan_id" label="Plan" placeholder="Any plan" :value="request('plan_id')"
                        :options="$plans->map(fn ($p) => ['id' => $p->id, 'name' => $p->name])->all()" />

                    {{-- The renewals worklist: the same windows
                         SendExpiryReminders fires on, so support sees exactly
                         the set that has been or is about to be nudged. --}}
                    <x-form.select name="expiring_in" label="Expiring within" placeholder="Any time" :value="request('expiring_in')" :options="[
                        ['id' => '1', 'name' => '1 day'],
                        ['id' => '3', 'name' => '3 days'],
                        ['id' => '7', 'name' => '7 days'],
                        ['id' => '30', 'name' => '30 days'],
                    ]" />

                    <div class="flex items-end gap-2">
                        <button type="submit" class="rounded-md bg-primary-500 px-4 py-2 text-sm font-medium text-white hover:bg-primary-600">Filter</button>
                        <a href="{{ route('admin.subscriptions.index') }}" class="rounded-md border px-4 py-2 text-sm font-medium" style="border-color:var(--border-default)">Clear</a>
                    </div>
                </form>
            </x-slot:filters>

            @forelse ($subscriptions as $subscription)
                <tr>
                    <td class="px-4 py-2.5">
                        <a href="{{ route('admin.users.show', $subscription->user_id) }}" class="font-medium text-primary-600 hover:underline">
                            {{ $subscription->user?->name }}
                        </a>
                        <span class="block font-mono text-xs" style="color:var(--text-muted)">{{ $subscription->user?->profile_code }}</span>
                    </td>
                    <td class="whitespace-nowrap px-4 py-2.5">{{ $subscription->plan?->name }}</td>
                    <td class="whitespace-nowrap px-4 py-2.5">{{ $subscription->starts_at?->format('d M Y') }}</td>
                    <td class="whitespace-nowrap px-4 py-2.5">
                        @if ($subscription->expires_at)
                            {{ $subscription->expires_at->format('d M Y') }}
                            @if ($subscription->isActive())
                                <span class="block text-xs" style="color:var(--text-muted)">{{ $subscription->daysRemaining() }} days left</span>
                            @endif
                        @else
                            Never
                        @endif
                    </td>
                    <td class="whitespace-nowrap px-4 py-2.5">
                        {{ $subscription->payment?->invoice_number ?? '—' }}
                    </td>
                    <td class="whitespace-nowrap px-4 py-2.5">
                        <span @class([
                            'rounded-full px-2 py-0.5 text-xs font-medium',
                            'bg-success/10 text-success' => $subscription->status === 'active',
                            'bg-warning/10 text-warning' => in_array($subscription->status, ['pending', 'expired'], true),
                            'bg-danger/10 text-danger' => $subscription->status === 'cancelled',
                        ])>{{ ucfirst($subscription->status) }}</span>
                    </td>
                    <td class="whitespace-nowrap px-4 py-2.5 text-right">
                        <a href="{{ route('admin.subscriptions.show', $subscription) }}" class="text-sm font-medium text-primary-600 hover:underline">View</a>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="px-4 py-8 text-center text-sm" style="color:var(--text-muted)">No subscriptions match these filters.</td>
                </tr>
            @endforelse
        </x-admin.data-table>
    </div>
@endsection
