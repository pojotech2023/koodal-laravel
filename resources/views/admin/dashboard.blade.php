@extends('admin.layouts.admin')

@section('title', 'Dashboard — '.config('app.name').' Admin')

@section('admin-body')
    <div class="flex flex-wrap items-end justify-between gap-3">
        <div>
            <h1 class="font-serif text-3xl font-bold text-ink-900">Dashboard</h1>
            <p class="mt-1 text-sm" style="color:var(--text-muted)">Members, moderation queues and revenue at a glance.</p>
        </div>
    </div>

    {{-- Matrimony Platform Plan & Capacity Limit Usage --}}
    @php
        $platformLimit = $limit ?? \App\Models\MatrimonyLimit::current();
        $isUnlimited = $platformLimit->isUnlimited();
        $usagePercent = $isUnlimited ? 0 : ($platformLimit->available_user_count > 0
            ? min(100, round(($platformLimit->used_count / $platformLimit->available_user_count) * 100))
            : 100);
        $planConfig = \App\Models\MatrimonyLimit::PLANS[$platformLimit->plan_type] ?? null;
        $planName = $isUnlimited ? 'Unlimited' : ($planConfig['name'] ?? ucfirst($platformLimit->plan_type));
        $planPrice = $isUnlimited ? 'Lifetime' : ($planConfig['price'] ?? '');
    @endphp

    <div class="relative mt-6 overflow-hidden rounded-3xl bg-ink-gradient p-6 text-white shadow-lg">
        <div class="pointer-events-none absolute inset-0 bg-grid-ink opacity-50"></div>
        <div class="pointer-events-none absolute -right-16 -top-16 h-64 w-64 rounded-full bg-primary-500/35 blur-3xl"></div>
        <div class="relative">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-center gap-3">
                <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-brand-gradient text-white shadow-glow">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                    </svg>
                </div>
                <div>
                    <div class="flex flex-wrap items-center gap-2">
                        <h2 class="text-base font-bold text-white">Matrimony Subscription Plan</h2>
                        <span class="inline-flex items-center rounded-full bg-gold-gradient px-2.5 py-0.5 text-xs font-extrabold uppercase tracking-wide text-ink-900">
                            {{ $planName }} Plan ({{ $isUnlimited ? 'Unlimited' : $platformLimit->available_user_count }} Users)
                        </span>
                        @if($planPrice)
                            <span class="text-xs font-semibold text-ink-200">
                                • {{ $planPrice }}
                            </span>
                        @endif
                    </div>
                    <p class="mt-0.5 text-xs text-ink-200/70">
                        Platform user capacity limit & real-time registration usage
                    </p>
                </div>
            </div>

            <div>
                <a
                    href="{{ route('admin.upgrade.index') }}"
                    class="inline-flex items-center gap-2 rounded-xl bg-white px-4 py-2.5 text-xs font-bold uppercase tracking-wider text-primary-700 shadow-lg transition-all hover:scale-[1.03] active:scale-95"
                >
                    <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                    </svg>
                    <span>Upgrade Plan</span>
                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                </a>
            </div>
        </div>

        <div class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-3">
            <div class="rounded-2xl border border-white/10 bg-white/5 p-4 backdrop-blur-sm">
                <span class="block text-xs font-medium text-ink-200/70">Allowed Capacity</span>
                <div class="mt-1 flex items-baseline gap-2">
                    <span class="font-serif text-3xl font-bold text-white">{{ $isUnlimited ? 'Unlimited' : number_format($platformLimit->available_user_count) }}</span>
                    <span class="text-xs text-ink-200/60">{{ $isUnlimited ? 'Total Users' : 'Total Users' }}</span>
                </div>
            </div>

            <div class="rounded-2xl border border-white/10 bg-white/5 p-4 backdrop-blur-sm">
                <span class="block text-xs font-medium text-ink-200/70">Registered Members (Used)</span>
                <div class="mt-1 flex items-baseline gap-2">
                    <span class="font-serif text-3xl font-bold text-primary-300">{{ number_format($platformLimit->used_count) }}</span>
                    <span class="text-xs text-ink-200/60">{{ $isUnlimited ? '(Active)' : "({$usagePercent}% used)" }}</span>
                </div>
            </div>

            <div class="rounded-2xl border border-white/10 bg-white/5 p-4 backdrop-blur-sm">
                <span class="block text-xs font-medium text-ink-200/70">Available Slots (Balance)</span>
                <div class="mt-1 flex items-baseline gap-2">
                    <span class="font-serif text-3xl font-bold text-emerald-300">
                        {{ $isUnlimited ? 'Unlimited' : number_format($platformLimit->balance_count) }}
                    </span>
                    <span class="text-xs text-ink-200/60">{{ $isUnlimited ? 'Users Left' : 'Users Left' }}</span>
                </div>
            </div>
        </div>

        <div class="mt-4">
            <div class="mb-1.5 flex justify-between text-xs font-medium text-ink-200/80">
                <span>Capacity Usage: <strong class="text-white">{{ $platformLimit->used_count }} / {{ $isUnlimited ? 'Unlimited' : $platformLimit->available_user_count }} Users</strong></span>
                <span class="font-bold {{ $isUnlimited ? 'text-emerald-300' : ($usagePercent >= 90 ? 'text-rose-300' : 'text-primary-300') }}">{{ $isUnlimited ? 'Unlimited Capacity' : $usagePercent.'%' }}</span>
            </div>
            <div class="h-2.5 w-full overflow-hidden rounded-full bg-white/10">
                <div class="h-full rounded-full transition-all duration-500 {{ $isUnlimited ? 'bg-gradient-to-r from-emerald-400 to-teal-400' : ($usagePercent >= 100 ? 'bg-rose-500' : ($usagePercent >= 80 ? 'bg-amber-500' : 'bg-gradient-to-r from-primary-400 to-accent-400')) }}"
                     style="width: {{ $isUnlimited ? '100' : $usagePercent }}%"></div>
            </div>
            @if(!$isUnlimited && $platformLimit->balance_count <= 0)
                <p class="mt-2 text-xs font-semibold text-rose-300 flex items-center gap-1.5">
                    <svg class="h-4 w-4 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                    </svg>
                    Capacity limit reached! New member registrations are blocked until you upgrade the plan.
                </p>
            @elseif(!$isUnlimited && $usagePercent >= 80)
                <p class="mt-2 text-xs font-semibold text-amber-300 flex items-center gap-1.5">
                    <svg class="h-4 w-4 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                    </svg>
                    Capacity running low! {{ $platformLimit->balance_count }} slots remaining. Upgrade plan soon to prevent registration issues.
                </p>
            @endif
        </div>
        </div>
    </div>

    {{-- 11 stat cards from DashboardMetrics::stats() — every number already
         aggregated server-side, per docs/06-admin-panel-guide.md §5. --}}
    <div class="stagger mt-6 grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-6">
        <x-admin.stat-card icon="users" tone="primary" label="Total members" :value="number_format($stats['users']['total'])" />
        <x-admin.stat-card icon="bolt" tone="emerald" label="Active" :value="number_format($stats['users']['active'])" />
        <x-admin.stat-card icon="plus" tone="sky" label="New today" :value="number_format($stats['users']['new_today'])" />
        <x-admin.stat-card icon="calendar" tone="violet" label="New this month" :value="number_format($stats['users']['new_this_month'])" />
        <x-admin.stat-card icon="shield" tone="emerald" label="Verified" :value="number_format($stats['users']['verified'])" />
        <x-admin.stat-card icon="star" tone="gold" label="Premium" :value="number_format($stats['users']['premium'])" />

        <x-admin.stat-card icon="clock" tone="amber" label="Profile approvals" :value="number_format($stats['pending']['profile_approvals'])" sub="Pending" />
        <x-admin.stat-card icon="photo" tone="sky" label="Photo moderation" :value="number_format($stats['pending']['photo_moderation'])" sub="Pending" />
        <x-admin.stat-card icon="id" tone="violet" label="Verifications" :value="number_format($stats['pending']['verifications'])" sub="Pending" />
        <x-admin.stat-card icon="flag" tone="rose" label="Open reports" :value="number_format($stats['pending']['reports'])" sub="Pending" />

        <x-admin.stat-card icon="cash" tone="gold" label="Revenue today"
            :value="$stats['revenue']['currency'].' '.number_format($stats['revenue']['today'], 2)"
        />
        <x-admin.stat-card icon="cash" tone="emerald" label="Revenue this month"
            :value="$stats['revenue']['currency'].' '.number_format($stats['revenue']['this_month'], 2)"
            :sub="$stats['revenue']['active_subscriptions'].' active subscriptions'"
        />
    </div>

    <div class="mt-6 grid grid-cols-1 gap-4 lg:grid-cols-2">
        <x-admin.chart id="chart-registrations" title="Registrations (30 days)" type="line" :series="$charts['registrations']" x-key="date" />
        <x-admin.chart id="chart-revenue" title="Revenue (30 days)" type="line" :series="$charts['revenue']" x-key="date" />
        <x-admin.chart id="chart-subscriptions" title="New subscriptions (30 days)" type="bar" :series="$charts['subscriptions']" x-key="date" />
        <x-admin.chart id="chart-active-users" title="Active users (30 days)" type="line" :series="$charts['active-users']" x-key="date" />

        <div class="card p-5 lg:col-span-2">
            <h3 class="text-sm font-bold text-ink-800">Demographics</h3>
            <div class="mt-3 grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div style="height:220px">
                    <canvas
                        id="chart-demographics-religion"
                        data-chart-type="bar"
                        data-chart-x-key="label"
                        data-chart-series="{{ json_encode(collect($charts['demographics']['by_religion'] ?? [])->values()) }}"
                    ></canvas>
                </div>
                <div style="height:220px">
                    <canvas
                        id="chart-demographics-age"
                        data-chart-type="bar"
                        data-chart-x-key="label"
                        data-chart-series="{{ json_encode(collect($charts['demographics']['by_age_band'] ?? [])->values()) }}"
                    ></canvas>
                </div>
            </div>
        </div>

        <x-admin.chart id="chart-locations" title="Top locations" type="bar" :series="$charts['locations']" x-key="label" />
    </div>
@endsection
