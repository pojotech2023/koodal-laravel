@extends('layouts.app')

{{-- Split-screen shell for login / register / OTP / password reset. Views
     keep their own `@section('body')`; only the `@extends` line changes.
     Deliberately no site nav/footer — the only ways out are the logo and the
     link back home, which keeps the sign-up funnel focused. --}}
@section('flash_inline', '1')

@section('content')
    <div class="grid min-h-screen lg:grid-cols-[minmax(0,5fr)_minmax(0,6fr)]">
        {{-- Brand panel --}}
        <aside class="relative hidden overflow-hidden bg-ink-gradient text-white lg:block">
            <div class="pointer-events-none absolute inset-0 bg-grid-ink opacity-60"></div>
            <div class="pointer-events-none absolute -left-24 -top-24 h-96 w-96 rounded-full bg-primary-500/40 blur-3xl animate-blob"></div>
            <div class="pointer-events-none absolute -bottom-32 -right-24 h-96 w-96 rounded-full bg-accent-500/25 blur-3xl animate-blob" style="animation-delay:-7s"></div>

            <div class="relative flex h-full min-h-screen flex-col justify-between p-12 lg:sticky lg:top-0 lg:h-screen">
                <a href="{{ route('home') }}" aria-label="{{ config('app.name') }} home">
                    <x-logo tone="light" size="h-32" />
                </a>

                <div>
                    <span class="inline-flex items-center gap-2 rounded-full border border-white/15 bg-white/10 px-3.5 py-1 text-xs font-bold uppercase tracking-wider text-accent-200 backdrop-blur-md">✦ Bringing hearts together</span>
                    <h2 class="mt-6 font-serif text-4xl font-bold leading-tight xl:text-5xl">
                        Where families find<br><span class="bg-gradient-to-r from-primary-300 to-accent-300 bg-clip-text text-transparent">the perfect match.</span>
                    </h2>

                    <ul class="mt-10 space-y-5">
                        @foreach ([
                            ['Verified profiles', 'Every photo and ID is checked by our team.', 'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z'],
                            ['Jadhagam matching', 'Check Porutham with confidence.', 'M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z'],
                            ['You control privacy', 'Decide who sees your photos and number.', 'M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z'],
                        ] as [$title, $copy, $path])
                            <li class="flex items-start gap-4">
                                <span class="grid h-11 w-11 shrink-0 place-items-center rounded-xl bg-white/10 text-accent-300 ring-1 ring-white/15">
                                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $path }}" /></svg>
                                </span>
                                <div>
                                    <p class="font-semibold">{{ $title }}</p>
                                    <p class="text-sm text-ink-200/80">{{ $copy }}</p>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                </div>

                <p class="text-xs text-ink-200/60">&copy; {{ now()->year }} {{ config('app.name') }}</p>
            </div>
        </aside>

        {{-- Form panel --}}
        <div class="relative flex flex-col bg-brand-soft">
            <div class="pointer-events-none absolute inset-0 bg-dots opacity-50 [mask-image:radial-gradient(ellipse_at_top_right,black,transparent_65%)]"></div>

            <div class="relative flex items-center justify-between px-6 pt-6 lg:justify-end">
                <a href="{{ route('home') }}" class="lg:hidden" aria-label="{{ config('app.name') }} home"><x-logo size="h-[4.5rem]" /></a>
                <a href="{{ route('home') }}" class="text-sm font-semibold text-ink-500 transition-colors hover:text-primary-600">&larr; Back to home</a>
            </div>

            <main class="relative flex flex-1 flex-col justify-center">
                <x-flash />
                <div class="animate-fade-up">
                    @yield('body')
                </div>
            </main>
        </div>
    </div>
@endsection
