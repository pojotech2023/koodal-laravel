@extends('layouts.auth')

@section('title', 'Forgot password — '.config('app.name'))
@section('robots', 'noindex, follow')

@section('body')
    <section class="mx-auto max-w-md px-4 py-16">
        <h1 class="font-serif text-3xl font-bold text-ink-900 sm:text-4xl">Forgot your password?</h1>
        <p class="mt-2 text-sm" style="color:var(--text-muted)">
            Enter your mobile number or email and we'll send a reset code.
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

        <form method="POST" action="{{ route('password.forgot') }}" class="mt-8 space-y-5">
            @csrf

            <div>
                <label for="identifier" class="block text-sm font-medium">Mobile number or email</label>
                <input id="identifier" name="identifier" type="text" required maxlength="190"
                       value="{{ old('identifier') }}" autofocus
                       class="mt-1 w-full rounded-md border px-3 py-2 text-sm" style="border-color:var(--border-default)">
            </div>

            <button type="submit" class="w-full rounded-md bg-primary-500 px-4 py-2.5 text-sm font-medium text-white hover:bg-primary-600">
                Send reset code
            </button>
        </form>

        <p class="mt-6 text-center text-sm" style="color:var(--text-muted)">
            <a href="{{ route('login') }}" class="font-medium text-primary-600">Back to log in</a>
        </p>
    </section>
@endsection
