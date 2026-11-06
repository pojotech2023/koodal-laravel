@extends('layouts.member')

@section('title', 'Membership — '.config('app.name'))

{{-- No checkout.js here — "Change plan"/"Upgrade" below just navigates to
     /plans, where the actual Subscribe buttons (and checkout.js) live. --}}

@section('body')
    <div class="mx-auto max-w-4xl px-4 py-8">
        <h1 class="font-serif text-3xl font-bold text-ink-900">Membership</h1>

        {{-- Status card --}}
        <div class="card mt-6 p-6">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div>
                    <p class="text-sm" style="color:var(--text-muted)">Current plan</p>
                    <p class="text-xl font-semibold">{{ $subscription?->plan?->name ?? 'Free' }}</p>
                    @if ($subscription?->expires_at)
                        <p class="mt-1 text-sm" style="color:var(--text-muted)">
                            {{ $subscription->isActive() ? 'Renews or expires' : 'Expired' }}
                            {{ $subscription->expires_at->toFormattedDateString() }}
                            @if ($subscription->isActive() && $subscription->daysRemaining() !== null)
                                ({{ $subscription->daysRemaining() }} days left)
                            @endif
                        </p>
                    @endif
                </div>
                <a href="{{ route('membership.plans') }}" class="rounded-md bg-primary-500 px-4 py-2 text-sm font-medium text-white hover:bg-primary-600">
                    {{ $subscription ? 'Change plan' : 'Upgrade' }}
                </a>
            </div>
        </div>

        {{-- Usage meters --}}
        @if (! empty($usage))
            <div class="mt-6 grid gap-4 sm:grid-cols-2">
                @foreach ($usage as $meter)
                    <div class="card p-5">
                        <div class="flex items-center justify-between text-sm">
                            <span>{{ $meter['label'] }}</span>
                            <span style="color:var(--text-muted)">
                                {{ $meter['used'] }} / {{ $meter['unlimited'] ? '∞' : $meter['limit'] }}
                            </span>
                        </div>
                        @if (! $meter['unlimited'] && $meter['limit'] > 0)
                            <div class="mt-2 h-1.5 w-full overflow-hidden rounded-full" style="background-color:var(--border-default)">
                                <div class="h-full rounded-full bg-primary-500" style="width: {{ min(100, (int) round($meter['used'] / $meter['limit'] * 100)) }}%"></div>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif

        {{-- Subscription history --}}
        <section class="card mt-6 p-6">
            <h2 class="card-title">Subscription history</h2>
            @if ($subscriptions->isEmpty())
                <p class="mt-2 text-sm" style="color:var(--text-muted)">No subscriptions yet.</p>
            @else
                <div class="mt-3 divide-y" style="border-color:var(--border-default)">
                    @foreach ($subscriptions as $row)
                        <div class="flex items-center justify-between py-3 text-sm">
                            <div>
                                <p class="font-medium">{{ $row->plan?->name ?? 'Plan removed' }}</p>
                                <p style="color:var(--text-muted)">
                                    {{ $row->starts_at?->toFormattedDateString() }}
                                    @if ($row->expires_at) – {{ $row->expires_at->toFormattedDateString() }} @endif
                                </p>
                            </div>
                            <span class="rounded-full px-3 py-1 text-xs" style="background-color:var(--bg-page);border:1px solid var(--border-default)">
                                {{ ucfirst($row->status) }}
                            </span>
                        </div>
                    @endforeach
                </div>
                <div class="mt-3">{{ $subscriptions->links() }}</div>
            @endif
        </section>

        {{-- Billing history --}}
        <section class="card mt-6 p-6">
            <h2 class="card-title">Billing history</h2>
            @if ($payments->isEmpty())
                <p class="mt-2 text-sm" style="color:var(--text-muted)">No payments yet.</p>
            @else
                <div class="mt-3 divide-y" style="border-color:var(--border-default)">
                    @foreach ($payments as $payment)
                        <div class="flex items-center justify-between py-3 text-sm">
                            <div>
                                <p class="font-medium">{{ $payment->plan?->name ?? 'Plan removed' }}</p>
                                <p style="color:var(--text-muted)">
                                    ₹{{ number_format((float) $payment->amount, 2) }} · {{ ucfirst($payment->status) }}
                                    @if ($payment->verified_at) · {{ $payment->verified_at->toFormattedDateString() }} @endif
                                </p>
                            </div>
                            @if ($payment->invoice_path !== null)
                                <a href="{{ route('payments.invoice', $payment) }}" class="text-sm font-medium text-primary-600 hover:underline">Invoice</a>
                            @endif
                        </div>
                    @endforeach
                </div>
                <div class="mt-3">{{ $payments->links() }}</div>
            @endif
        </section>
    </div>
@endsection
