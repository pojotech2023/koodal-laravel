@extends('layouts.member')

@section('title', 'Dashboard — '.config('app.name'))

@section('body')
    @php
        $pct = (int) $completion['completion_percentage'];
        $circ = 2 * M_PI * 52;
        $stats = [
            ['Profile views', $user->profile?->profile_views_count ?? 0, 'M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178zM15 12a3 3 0 11-6 0 3 3 0 016 0z', 'bg-sky-100 text-sky-600', 'profile-views.index'],
            ['Shortlisted you', $user->profile?->shortlisted_count ?? 0, 'M11.48 3.499a.562.562 0 011.04 0l2.125 5.111a.563.563 0 00.475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 00-.182.557l1.285 5.385a.562.562 0 01-.84.61l-4.725-2.885a.563.563 0 00-.586 0L6.982 20.54a.562.562 0 01-.84-.61l1.285-5.386a.562.562 0 00-.182-.557l-4.204-3.602a.563.563 0 01.321-.988l5.518-.442a.563.563 0 00.475-.345L11.48 3.5z', 'bg-accent-100 text-accent-700', 'shortlist.received'],
            ['Interests received', $user->profile?->interests_received_count ?? 0, 'M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12z', 'bg-primary-100 text-primary-600', 'interests.received'],
        ];
        $actions = [
            ['Find matches', 'Browse profiles picked for you', 'matches.recommended', 'M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09z'],
            ['Search', 'Filter by community & more', 'search', 'M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z'],
            ['Interests', 'Sent & received', 'interests.index', 'M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12z'],
            ['Messages', 'Talk to your connections', 'messages.index', 'M20.25 8.511c.884.284 1.5 1.128 1.5 2.097v4.286c0 1.136-.847 2.1-1.98 2.193-.34.027-.68.052-1.02.072v3.091l-3-3c-1.354 0-2.694-.055-4.02-.163a2.115 2.115 0 01-.825-.242m9.345-8.334a2.126 2.126 0 00-.476-.095 48.64 48.64 0 00-8.048 0c-1.131.094-1.976 1.057-1.976 2.192v4.286c0 .837.46 1.58 1.155 1.951m9.345-8.334V6.637c0-1.621-1.152-3.026-2.76-3.235A48.455 48.455 0 0011.25 3c-2.115 0-4.198.137-6.24.402-1.608.209-2.76 1.614-2.76 3.235v6.226c0 1.621 1.152 3.026 2.76 3.235.577.075 1.157.14 1.74.194V21l4.155-4.155'],
        ];
    @endphp

    <div class="mx-auto max-w-6xl px-4 py-8 sm:px-6 sm:py-10">
        {{-- Welcome + profile strength --}}
        <div class="relative overflow-hidden rounded-3xl bg-ink-gradient p-6 text-white shadow-xl sm:p-9">
            <div class="pointer-events-none absolute inset-0 bg-grid-ink opacity-50"></div>
            <div class="pointer-events-none absolute -right-16 -top-20 h-72 w-72 rounded-full bg-primary-500/40 blur-3xl animate-blob"></div>
            <div class="pointer-events-none absolute -bottom-24 left-1/3 h-64 w-64 rounded-full bg-accent-500/20 blur-3xl"></div>

            <div class="relative flex flex-col items-start gap-8 sm:flex-row sm:items-center sm:justify-between">
                <div class="max-w-xl">
                    <span class="inline-flex items-center gap-2 rounded-full border border-white/15 bg-white/10 px-3.5 py-1 text-xs font-bold uppercase tracking-wider text-accent-200 backdrop-blur-md">✦ Your dashboard</span>
                    <h1 class="mt-4 font-serif text-3xl font-bold sm:text-4xl">Hi, {{ $user->name }} 👋</h1>
                    <p class="mt-2 text-sm text-ink-200/85">Here's where your profile stands today.</p>

                    @if ($completion['next_action'])
                        <p class="mt-5 text-sm text-ink-200">
                            <span class="font-semibold text-white">{{ $completion['next_action']['label'] }}</span> next — a fuller profile gets seen more and matched better.
                        </p>
                        <a href="{{ route('profile.wizard.start') }}" class="mt-5 inline-flex items-center gap-2 rounded-xl bg-white px-6 py-3 text-sm font-bold text-primary-600 shadow-lg transition-transform hover:scale-105">
                            {{ $pct > 0 ? 'Continue your profile' : 'Complete your profile' }}
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2.4" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" /></svg>
                        </a>
                    @else
                        <p class="mt-5 inline-flex items-center gap-2 rounded-xl bg-white/20 px-4 py-2 text-sm font-semibold text-white ring-1 ring-white/35">✓ Your profile is complete. Nice work.</p>
                    @endif
                </div>

                {{-- Profile-strength ring --}}
                <div class="relative grid h-36 w-36 shrink-0 place-items-center" role="img" aria-label="Profile strength {{ $pct }} percent">
                    <svg class="h-36 w-36 -rotate-90" viewBox="0 0 120 120">
                        <defs>
                            <linearGradient id="ringGrad" x1="0" y1="0" x2="1" y2="1">
                                <stop offset="0%" stop-color="#FFE4F2" />
                                <stop offset="50%" stop-color="#F5CB66" />
                                <stop offset="100%" stop-color="#FF2E7E" />
                            </linearGradient>
                        </defs>
                        <circle cx="60" cy="60" r="52" fill="none" stroke="rgba(255,255,255,.12)" stroke-width="10" />
                        <circle cx="60" cy="60" r="52" fill="none" stroke="url(#ringGrad)" stroke-width="10" stroke-linecap="round"
                                stroke-dasharray="{{ $circ }}" stroke-dashoffset="{{ $circ * (1 - $pct / 100) }}" class="transition-all duration-1000" />
                    </svg>
                    <div class="absolute text-center">
                        <p class="font-serif text-3xl font-bold">{{ $pct }}%</p>
                        <p class="text-[10px] font-semibold uppercase tracking-wider text-ink-200/80">Strength</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Activity stats --}}
        <div class="stagger mt-6 grid gap-4 sm:grid-cols-3">
            @foreach ($stats as [$label, $value, $path, $chip, $statRoute])
                <a href="{{ route($statRoute) }}" class="card hover-lift group flex items-center justify-between gap-4 p-5 transition-all hover:border-primary-300">
                    <div class="flex items-center gap-4">
                        <span class="grid h-12 w-12 shrink-0 place-items-center rounded-2xl {{ $chip }} transition-transform group-hover:scale-105">
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $path }}" /></svg>
                        </span>
                        <div>
                            <p class="font-serif text-3xl font-bold leading-none text-ink-900 group-hover:text-primary-600 transition-colors">{{ $value }}</p>
                            <p class="mt-1.5 text-sm font-medium" style="color:var(--text-muted)">{{ $label }}</p>
                        </div>
                    </div>
                    <span class="text-ink-400 transition-transform group-hover:translate-x-1 group-hover:text-primary-600">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" /></svg>
                    </span>
                </a>
            @endforeach
        </div>

        {{-- Quick actions --}}
        <h2 class="mt-10 font-serif text-xl font-bold text-ink-900">Jump back in</h2>
        <div class="stagger mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ($actions as [$title, $copy, $route, $path])
                <a href="{{ route($route) }}" class="card hover-lift group p-5">
                    <span class="grid h-11 w-11 place-items-center rounded-xl bg-primary-50 text-primary-600 transition-colors group-hover:bg-brand-gradient group-hover:text-white">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $path }}" /></svg>
                    </span>
                    <p class="mt-4 font-bold text-ink-900">{{ $title }}</p>
                    <p class="mt-0.5 text-sm" style="color:var(--text-muted)">{{ $copy }}</p>
                </a>
            @endforeach
        </div>

        <div class="mt-8 flex flex-wrap gap-3">
            <a href="{{ route('profile.show') }}" class="btn-outline">My profile</a>
            <a href="{{ route('profile.wizard.start') }}" class="btn-outline">Edit profile</a>
        </div>
    </div>
@endsection
