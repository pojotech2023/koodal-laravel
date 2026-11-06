@extends('admin.layouts.admin')

@section('title', 'Subscription #'.$subscription->id.' — '.config('app.name').' Admin')

@section('admin-body')
    <a href="{{ route('admin.subscriptions.index') }}" class="text-sm font-medium text-primary-600 hover:underline">&larr; Subscriptions</a>

    <div class="mt-3 flex flex-wrap items-start justify-between gap-4">
        <div>
            <h1 class="font-serif text-3xl font-bold text-ink-900">
                {{ $subscription->plan?->name }} —
                <a href="{{ route('admin.users.show', $subscription->user_id) }}" class="text-primary-600 hover:underline">{{ $subscription->user?->name }}</a>
            </h1>
            <p class="mt-1 text-sm" style="color:var(--text-muted)">
                <span class="font-mono">{{ $subscription->user?->profile_code }}</span> ·
                Started {{ $subscription->starts_at?->format('d M Y') }} ·
                {{ $subscription->expires_at ? 'Expires '.$subscription->expires_at->format('d M Y') : 'Never expires' }}
            </p>
        </div>

        <span @class([
            'rounded-full px-3 py-1 text-sm font-medium',
            'bg-success/10 text-success' => $subscription->status === 'active',
            'bg-warning/10 text-warning' => in_array($subscription->status, ['pending', 'expired'], true),
            'bg-danger/10 text-danger' => $subscription->status === 'cancelled',
        ])>{{ ucfirst($subscription->status) }}</span>
    </div>

    <div class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-3">
        {{-- The reason this page exists. `features_snapshot` is what
             `FeatureGate` reads for this member — not the plan's current
             features — so a member who says a capability they paid for has
             gone is answered by the left column, and the right column shows
             whether an edit to the plan is why the two differ. --}}
        <section class="card p-5 lg:col-span-2">
            <h2 class="card-title">What they bought, and what the plan grants today</h2>
            <p class="mt-1 text-xs" style="color:var(--text-muted)">
                The left column is frozen at purchase and is the one that counts. A difference is not a bug — it is a plan edit that correctly did not reach backwards.
            </p>

            <div class="mt-3 overflow-x-auto">
                <table class="min-w-full divide-y text-sm" style="border-color:var(--border-default)">
                    <thead>
                        <tr>
                            <th class="px-3 py-2 text-left text-xs font-medium uppercase tracking-wide" style="color:var(--text-muted)">Feature</th>
                            <th class="px-3 py-2 text-left text-xs font-medium uppercase tracking-wide" style="color:var(--text-muted)">Bought (snapshot)</th>
                            <th class="px-3 py-2 text-left text-xs font-medium uppercase tracking-wide" style="color:var(--text-muted)">Plan today</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y" style="border-color:var(--border-default)">
                        @foreach ($schema as $key => $meta)
                            @php($bought = data_get($subscription->features_snapshot, $key))
                            @php($now = data_get($subscription->plan?->features, $key))

                            <tr @class(['bg-warning/5' => $bought !== $now])>
                                <td class="px-3 py-2">{{ $meta['label'] }}</td>
                                <td class="px-3 py-2 font-medium">
                                    @include('admin.subscriptions._feature-value', ['value' => $bought, 'meta' => $meta])
                                </td>
                                <td class="px-3 py-2" style="color:var(--text-muted)">
                                    @include('admin.subscriptions._feature-value', ['value' => $now, 'meta' => $meta])
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>

        <div class="space-y-6">
            <section class="card p-5">
                <h2 class="card-title">Payment</h2>
                <dl class="mt-3 space-y-2 text-sm">
                    <x-profile.detail-row label="Invoice" :value="$subscription->payment?->invoice_number" />
                    <x-profile.detail-row label="Amount" :value="$subscription->payment ? $subscription->payment->currency.' '.number_format((float) $subscription->payment->amount, 2) : null" />
                    <x-profile.detail-row label="Payment status" :value="$subscription->payment?->status ? ucfirst($subscription->payment->status) : null" />
                    <x-profile.detail-row label="Auto renew" :value="$subscription->auto_renew ? 'Yes' : 'No'" />
                    @if ($subscription->cancelled_at)
                        <x-profile.detail-row label="Cancelled" :value="$subscription->cancelled_at->format('d M Y, H:i')" />
                        <x-profile.detail-row label="Cancellation reason" :value="$subscription->cancellation_reason" />
                    @endif
                </dl>

                @if ($subscription->payment)
                    <a href="{{ route('admin.payments.show', $subscription->payment) }}" class="mt-3 inline-block text-sm font-medium text-primary-600 hover:underline">
                        Open the payment →
                    </a>
                @endif
            </section>

            @can('subscriptions.manage')
                @if (in_array($subscription->status, ['active', 'pending'], true))
                    <section class="card p-5">
                        <h2 class="card-title">Extend</h2>
                        <p class="mt-1 text-xs" style="color:var(--text-muted)">
                            Goodwill days, capped at a year. Anything longer is a plan change and should go through a purchase so the revenue and the snapshot line up.
                        </p>

                        <form method="POST" action="{{ route('admin.subscriptions.extend', $subscription) }}" class="mt-3 space-y-3">
                            @csrf
                            <x-form.input name="days" label="Days" type="number" min="1" max="365" required />
                            <x-form.input name="reason" label="Reason" maxlength="255" />
                            <button type="submit" class="w-full rounded-md border border-primary-500 px-4 py-2 text-sm font-medium text-primary-600 hover:bg-primary-50">
                                Extend membership
                            </button>
                        </form>
                    </section>

                    <section class="card p-5">
                        <h2 class="card-title">Cancel</h2>
                        <p class="mt-1 text-xs" style="color:var(--text-muted)">
                            Ends the entitlement immediately and refunds nothing. The reason is what support reads back when the member calls.
                        </p>

                        {{-- Typed confirmation: this ends something the member
                             paid for, which is docs/06 §7's test for the
                             dialog, and the identifier is their profile code
                             so the wrong tab cannot be cancelled by muscle
                             memory. --}}
                        <div class="mt-3">
                            <x-admin.confirm-dialog
                                id="cancel-sub-{{ $subscription->id }}" trigger="Cancel membership" variant="danger"
                                title="Cancel {{ $subscription->user?->name }}'s membership?"
                                :action="route('admin.subscriptions.cancel', $subscription)"
                                :identifier="$subscription->user?->profile_code" confirm-label="Cancel membership"
                            >
                                <p style="color:var(--text-muted)">
                                    They lose {{ $subscription->plan?->name }} access immediately. Nothing is refunded — that is the payment's refund action, separately.
                                </p>
                                <label class="block font-medium">Reason</label>
                                <textarea name="reason" required minlength="5" maxlength="255" rows="2"
                                          class="mt-1 w-full rounded-md border px-3 py-2 text-sm" style="border-color:var(--border-default)"></textarea>
                            </x-admin.confirm-dialog>
                        </div>
                    </section>
                @endif
            @endcan
        </div>
    </div>
@endsection
