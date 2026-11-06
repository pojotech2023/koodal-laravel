@extends('layouts.public')

@section('title', $settings['meta_title'] ?? config('app.name').' — Trusted Matrimonial Matchmaking')
@section('description', $settings['meta_description'] ?? "India's trusted matrimonial platform — verified profiles, real conversations, real marriages.")

@section('body')
    {{-- ───────────────────────── Hero ───────────────────────── --}}
    <section class="relative overflow-hidden">
        <div class="pointer-events-none absolute inset-0 bg-brand-soft"></div>
        <div class="pointer-events-none absolute inset-0 bg-dots opacity-60 [mask-image:radial-gradient(ellipse_at_top,black,transparent_70%)]"></div>
        <div class="pointer-events-none absolute -left-24 top-10 h-96 w-96 rounded-full bg-primary-300/40 blur-3xl animate-blob"></div>
        <div class="pointer-events-none absolute -right-24 top-40 h-96 w-96 rounded-full bg-accent-300/40 blur-3xl animate-blob" style="animation-delay:-6s"></div>

        <div class="relative mx-auto grid max-w-7xl items-center gap-12 px-4 py-14 sm:px-6 sm:py-20 lg:grid-cols-12 lg:py-24">
            <div class="animate-fade-up lg:col-span-7">
                <span class="eyebrow">
                    <span class="relative flex h-2 w-2"><span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-primary-500 opacity-70"></span><span class="relative inline-flex h-2 w-2 rounded-full bg-primary-500"></span></span>
                    Trusted matrimony · Verified profiles &amp; Jadhagam
                </span>

                <h1 class="mt-6 font-serif text-4xl font-bold leading-[1.08] text-ink-900 sm:text-5xl lg:text-[3.6rem]">
                    Find someone who fits your
                    <span class="text-gradient">life, family &amp; heart.</span>
                </h1>

                <p class="mt-5 max-w-xl text-base leading-relaxed text-ink-500 sm:text-lg">
                    Verified profiles, authentic Jadhagam matching and privacy controls built around
                    your family — everything you need to begin a lifelong journey with confidence.
                </p>

                {{-- Quick match finder --}}
                <form method="GET" action="{{ route('browse') }}"
                      class="mt-8 grid max-w-2xl grid-cols-2 items-end gap-3 rounded-3xl border border-white bg-white/80 p-4 shadow-lg backdrop-blur-xl sm:grid-cols-[1.3fr_1fr_1fr_auto] sm:p-4">
                    <div class="col-span-2 sm:col-span-1">
                        <label for="quick_looking_for" class="mb-1 block text-[11px] font-bold uppercase tracking-wider text-ink-400">I'm looking for</label>
                        <select id="quick_looking_for" name="looking_for" class="w-full border bg-white px-3 py-2.5 text-sm font-semibold">
                            <option value="female">Bride (பெண்)</option>
                            <option value="male">Groom (மாப்பிள்ளை)</option>
                        </select>
                    </div>
                    <div>
                        <label for="quick_age_from" class="mb-1 block text-[11px] font-bold uppercase tracking-wider text-ink-400">Age from</label>
                        <select id="quick_age_from" name="age_from" class="w-full border bg-white px-3 py-2.5 text-sm font-semibold">
                            @foreach (range(18, 50) as $age)
                                <option value="{{ $age }}" {{ $age == 21 ? 'selected' : '' }}>{{ $age }} yrs</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="quick_age_to" class="mb-1 block text-[11px] font-bold uppercase tracking-wider text-ink-400">Age to</label>
                        <select id="quick_age_to" name="age_to" class="w-full border bg-white px-3 py-2.5 text-sm font-semibold">
                            @foreach (range(18, 60) as $age)
                                <option value="{{ $age }}" {{ $age == 28 ? 'selected' : '' }}>{{ $age }} yrs</option>
                            @endforeach
                        </select>
                    </div>
                    <button type="submit" class="btn-primary col-span-2 !py-3 sm:col-span-1">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.4" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                        Search
                    </button>
                </form>

                <div class="mt-6 flex flex-wrap items-center gap-4">
                    @guest('web')
                        <a href="{{ route('register') }}" class="btn-primary animate-pulse-glow !px-7 !py-3.5 !text-base">
                            Create your free profile
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" /></svg>
                        </a>
                        <a href="{{ route('browse') }}" class="btn-outline !px-7 !py-3.5 !text-base">Browse all profiles</a>
                    @else
                        <a href="{{ route('dashboard') }}" class="btn-primary !px-7 !py-3.5 !text-base">Go to your dashboard &rarr;</a>
                    @endguest
                </div>

                <ul class="mt-8 flex flex-wrap gap-x-6 gap-y-2 text-sm font-medium text-ink-500">
                    @foreach (['Free to join', 'Manual ID & photo checks', 'You control who sees you'] as $point)
                        <li class="flex items-center gap-2">
                            <svg class="h-4 w-4 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke-width="2.6" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
                            {{ $point }}
                        </li>
                    @endforeach
                </ul>
            </div>

            {{-- Hero visual: illustrative UI, not real members --}}
            <div class="relative hidden lg:col-span-5 lg:block" aria-hidden="true">
                <div class="relative mx-auto h-[30rem] w-full max-w-md">
                    <div class="absolute inset-6 rotate-3 rounded-[2.5rem] bg-brand-gradient opacity-90 shadow-xl"></div>
                    <div class="absolute inset-6 -rotate-3 rounded-[2.5rem] border border-white/60 bg-white/40 backdrop-blur-sm"></div>

                    <div class="absolute inset-x-4 top-8 rounded-3xl border border-white bg-white p-5 shadow-xl animate-float">
                        <div class="flex items-center gap-4">
                            <div class="relative h-16 w-24 shrink-0">
                                <span class="absolute left-0 top-0 grid h-16 w-16 place-items-center rounded-full bg-gradient-to-br from-primary-300 to-primary-600 text-white ring-4 ring-white">
                                    <svg class="h-8 w-8" fill="currentColor" viewBox="0 0 24 24"><path d="M12 12a4 4 0 100-8 4 4 0 000 8zm0 2c-3.3 0-8 1.7-8 5v1h16v-1c0-3.3-4.7-5-8-5z" /></svg>
                                </span>
                                <span class="absolute right-0 top-0 grid h-16 w-16 place-items-center rounded-full bg-gradient-to-br from-accent-300 to-accent-600 text-white ring-4 ring-white">
                                    <svg class="h-8 w-8" fill="currentColor" viewBox="0 0 24 24"><path d="M12 12a4 4 0 100-8 4 4 0 000 8zm0 2c-3.3 0-8 1.7-8 5v1h16v-1c0-3.3-4.7-5-8-5z" /></svg>
                                </span>
                            </div>
                            <div class="min-w-0">
                                <p class="text-[11px] font-bold uppercase tracking-wider text-primary-600">Jadhagam match</p>
                                <p class="font-serif text-2xl font-bold text-ink-900">9 / 10 <span class="text-sm font-semibold text-ink-400">poruthams</span></p>
                            </div>
                        </div>
                        <div class="mt-4 h-2 overflow-hidden rounded-full bg-primary-50"><div class="h-full w-[90%] rounded-full bg-brand-gradient"></div></div>
                    </div>

                    <div class="absolute inset-x-10 bottom-10 rounded-3xl border border-white bg-white p-5 shadow-xl animate-float-delayed">
                        <div class="flex items-center gap-3">
                            <span class="grid h-11 w-11 place-items-center rounded-2xl bg-emerald-100 text-emerald-600">
                                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" /></svg>
                            </span>
                            <div>
                                <p class="text-sm font-bold text-ink-900">Profile verified</p>
                                <p class="text-xs text-ink-400">ID &amp; photo reviewed by our team</p>
                            </div>
                        </div>
                        <div class="mt-4 flex gap-2">
                            <span class="badge bg-primary-50 text-primary-700">♥ Interest sent</span>
                            <span class="badge bg-accent-100 text-accent-700">★ Shortlisted</span>
                        </div>
                    </div>

                    <span class="absolute -right-2 top-1/2 grid h-14 w-14 -translate-y-1/2 place-items-center rounded-2xl bg-gold-gradient text-2xl shadow-lg animate-float">💍</span>
                </div>
            </div>
        </div>
    </section>

    {{-- ───────────────────────── Announcements / banners ───────────────────────── --}}
    @if ($banners->isNotEmpty())
        <section class="mx-auto max-w-7xl px-4 py-12 sm:px-6" aria-label="Announcements">
            <div class="stagger grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($banners as $banner)
                    <a
                        @if ($banner->link_url) href="{{ $banner->link_url }}" @endif
                        class="card hover-lift group block overflow-hidden"
                    >
                        @if ($banner->imageUrl())
                            <div class="overflow-hidden">
                                <img src="{{ $banner->imageUrl() }}" alt="{{ $banner->title }}" class="h-48 w-full object-cover transition-transform duration-700 group-hover:scale-105">
                            </div>
                        @endif
                        @if ($banner->title)
                            <p class="p-5 text-sm font-bold text-ink-900 transition-colors group-hover:text-primary-600">{{ $banner->title }}</p>
                        @endif
                    </a>
                @endforeach
            </div>
        </section>
    @endif

    {{-- ───────────────────────── How it works ───────────────────────── --}}
    <section class="relative py-20 sm:py-24" style="background-color:var(--bg-surface)">
        <div class="mx-auto max-w-7xl px-4 sm:px-6">
            <div class="mx-auto max-w-2xl text-center">
                <span class="eyebrow">Simple 3-step process</span>
                <h2 class="mt-4 font-serif text-3xl font-bold text-ink-900 sm:text-4xl">How {{ config('app.name') }} works</h2>
                <p class="mt-3 text-ink-500">Your effortless pathway to finding the perfect life partner.</p>
            </div>

            <div class="relative mt-14">
                <div class="pointer-events-none absolute left-[16%] right-[16%] top-8 hidden h-px border-t-2 border-dashed border-primary-200 md:block"></div>
                <div class="stagger grid gap-10 md:grid-cols-3">
                    @foreach ([
                        ['1', 'Create your profile', 'Sign up in minutes. Add authentic photos, your story, education, community details and Jadhagam.', 'from-primary-400 to-primary-600'],
                        ['2', 'Connect & match', 'Filter verified matches by community, lifestyle and Jadhagam compatibility, then send an interest.', 'from-primary-500 to-ink-500'],
                        ['3', 'Talk & celebrate', 'Start safe, direct conversations between families and take the auspicious next steps together.', 'from-accent-400 to-accent-600'],
                    ] as [$n, $title, $copy, $grad])
                        <div class="relative text-center">
                            <div class="relative z-10 mx-auto grid h-16 w-16 place-items-center rounded-2xl bg-gradient-to-br {{ $grad }} font-serif text-2xl font-bold text-white shadow-glow ring-8 ring-white">{{ $n }}</div>
                            <h3 class="mt-6 text-lg font-bold text-ink-900">{{ $title }}</h3>
                            <p class="mx-auto mt-2 max-w-xs text-sm leading-relaxed text-ink-500">{{ $copy }}</p>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </section>

    {{-- ───────────────────────── Why families trust us (bento) ───────────────────────── --}}
    <section class="py-20 sm:py-24">
        <div class="mx-auto max-w-7xl px-4 sm:px-6">
            <div class="mx-auto max-w-2xl text-center">
                <span class="eyebrow">Peace of mind</span>
                <h2 class="mt-4 font-serif text-3xl font-bold text-ink-900 sm:text-4xl">Why families trust us</h2>
                <p class="mt-3 text-ink-500">Built with integrity, cultural tradition and modern security.</p>
            </div>

            <div class="stagger mt-14 grid gap-5 md:grid-cols-3 md:grid-rows-2">
                <div class="hover-lift relative overflow-hidden rounded-3xl bg-ink-gradient p-8 text-white shadow-lg md:col-span-2 md:row-span-1">
                    <div class="pointer-events-none absolute -right-10 -top-10 h-56 w-56 rounded-full bg-primary-500/40 blur-3xl"></div>
                    <div class="pointer-events-none absolute inset-0 bg-grid-ink opacity-50"></div>
                    <div class="relative">
                        <span class="grid h-14 w-14 place-items-center rounded-2xl bg-white/10 text-accent-300 ring-1 ring-white/15">
                            <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" /></svg>
                        </span>
                        <h3 class="mt-5 font-serif text-2xl font-bold">Strictly verified profiles</h3>
                        <p class="mt-2 max-w-lg text-sm leading-relaxed text-ink-200/85">Every member photo and government ID is reviewed by our moderation team before a profile goes live — so the person you message is who they say they are.</p>
                    </div>
                </div>

                <div class="hover-lift card p-7">
                    <span class="grid h-12 w-12 place-items-center rounded-2xl bg-accent-100 text-accent-700">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" /></svg>
                    </span>
                    <h3 class="mt-4 font-serif text-lg font-bold text-ink-900">Your privacy, your control</h3>
                    <p class="mt-2 text-sm leading-relaxed text-ink-500">Choose exactly who can see your photos and phone number, and keep them private until you're comfortable.</p>
                </div>

                <div class="hover-lift card p-7">
                    <span class="grid h-12 w-12 place-items-center rounded-2xl bg-primary-100 text-primary-600">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" /></svg>
                    </span>
                    <h3 class="mt-4 font-serif text-lg font-bold text-ink-900">Astrology &amp; horoscope</h3>
                    <p class="mt-2 text-sm leading-relaxed text-ink-500">Upload Jadhagam charts so families can check Porutham and astrological alignment with confidence.</p>
                </div>

                <div class="hover-lift relative overflow-hidden rounded-3xl bg-brand-gradient p-8 text-white shadow-glow md:col-span-2">
                    <div class="pointer-events-none absolute -bottom-12 -right-12 h-56 w-56 rounded-full bg-white/15 blur-2xl"></div>
                    <div class="relative flex flex-col gap-5 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <h3 class="font-serif text-2xl font-bold">Real conversations, real families</h3>
                            <p class="mt-2 max-w-md text-sm leading-relaxed text-white/85">Send an interest, chat safely once you connect, and involve family every step of the way.</p>
                        </div>
                        @guest('web')
                            <a href="{{ route('register') }}" class="inline-flex shrink-0 items-center justify-center rounded-xl bg-white px-6 py-3 text-sm font-bold text-primary-700 shadow-lg transition-transform hover:scale-105">Get started free</a>
                        @endguest
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- ───────────────────────── Success stories ───────────────────────── --}}
    @if ($stories->isNotEmpty())
        <section class="py-20 sm:py-24" style="background-color:var(--bg-surface)" aria-label="Success stories">
            <div class="mx-auto max-w-7xl px-4 sm:px-6">
                <div class="mx-auto max-w-2xl text-center">
                    <span class="eyebrow">Real celebrations</span>
                    <h2 class="mt-4 font-serif text-3xl font-bold text-ink-900 sm:text-4xl">Real stories, real marriages</h2>
                    <p class="mt-3 text-ink-500">Celebrating hearts united through {{ config('app.name') }}.</p>
                </div>

                <div class="stagger mt-14 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($stories as $story)
                        <article class="card hover-lift group overflow-hidden">
                            @if ($story->photoUrl())
                                <div class="relative overflow-hidden">
                                    <img src="{{ $story->photoUrl() }}" alt="{{ $story->groom_name }} &amp; {{ $story->bride_name }}" class="h-60 w-full object-cover transition-transform duration-700 group-hover:scale-105">
                                    <div class="absolute inset-0 bg-gradient-to-t from-ink-900/50 via-transparent to-transparent"></div>
                                </div>
                            @endif
                            <div class="p-6">
                                <h3 class="font-serif text-xl font-bold text-ink-900 transition-colors group-hover:text-primary-600">{{ $story->groom_name }} <span class="text-primary-400">&amp;</span> {{ $story->bride_name }}</h3>
                                @if ($story->location)
                                    <p class="mt-1.5 flex items-center gap-1 text-xs font-semibold text-ink-400">
                                        <svg class="h-3.5 w-3.5 text-primary-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd" /></svg>
                                        {{ $story->location }}
                                    </p>
                                @endif
                                @if ($story->story)
                                    <p class="mt-3 line-clamp-3 text-sm italic leading-relaxed text-ink-500">&ldquo;{{ $story->story }}&rdquo;</p>
                                @endif
                            </div>
                        </article>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{-- ───────────────────────── Closing CTA ───────────────────────── --}}
    <section class="px-4 py-20 sm:px-6 sm:py-24">
        <div class="relative mx-auto max-w-6xl overflow-hidden rounded-[2.5rem] bg-ink-gradient p-10 text-center text-white shadow-xl sm:p-16">
            <div class="pointer-events-none absolute inset-0 bg-grid-ink opacity-60"></div>
            <div class="pointer-events-none absolute -left-20 -top-20 h-80 w-80 rounded-full bg-primary-500/40 blur-3xl animate-blob"></div>
            <div class="pointer-events-none absolute -bottom-24 -right-16 h-80 w-80 rounded-full bg-accent-500/30 blur-3xl animate-blob" style="animation-delay:-7s"></div>

            <div class="relative">
                <span class="inline-block rounded-full border border-white/15 bg-white/10 px-4 py-1.5 text-xs font-bold uppercase tracking-wider text-accent-200 backdrop-blur-md">Your story starts today</span>
                <h2 class="mx-auto mt-5 max-w-2xl font-serif text-3xl font-bold leading-tight sm:text-5xl">Ready to find your life partner?</h2>
                <p class="mx-auto mt-4 max-w-xl text-base text-ink-200/85">
                    Join brides, grooms and families who found their lifelong match on {{ config('app.name') }}.
                </p>

                @guest('web')
                    <div class="mt-9 flex flex-col items-center justify-center gap-3 sm:flex-row">
                        <a href="{{ route('register') }}" class="btn-primary !px-8 !py-3.5 !text-base">Join free today</a>
                        <a href="{{ route('browse') }}" class="inline-flex items-center justify-center rounded-xl border border-white/20 bg-white/10 px-8 py-3.5 text-base font-semibold text-white backdrop-blur-sm transition hover:bg-white/20">Explore matches</a>
                    </div>
                @endguest
            </div>
        </div>
    </section>
@endsection
