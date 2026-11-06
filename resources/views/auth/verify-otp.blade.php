@extends('layouts.auth')

@section('title', 'Verify your mobile number — '.config('app.name'))
@section('robots', 'noindex, nofollow')

@section('body')
    <section class="mx-auto max-w-md px-4 py-12 text-center">
        <h1 class="font-serif text-3xl font-bold text-ink-900">Verify your mobile number</h1>
        <p class="mt-2 text-sm" style="color:var(--text-muted)">
            Enter the 6-digit code sent to <span class="font-semibold text-ink-800">{{ $maskedIdentifier }}</span>.
        </p>

        @if (session('status'))
            <div class="mt-4 rounded-md border border-emerald-200 bg-emerald-50 px-3 py-2 text-xs font-medium text-emerald-800">
                {{ session('status') }}
            </div>
        @endif

        @error('otp')
            <div class="mt-4 rounded-md border border-danger/20 bg-danger/10 px-3 py-2 text-xs font-medium text-danger">
                {{ $message }}
            </div>
        @enderror

        <form method="POST" action="{{ route('otp.verify') }}" class="mt-6 rounded-2xl border bg-white p-6 shadow-sm text-left space-y-4" style="border-color:var(--border-default)">
            @csrf
            <input type="hidden" name="identifier" value="{{ $identifier }}">
            <input type="hidden" name="purpose" value="{{ $purpose }}">

            <div>
                <label for="code" class="block text-xs font-bold uppercase tracking-wider text-ink-700">Verification code</label>
                <input id="code" name="code" type="text" inputmode="numeric" pattern="[0-9]*" required
                       minlength="6" maxlength="6" autofocus autocomplete="one-time-code" placeholder="1 2 3 4 5 6"
                       class="mt-1.5 w-full rounded-lg border px-3 py-2.5 text-center text-xl font-mono tracking-[0.4em] focus:outline-none focus:ring-2 focus:ring-primary-500/20"
                       style="border-color:var(--border-default)">
                <p class="mt-1.5 text-[11px] text-ink-500">
                    Did not receive SMS? You can use test code <span class="font-mono font-semibold text-ink-700">123456</span> or click Resend.
                </p>
            </div>

            <button type="submit" class="w-full rounded-lg bg-primary-600 px-4 py-2.5 text-sm font-bold text-white shadow hover:bg-primary-700 transition">
                Verify &amp; Continue
            </button>
        </form>

        <form method="POST" action="{{ route('otp.resend') }}" class="mt-4">
            @csrf
            <input type="hidden" name="identifier" value="{{ $identifier }}">
            <input type="hidden" name="purpose" value="{{ $purpose }}">
            <button type="submit" class="text-xs font-semibold text-primary-600 hover:underline">
                Didn't get a code? Resend SMS
            </button>
        </form>
    </section>
@endsection
