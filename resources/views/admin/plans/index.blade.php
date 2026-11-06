@extends('admin.layouts.admin')

@section('title', 'Membership plans — '.config('app.name').' Admin')

@section('admin-body')
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="font-serif text-3xl font-bold text-ink-900">Membership plans</h1>
            <p class="mt-1 text-sm" style="color:var(--text-muted)">
                Editing a plan changes what it sells from now on. Every existing subscription keeps the features it was bought with.
            </p>
        </div>

        @can('create', \App\Models\MembershipPlan::class)
            <a href="{{ route('admin.plans.create') }}" class="rounded-md bg-primary-500 px-4 py-2 text-sm font-medium text-white hover:bg-primary-600">
                New plan
            </a>
        @endcan
    </div>

    <div class="mt-4">
        <x-admin.data-table :rows="$plans" :columns="['Plan', 'Price', 'Duration', 'Active subscribers', 'Status', '']">
            @forelse ($plans as $plan)
                <tr>
                    <td class="px-4 py-2.5">
                        <span class="font-medium">{{ $plan->name }}</span>
                        @if ($plan->is_default)
                            <span class="ml-1 rounded-full bg-primary-50 px-2 py-0.5 text-xs font-medium text-primary-700">Default</span>
                        @endif
                        @if ($plan->is_featured)
                            <span class="ml-1 rounded-full bg-warning/10 px-2 py-0.5 text-xs font-medium text-warning">{{ $plan->badge_text ?: 'Featured' }}</span>
                        @endif
                        <span class="block font-mono text-xs" style="color:var(--text-muted)">{{ $plan->slug }}</span>
                    </td>
                    <td class="whitespace-nowrap px-4 py-2.5">{{ $plan->currency }} {{ number_format((float) $plan->price, 2) }}</td>
                    <td class="whitespace-nowrap px-4 py-2.5">
                        {{ $plan->duration_days ? $plan->duration_days.' days' : 'Never expires' }}
                    </td>
                    <td class="whitespace-nowrap px-4 py-2.5">{{ number_format($plan->subscriptions_count) }}</td>
                    <td class="whitespace-nowrap px-4 py-2.5">
                        <span @class([
                            'rounded-full px-2 py-0.5 text-xs font-medium',
                            'bg-success/10 text-success' => $plan->is_active,
                            'bg-danger/10 text-danger' => ! $plan->is_active,
                        ])>{{ $plan->is_active ? 'Active' : 'Retired' }}</span>
                    </td>
                    <td class="whitespace-nowrap px-4 py-2.5 text-right">
                        <div class="flex items-center justify-end gap-2">
                            @can('update', $plan)
                                <a href="{{ route('admin.plans.edit', $plan) }}" class="text-sm font-medium text-primary-600 hover:underline">Edit</a>
                            @endcan

                            {{-- The Policy refuses the default plan and any
                                 plan with subscribers, so this only ever
                                 renders where it can succeed — and even then
                                 it types the slug back, because the plan name
                                 is on every historical invoice. --}}
                            @can('delete', $plan)
                                <x-admin.confirm-dialog
                                    id="delete-plan-{{ $plan->id }}" trigger="Delete" variant="danger"
                                    title="Delete the {{ $plan->name }} plan?"
                                    :action="route('admin.plans.destroy', $plan)" method="DELETE"
                                    :identifier="$plan->slug" confirm-label="Delete plan"
                                >
                                    <p style="color:var(--text-muted)">
                                        Soft-deleted — past invoices and payments still name this plan. Nobody holds it, or this button would not be here.
                                    </p>
                                </x-admin.confirm-dialog>
                            @endcan
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="px-4 py-8 text-center text-sm" style="color:var(--text-muted)">
                        No plans yet. `MembershipPlanSeeder` normally creates the free tier and three paid plans.
                    </td>
                </tr>
            @endforelse
        </x-admin.data-table>
    </div>
@endsection
