@extends('layouts.auth')

@section('title', 'Reset password — '.config('app.name'))
@section('robots', 'noindex, nofollow')

@section('body')
    <section class="mx-auto max-w-sm px-4 py-16">
        <h1 class="font-serif text-3xl font-bold text-ink-900 sm:text-4xl">Reset your password</h1>
        <p class="mt-2 text-sm" style="color:var(--text-muted)">
            Enter the 6-digit code we sent, and your new password.
        </p>

        @if (session('status'))
            <div class="mt-4 rounded-md border border-success bg-success/10 p-3 text-sm font-medium text-success">
                {{ session('status') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="mt-4 rounded-md border border-danger bg-danger/10 p-3 text-sm font-medium text-danger">
                @foreach ($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('password.reset') }}" class="mt-8 space-y-5">
            @csrf
            <input type="hidden" name="identifier" value="{{ $identifier }}">

            <div>
                <label for="code" class="block text-sm font-medium">Verification code</label>
                <input id="code" name="code" type="text" inputmode="numeric" pattern="[0-9]*" required
                       minlength="6" maxlength="6" autofocus autocomplete="one-time-code"
                       class="mt-1 w-full rounded-md border px-3 py-2 text-center text-lg tracking-[0.5em]" style="border-color:var(--border-default)">
            </div>

            <div>
                <label for="password" class="block text-sm font-medium">New password</label>
                <input id="password" name="password" type="password" required
                       class="mt-1 w-full rounded-md border px-3 py-2 text-sm" style="border-color:var(--border-default)">
            </div>

            <div>
                <label for="password_confirmation" class="block text-sm font-medium">Confirm new password</label>
                <input id="password_confirmation" name="password_confirmation" type="password" required
                       class="mt-1 w-full rounded-md border px-3 py-2 text-sm" style="border-color:var(--border-default)">
            </div>

            <button type="submit" class="w-full rounded-md bg-primary-500 px-4 py-2.5 text-sm font-medium text-white hover:bg-primary-600">
                Reset password
            </button>
        </form>

        <form method="POST" action="{{ route('otp.resend') }}" class="mt-4">
            @csrf
            <input type="hidden" name="identifier" value="{{ $identifier }}">
            <input type="hidden" name="purpose" value="password_reset">
            <button type="submit" class="text-sm font-medium text-primary-600 hover:underline">
                Didn't get a code? Resend
            </button>
        </form>
    </section>
@endsection
