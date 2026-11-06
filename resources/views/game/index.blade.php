@extends('layouts.app')

@section('title', 'Spot the Difference 1v1')

@section('content')
<div class="max-w-6xl mx-auto px-4 py-8 space-y-8">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h1 class="text-3xl font-extrabold text-ink-900 tracking-tight">Spot the Difference 1v1</h1>
            <p class="mt-1 text-sm text-ink-600">Challenge a friend to a real-time 1v1 puzzle battle! Race to find all hidden differences first.</p>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('spot.leaderboard') }}" class="rounded-xl border bg-white px-4 py-2.5 text-sm font-bold text-ink-700 hover:bg-slate-50 shadow-sm" style="border-color:var(--border-default)">
                Leaderboard
            </a>
            <a href="{{ route('spot.matches.create') }}" class="inline-flex items-center gap-2 rounded-xl bg-brand-gradient px-5 py-2.5 text-sm font-bold text-white shadow-glow hover:-translate-y-0.5 transition-transform">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Create Match
            </a>
        </div>
    </div>

    {{-- Active Puzzles Gallery --}}
    <div class="space-y-4">
        <h2 class="text-xl font-bold text-ink-900">Select a Puzzle</h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-6">
            @forelse($puzzles as $puzzle)
                <div class="rounded-2xl border bg-white overflow-hidden shadow-sm hover:shadow-md transition-all flex flex-col" style="border-color:var(--border-default)">
                    <div class="relative h-48 bg-slate-900">
                        <img src="{{ Storage::disk('public')->url($puzzle->original_image) }}" class="w-full h-full object-cover">
                        <span class="absolute top-3 right-3 rounded-full bg-slate-900/80 px-2.5 py-1 text-[10px] font-bold uppercase tracking-wider text-white backdrop-blur-sm">
                            {{ $puzzle->difficulty }}
                        </span>
                    </div>
                    <div class="p-5 flex-1 flex flex-col justify-between space-y-4">
                        <div>
                            <h3 class="text-lg font-bold text-ink-900">{{ $puzzle->title }}</h3>
                            <p class="text-xs text-ink-500 mt-1">{{ $puzzle->differences_count }} differences &bull; {{ $puzzle->time_limit_seconds }}s timer</p>
                        </div>
                        <a href="{{ route('spot.matches.create', ['puzzle_id' => $puzzle->id]) }}" class="block text-center rounded-xl bg-slate-900 py-2.5 text-xs font-bold text-white hover:bg-slate-800 transition-colors">
                            Host Game with this Puzzle
                        </a>
                    </div>
                </div>
            @empty
                <div class="col-span-full rounded-2xl border p-12 text-center text-ink-400 bg-white">
                    No active puzzles found. Ask an administrator to create and activate a puzzle in the admin panel.
                </div>
            @endforelse
        </div>
    </div>

    {{-- Recent Matches --}}
    @if($myMatches->isNotEmpty())
        <div class="space-y-4 pt-6 border-t" style="border-color:var(--border-default)">
            <h2 class="text-xl font-bold text-ink-900">Your Recent Matches</h2>
            <div class="rounded-2xl border bg-white divide-y shadow-sm" style="border-color:var(--border-default)">
                @foreach($myMatches as $m)
                    <div class="p-4 flex items-center justify-between">
                        <div class="flex items-center gap-4">
                            <div class="h-10 w-10 rounded-full bg-primary-100 grid place-items-center text-primary-700 font-bold text-sm">
                                {{ strtoupper(mb_substr($m->puzzle->title ?? 'P', 0, 1)) }}
                            </div>
                            <div>
                                <p class="font-bold text-ink-900 text-sm">{{ $m->puzzle->title }}</p>
                                <p class="text-xs text-ink-500">
                                    vs {{ $m->players->firstWhere('user_id', '!=', auth()->id())?->user?->name ?? 'Opponent' }}
                                    &bull; Status: <span class="capitalize font-semibold">{{ $m->status }}</span>
                                </p>
                            </div>
                        </div>
                        <a href="{{ route('spot.matches.show', $m->id) }}" class="rounded-xl border px-3.5 py-1.5 text-xs font-bold text-ink-700 hover:bg-slate-50" style="border-color:var(--border-default)">
                            Open Match
                        </a>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</div>
@endsection
