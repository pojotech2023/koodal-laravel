@extends('layouts.auth')

@section('title', 'Log in — '.config('app.name'))
@section('robots', 'noindex, follow')

@section('body')
    <section class="flex flex-col items-center px-4 py-12 sm:py-16">
        <div class="w-full max-w-md">
            <div class="flex flex-col items-center text-center">
                <span class="flex h-12 w-12 items-center justify-center rounded-full bg-primary-50 text-primary-600">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12z" />
                    </svg>
                </span>
                <h1 class="mt-4 font-serif text-2xl font-semibold sm:text-3xl">Welcome back</h1>
                <p class="mt-1 text-sm" style="color:var(--text-muted)">Log in with your mobile number or email.</p>
            </div>

            <div class="card mt-8 p-6 sm:p-8">
                <form method="POST" action="{{ route('login') }}" class="space-y-5" x-data="{ showPassword: false }">
                    @csrf

                    <div>
                        <label for="identifier" class="block text-sm font-medium">Mobile number or email</label>
                        <div class="relative mt-1">
                            <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3" style="color:var(--text-muted)">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M17.982 18.725A7.488 7.488 0 0012 15.75a7.488 7.488 0 00-5.982 2.975m11.963 0a9 9 0 10-11.963 0m11.963 0A8.966 8.966 0 0112 21a8.966 8.966 0 01-5.982-2.275M15 9.75a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                            </span>
                            <input id="identifier" name="identifier" type="text" required maxlength="190"
                                   value="{{ old('identifier') }}" autofocus autocomplete="username"
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

                    <div class="flex items-center justify-between">
                        <label class="flex items-center gap-2 text-sm" style="color:var(--text-muted)">
                            <input type="checkbox" name="remember" value="1" class="rounded" style="border-color:var(--border-default)">
                            Keep me logged in
                        </label>
                        <a href="{{ route('password.forgot') }}" class="text-sm font-medium text-primary-600 hover:underline">Forgot password?</a>
                    </div>

                    <button type="submit" class="w-full rounded-md bg-primary-500 px-4 py-2.5 text-sm font-medium text-white transition-colors hover:bg-primary-600">
                        Log in
                    </button>
                </form>
            </div>

            <p class="mt-6 text-center text-sm" style="color:var(--text-muted)">
                New here?
                <a href="{{ route('register') }}" class="font-medium text-primary-600 hover:underline">Create a free profile</a>
            </p>
        </div>
    </section>
@endsection
