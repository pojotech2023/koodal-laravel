@extends('layouts.app')

@section('title', 'Match Results')

@section('content')
<div class="max-w-4xl mx-auto px-4 py-12 space-y-8">

    {{-- Winner Header Banner --}}
    <div class="rounded-3xl bg-slate-900 text-white p-8 text-center shadow-xl relative overflow-hidden">
        @if($winner)
            <div class="inline-flex items-center gap-2 rounded-full bg-amber-400/20 px-4 py-1.5 text-xs font-black text-amber-300 uppercase tracking-widest mb-3 border border-amber-400/30">
                <svg class="h-4 w-4 text-amber-400" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" /></svg>
                VICTORY
            </div>
            <h1 class="text-4xl font-black tracking-tight">{{ $winner->name }} Wins!</h1>
            <p class="text-slate-400 text-sm mt-2 font-medium">Puzzle: {{ $match->puzzle->title }}</p>
        @else
            <div class="inline-flex items-center gap-2 rounded-full bg-slate-800 px-4 py-1.5 text-xs font-black text-slate-300 uppercase tracking-widest mb-3 border border-slate-700">
                DRAW
            </div>
            <h1 class="text-4xl font-black tracking-tight">It's a Tie!</h1>
            <p class="text-slate-400 text-sm mt-2 font-medium">Equal performance on {{ $match->puzzle->title }}</p>
        @endif
    </div>

    {{-- Score Breakdown Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        @foreach($players as $player)
            @php
                $isWinner = ($winner && $winner->id === $player->user_id);
                $isMe = ($player->user_id === auth()->id());
            @endphp
            <div class="rounded-3xl border bg-white p-6 shadow-md space-y-6 relative overflow-hidden {{ $isWinner ? 'ring-2 ring-amber-400' : '' }}" style="border-color:var(--border-default)">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="h-12 w-12 rounded-2xl bg-brand-gradient text-white grid place-items-center text-lg font-black shadow-sm">
                            {{ strtoupper(mb_substr($player->user?->name ?? 'P', 0, 1)) }}
                        </div>
                        <div>
                            <h3 class="font-extrabold text-ink-900 text-base flex items-center gap-2">
                                {{ $player->user?->name }}
                                @if($isMe)<span class="text-[10px] font-black uppercase text-blue-600 bg-blue-50 px-2 py-0.5 rounded-full">YOU</span>@endif
                            </h3>
                            <p class="text-xs text-ink-400">Total Score</p>
                        </div>
                    </div>
                    <span class="text-3xl font-black text-ink-900">{{ $player->score }}</span>
                </div>

                <div class="space-y-3 pt-4 border-t text-sm" style="border-color:var(--border-default)">
                    <div class="flex justify-between text-ink-600">
                        <span>Differences Found</span>
                        <span class="font-bold text-emerald-600">+{{ $player->correct_count }}</span>
                    </div>
                    <div class="flex justify-between text-ink-600">
                        <span>Wrong Clicks</span>
                        <span class="font-bold text-rose-600">{{ $player->wrong_count }}</span>
                    </div>
                    <div class="flex justify-between text-ink-600">
                        <span>Last Spot Claimed</span>
                        <span class="font-mono text-xs">{{ $player->last_claim_at ? $player->last_claim_at->format('H:i:s') : 'N/A' }}</span>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    {{-- Bottom Action Buttons --}}
    <div class="flex items-center justify-center gap-4 pt-4">
        <a href="{{ route('spot.matches.index') }}" class="rounded-2xl border bg-white px-6 py-3 text-sm font-bold text-ink-700 hover:bg-slate-50 shadow-sm" style="border-color:var(--border-default)">
            Back to Match Hub
        </a>

        <form method="POST" action="{{ route('spot.matches.rematch', $match->id) }}">
            @csrf
            <button type="submit" class="rounded-2xl bg-brand-gradient px-8 py-3 text-sm font-black text-white shadow-glow hover:-translate-y-0.5 transition-transform">
                Play Rematch!
            </button>
        </form>
    </div>
</div>
@endsection
