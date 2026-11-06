@extends('layouts.app')

{{-- Extends layouts.app directly, not layouts.public — this screen has no
     business showing the member site's register/login nav or footer, and
     must never be indexed regardless of what an individual view sets. --}}
@section('title', 'Staff sign in — '.config('app.name'))
@section('robots', 'noindex, nofollow')

@section('content')
    <section class="relative flex min-h-screen flex-col items-center justify-center overflow-hidden bg-ink-gradient px-4 py-12">
        <div class="pointer-events-none absolute inset-0 bg-grid-ink opacity-60"></div>
        <div class="pointer-events-none absolute -left-24 -top-24 h-[28rem] w-[28rem] rounded-full bg-primary-500/35 blur-3xl animate-blob"></div>
        <div class="pointer-events-none absolute -bottom-32 -right-24 h-[28rem] w-[28rem] rounded-full bg-accent-500/20 blur-3xl animate-blob" style="animation-delay:-7s"></div>

        <div class="relative w-full max-w-md animate-fade-up">
            <div class="flex flex-col items-center text-center">
                <x-logo tone="light" size="h-32" />
                <h1 class="sr-only">{{ config('app.name') }} admin sign in</h1>
                <span class="mt-6 inline-flex items-center gap-1.5 rounded-full border border-white/15 bg-white/10 px-3 py-1 text-[11px] font-bold uppercase tracking-[0.16em] text-accent-200 backdrop-blur-md">
                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z" /></svg>
                    Admin console
                </span>
                <p class="mt-3 text-sm text-ink-200/80">Sign in with your staff account.</p>
            </div>

            <div class="mt-7 rounded-3xl border border-white/10 bg-white p-7 shadow-xl sm:p-8">
                <h2 class="mb-6 font-serif text-2xl font-bold text-ink-900">Welcome back</h2>
                <form method="POST" action="{{ route('admin.login') }}" class="space-y-5" x-data="{ showPassword: false }">
                    @csrf

                    <div>
                        <label for="email" class="block text-sm font-medium">Email</label>
                        <div class="relative mt-1">
                            <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3" style="color:var(--text-muted)">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0a2.25 2.25 0 00-2.25-2.25h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75" />
                                </svg>
                            </span>
                            <input id="email" name="email" type="email" required maxlength="190"
                                   value="{{ old('email') }}" autofocus autocomplete="username"
                                   class="w-full rounded-md border py-2 pl-10 pr-3 text-sm transition-colors focus:border-primary-500"
                                   style="border-color:var(--border-default)">
                        </div>
                    </div>

                    <div>
                        <label for="password" class="block text-sm font-medium">Password</label>
                        <div class="relative mt-1">
                            <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3" style="color:var(--text-muted)">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" />
                                </svg>
                            </span>
                            <input :type="showPassword ? 'text' : 'password'" id="password" name="password" required
                                   autocomplete="current-password"
                                   class="w-full rounded-md border py-2 pl-10 pr-10 text-sm transition-colors focus:border-primary-500"
                                   style="border-color:var(--border-default)">
                            <button type="button" @click="showPassword = !showPassword"
                                    class="absolute inset-y-0 right-0 flex items-center pr-3"
                                    style="color:var(--text-muted)"
                                    :aria-label="showPassword ? 'Hide password' : 'Show password'">
                                <svg x-show="!showPassword" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                                <svg x-show="showPassword" x-cloak class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.243 4.243L9.88 9.88" />
                                </svg>
                            </button>
                        </div>
                    </div>

                    <label class="flex items-center gap-2 text-sm" style="color:var(--text-muted)">
                        <input type="checkbox" name="remember" value="1" class="rounded" style="border-color:var(--border-default)">
                        Keep me signed in
                    </label>

                    <button type="submit" class="w-full rounded-xl bg-primary-500 px-4 py-3 text-sm font-semibold text-white transition-colors hover:bg-primary-600">
                        Sign in
                    </button>
                </form>
            </div>

            <p class="mt-6 text-center text-xs text-ink-200/70">
                Staff access only — every sign-in is logged.
                <br>
                <a href="{{ route('home') }}" class="mt-1 inline-block font-semibold text-accent-300 hover:underline">&larr; Back to {{ config('app.name') }}</a>
            </p>
        </div>
    </section>
@endsection
