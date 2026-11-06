@extends('layouts.app')

@section('title', 'Host a Spot the Difference Match')

@section('content')
<div class="max-w-xl mx-auto px-4 py-12">
    <div class="rounded-3xl border bg-white p-8 shadow-xl space-y-6" style="border-color:var(--border-default)">
        <div>
            <h1 class="text-2xl font-bold text-ink-900">Host a 1v1 Match</h1>
            <p class="mt-1 text-sm text-ink-500">Pick a puzzle and invite your friend by email to play.</p>
        </div>

        @if ($errors->any())
            <div class="rounded-2xl bg-rose-50 p-4 text-xs text-rose-700 border border-rose-200">
                <ul class="list-disc list-inside space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('spot.matches.store') }}" class="space-y-6">
            @csrf

            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-ink-700">Select Puzzle</label>
                <select name="puzzle_id" required class="mt-2 w-full rounded-xl border px-4 py-3 text-sm font-semibold bg-white" style="border-color:var(--border-default)">
                    @foreach($puzzles as $p)
                        <option value="{{ $p->id }}" {{ request('puzzle_id') == $p->id ? 'selected' : '' }}>
                            {{ $p->title }} ({{ $p->differences()->count() }} differences, {{ $p->difficulty }})
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-ink-700">Friend's Email Address</label>
                <input type="email" name="invited_email" value="{{ old('invited_email') }}" placeholder="friend@example.com" required class="mt-2 w-full rounded-xl border px-4 py-3 text-sm" style="border-color:var(--border-default)">
                <p class="mt-1 text-xs text-ink-400">An invitation email with a signed link valid for 24h will be sent to this email.</p>
            </div>

            <div class="pt-4 flex items-center justify-end gap-3">
                <a href="{{ route('spot.matches.index') }}" class="rounded-xl border px-5 py-2.5 text-sm font-bold text-ink-700 hover:bg-slate-50">Cancel</a>
                <button type="submit" class="rounded-xl bg-brand-gradient px-6 py-2.5 text-sm font-bold text-white shadow-glow hover:-translate-y-0.5 transition-transform">
                    Send Invitation & Open Lobby
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
