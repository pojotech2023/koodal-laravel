@extends('layouts.public')

@section('title', 'Membership plans — '.config('app.name'))
@section('description', 'Compare '.config('app.name').' membership plans and choose the one that fits your search.')

@push('scripts')
    <script src="https://checkout.razorpay.com/v1/checkout.js"></script>
    @vite('resources/js/checkout.js')
@endpush

@section('body')
    <x-page-header eyebrow="Membership" title="Plans that fit your search" sub="Start free, upgrade when you're ready to connect. No hidden charges." />

    <div class="mx-auto max-w-7xl px-4 py-14 sm:px-6">
        <div id="checkout-error" class="mx-auto mb-6 hidden max-w-md rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-center text-sm font-medium text-red-800" role="alert"></div>
        <div id="checkout-success" class="mx-auto mb-6 hidden max-w-md rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-center text-sm font-medium text-emerald-800" role="alert"></div>

        <div class="stagger grid items-stretch gap-6 sm:grid-cols-2 lg:grid-cols-4">
            @php $currentSlug = $viewer?->activeSubscription?->plan?->slug ?? 'free'; @endphp
            @foreach ($plans as $plan)
                @php $featured = (bool) $plan->is_featured; @endphp
                <div class="relative flex flex-col rounded-3xl p-7 transition-all duration-300 hover:-translate-y-1.5 {{ $featured ? 'bg-ink-gradient text-white shadow-xl ring-1 ring-primary-400/50 lg:scale-[1.03]' : 'card hover:shadow-lg' }}">
                    @if ($featured)
                        <div class="pointer-events-none absolute -right-10 -top-10 h-40 w-40 rounded-full bg-primary-500/40 blur-3xl"></div>
                    @endif

                    <div class="relative flex min-h-[1.75rem] items-center">
                        @if ($plan->badge_text)
                            <span class="inline-block w-fit rounded-full px-3 py-1 text-xs font-bold {{ $featured ? 'bg-gold-gradient text-ink-900' : 'bg-primary-50 text-primary-700' }}">{{ $plan->badge_text }}</span>
                        @endif
                    </div>

                    <h2 class="relative mt-3 font-serif text-2xl font-bold {{ $featured ? 'text-white' : 'text-ink-900' }}">{{ $plan->name }}</h2>
                    <p class="relative mt-1 min-h-[2.5rem] text-sm {{ $featured ? 'text-ink-200/80' : '' }}" @unless($featured) style="color:var(--text-muted)" @endunless>{{ $plan->description }}</p>

                    <p class="relative mt-5 flex items-baseline gap-1.5">
                        <span class="font-serif text-4xl font-bold {{ $featured ? 'text-white' : 'text-ink-900' }}">{{ $plan->isFree() ? 'Free' : '₹'.number_format((float) $plan->price) }}</span>
                        @if (! $plan->isFree())
                            <span class="text-sm {{ $featured ? 'text-ink-200/70' : '' }}" @unless($featured) style="color:var(--text-muted)" @endunless>/ {{ $plan->duration_days }} days</span>
                        @endif
                    </p>

                    <ul class="relative mt-6 flex-1 space-y-3 border-t pt-6 text-sm {{ $featured ? 'border-white/10' : '' }}" @unless($featured) style="border-color:var(--border-default)" @endunless>
                        @foreach ($plan->featureRows() as $row)
                            <li class="flex items-start justify-between gap-3">
                                <span class="flex items-start gap-2 {{ $featured ? 'text-ink-200/80' : '' }}" @unless($featured) style="color:var(--text-muted)" @endunless>
                                    <svg class="mt-0.5 h-4 w-4 shrink-0 {{ $featured ? 'text-accent-300' : 'text-primary-500' }}" fill="none" viewBox="0 0 24 24" stroke-width="2.6" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
                                    {{ $row['label'] }}
                                </span>
                                <span class="shrink-0 font-semibold {{ $featured ? 'text-white' : 'text-ink-800' }}">{{ $row['display'] }}</span>
                            </li>
                        @endforeach
                    </ul>

                    <div class="relative mt-7">
                        @if ($currentSlug === $plan->slug)
                            <span class="block rounded-xl border px-4 py-3 text-center text-sm font-bold {{ $featured ? 'border-white/30 text-white' : 'border-primary-300 bg-primary-50 text-primary-700' }}">✓ Current plan</span>
                        @elseif ($plan->isFree())
                            <span class="block rounded-md px-4 py-3 text-center text-sm" style="color:var(--text-muted)">&nbsp;</span>
                        @elseif (! $viewer)
                            <a href="{{ route('register') }}" class="block rounded-xl bg-primary-500 px-4 py-3 text-center text-sm font-bold text-white hover:bg-primary-600">
                                Create a profile to subscribe
                            </a>
                        @else
                            <button
                                type="button"
                                data-checkout-plan-id="{{ $plan->id }}"
                                data-checkout-plan-name="{{ $plan->name }}"
                                data-checkout-plan-duration="{{ $plan->duration_days }}"
                                class="block w-full rounded-xl bg-primary-500 px-4 py-3 text-center text-sm font-bold text-white hover:bg-primary-600"
                            >
                                Subscribe
                            </button>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>

        <p class="mt-10 text-center text-xs" style="color:var(--text-muted)">Payments are processed securely by Razorpay. See our <a href="{{ route('page.show', 'refund-policy') }}" class="font-semibold text-primary-600 hover:underline">refund policy</a>.</p>
    </div>
@endsection
