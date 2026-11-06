@extends('admin.layouts.admin')

@section('title', 'Payment '.($payment->invoice_number ?? $payment->id).' — '.config('app.name').' Admin')

@section('admin-body')
    <a href="{{ route('admin.payments.index') }}" class="text-sm font-medium text-primary-600 hover:underline">&larr; Payments</a>

    <div class="mt-3 flex flex-wrap items-start justify-between gap-4">
        <div>
            <h1 class="font-serif text-3xl font-bold text-ink-900">
                {{ $payment->currency }} {{ number_format((float) $payment->amount, 2) }}
                <span class="font-mono text-base" style="color:var(--text-muted)">{{ $payment->invoice_number ?? '—' }}</span>
            </h1>
            <p class="mt-1 text-sm" style="color:var(--text-muted)">
                {{ $payment->plan?->name ?? 'Plan deleted' }} ·
                {{ $payment->created_at->format('d M Y, H:i') }}
                @if ($payment->user)
                    · <a href="{{ route('admin.users.show', $payment->user_id) }}" class="text-primary-600 hover:underline">{{ $payment->user->name }}</a>
                    <span class="font-mono">{{ $payment->user->profile_code }}</span>
                @endif
            </p>
        </div>

        <span @class([
            'rounded-full px-3 py-1 text-sm font-medium',
            'bg-success/10 text-success' => $payment->status === 'paid',
            'bg-danger/10 text-danger' => in_array($payment->status, ['failed', 'refunded'], true),
            'bg-warning/10 text-warning' => in_array($payment->status, ['created', 'attempted'], true),
        ])>{{ ucfirst($payment->status) }}</span>
    </div>

    <div class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-3">
        <section class="card p-5 lg:col-span-2">
            <h2 class="card-title">Gateway record</h2>
            <dl class="mt-3 grid grid-cols-1 gap-x-4 gap-y-2 text-sm sm:grid-cols-2">
                <x-profile.detail-row label="Razorpay order" :value="$payment->razorpay_order_id" />
                <x-profile.detail-row label="Razorpay payment" :value="$payment->razorpay_payment_id" />
                <x-profile.detail-row label="Method" :value="$payment->method ? ucfirst($payment->method) : null" />
                <x-profile.detail-row label="Verified at" :value="$payment->verified_at?->format('d M Y, H:i')" />
                <x-profile.detail-row label="Failure reason" :value="$payment->failure_reason" />
                <x-profile.detail-row label="Refund id" :value="$payment->refund_id" />
                <x-profile.detail-row label="Refunded amount" :value="$payment->refunded_amount ? $payment->currency.' '.number_format((float) $payment->refunded_amount, 2) : null" />
                <x-profile.detail-row label="Refund reason" :value="$payment->refund_reason" />
            </dl>

            @if ($payment->subscription)
                <h2 class="mt-5 text-sm font-semibold">What it bought</h2>
                <dl class="mt-3 grid grid-cols-1 gap-x-4 gap-y-2 text-sm sm:grid-cols-2">
                    <x-profile.detail-row label="Subscription status" :value="ucfirst($payment->subscription->status)" />
                    <x-profile.detail-row label="Started" :value="$payment->subscription->starts_at?->format('d M Y')" />
                    <x-profile.detail-row label="Expires" :value="$payment->subscription->expires_at?->format('d M Y')" />
                </dl>
                <a href="{{ route('admin.subscriptions.show', $payment->subscription) }}" class="mt-3 inline-block text-sm font-medium text-primary-600 hover:underline">
                    Open the subscription →
                </a>
            @endif
        </section>

        <div class="space-y-6">
            @can('refund', $payment)
                <section class="card p-5">
                    <h2 class="card-title">Refund</h2>
                    <p class="mt-1 text-xs" style="color:var(--text-muted)">
                        This moves money at Razorpay. Leave the amount empty to refund the whole payment; a partial refund has to be stated in {{ $payment->currency }}, not paise.
                    </p>

                    {{-- The confirm dialog is not decoration here: docs/06 §7
                         names `refund` as a destructive action by name, and
                         the identifier typed back is the invoice number, which
                         is what the operator is looking at on the ticket. --}}
                    <div class="mt-3">
                        <x-admin.confirm-dialog
                            id="refund-{{ $payment->id }}" trigger="Refund this payment" variant="danger"
                            title="Refund {{ $payment->currency }} {{ number_format((float) $payment->amount, 2) }}?"
                            :action="route('admin.payments.refund', $payment)"
                            :identifier="$payment->invoice_number ?? $payment->razorpay_payment_id" confirm-label="Issue refund"
                        >
                            <p style="color:var(--text-muted)">
                                Razorpay confirms by webhook, so the status here changes shortly after — not on submit. The membership is <em>not</em> cancelled by this; do that separately if it should be.
                            </p>

                            <label class="block font-medium">Amount <span class="font-normal" style="color:var(--text-muted)">(empty = full refund)</span></label>
                            <input type="number" name="amount" step="0.01" min="0.01" max="{{ $payment->amount }}"
                                   class="mt-1 w-full rounded-md border px-3 py-2 text-sm" style="border-color:var(--border-default)">

                            <label class="block font-medium">Reason</label>
                            <textarea name="reason" required minlength="5" maxlength="255" rows="2"
                                      class="mt-1 w-full rounded-md border px-3 py-2 text-sm" style="border-color:var(--border-default)"></textarea>
                        </x-admin.confirm-dialog>
                    </div>
                </section>
            @endcan

            <section class="card p-5">
                <h2 class="card-title">Member</h2>
                <dl class="mt-3 space-y-2 text-sm">
                    <x-profile.detail-row label="Name" :value="$payment->user?->name" />
                    <x-profile.detail-row label="Profile ID" :value="$payment->user?->profile_code" />
                    <x-profile.detail-row label="Email" :value="$payment->user?->email" />
                    <x-profile.detail-row label="Mobile" :value="$payment->user?->mobile" />
                </dl>
                @unless ($payment->user)
                    <p class="mt-3 text-sm" style="color:var(--text-muted)">
                        The account was deleted. The payment row survives it on purpose — deleting a member must not erase the financial record.
                    </p>
                @endunless
            </section>
        </div>
    </div>
@endsection
