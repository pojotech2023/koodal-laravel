@extends('layouts.app')

@section('title', 'Match Lobby')

@section('content')
<div x-data="matchLobby({ matchId: {{ $match->id }}, driver: '{{ $driver }}' })" class="max-w-3xl mx-auto px-4 py-12 space-y-8">

    {{-- Header --}}
    <div class="text-center space-y-2">
        <h1 class="text-3xl font-black text-ink-900 tracking-tight">Match Lobby</h1>
        <p class="text-sm font-bold text-primary-600 uppercase tracking-widest">{{ $match->puzzle->title }}</p>
    </div>

    {{-- Countdown Overlay (Triggers when starting) --}}
    <div x-show="countdown > 0" x-cloak class="fixed inset-0 z-50 flex flex-col items-center justify-center bg-slate-900/90 text-white backdrop-blur-md">
        <p class="text-xl font-bold uppercase tracking-widest text-emerald-400">Both Players Ready!</p>
        <div class="text-9xl font-black animate-pulse my-4" x-text="countdown"></div>
        <p class="text-sm text-slate-400">Get ready to spot differences!</p>
    </div>

    {{-- Player Slots --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <template x-for="(player, idx) in match.players" :key="player.id">
            <div class="rounded-3xl border bg-white p-6 shadow-md flex flex-col items-center text-center space-y-4 relative overflow-hidden" style="border-color:var(--border-default)">
                <div class="h-20 w-20 rounded-full bg-brand-gradient text-white grid place-items-center text-2xl font-black shadow-lg">
                    <span x-text="player.name.charAt(0).toUpperCase()"></span>
                </div>

                <div>
                    <h3 class="text-lg font-bold text-ink-900" x-text="player.name"></h3>
                    <p class="text-xs text-ink-400" x-text="player.is_me ? '(You)' : 'Opponent'"></p>
                </div>

                <div>
                    <span x-show="player.status === 'ready'" class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-4 py-1.5 text-xs font-black text-emerald-700">
                        <span class="h-2 w-2 rounded-full bg-emerald-500"></span> READY
                    </span>
                    <span x-show="player.status !== 'ready'" class="inline-flex items-center gap-1.5 rounded-full bg-amber-50 px-4 py-1.5 text-xs font-black text-amber-700">
                        <span class="h-2 w-2 rounded-full bg-amber-500"></span> WAITING
                    </span>
                </div>
            </div>
        </template>

        {{-- Empty Slot if invitee hasn't joined yet --}}
        <template x-if="match.players.length < 2">
            <div class="rounded-3xl border border-dashed p-8 grid place-items-center text-center space-y-3 bg-slate-50/50">
                <div class="h-16 w-16 rounded-full bg-slate-200 grid place-items-center text-slate-400">
                    <svg class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                </div>
                <div>
                    <p class="text-sm font-bold text-ink-700">Waiting for opponent to join...</p>
                    <p class="text-xs text-ink-400 mt-1">Invitation link sent to invited email.</p>
                </div>
            </div>
        </template>
    </div>

    {{-- Actions Bar --}}
    <div class="rounded-2xl border bg-white p-6 shadow-sm flex flex-col sm:flex-row items-center justify-between gap-4" style="border-color:var(--border-default)">
        <div>
            <p class="text-xs text-ink-500">Both players must click <strong>Ready</strong> to start the countdown.</p>
        </div>

        <div class="flex items-center gap-3">
            @can('cancel', $match)
                <form method="POST" action="{{ route('spot.matches.cancel', $match->id) }}">
                    @csrf
                    <button type="submit" class="rounded-xl border border-rose-200 px-4 py-2.5 text-xs font-bold text-rose-600 hover:bg-rose-50">
                        Cancel Match
                    </button>
                </form>
            @endcan

            <button type="button" @click="toggleReady()" class="rounded-xl bg-brand-gradient px-8 py-3 text-sm font-black text-white shadow-glow hover:-translate-y-0.5 transition-transform" :class="{ '!bg-emerald-600': isMyStatusReady }">
                <span x-text="isMyStatusReady ? 'Cancel Ready' : 'CLICK READY'"></span>
            </button>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('matchLobby', (cfg) => ({
        matchId: cfg.matchId,
        driver: cfg.driver,
        match: { players: [] },
        countdown: 0,
        timer: null,

        init() {
            this.fetchState();
            this.timer = setInterval(() => this.fetchState(), 1500);
        },

        async fetchState() {
            try {
                const res = await fetch(`/spot-matches/${this.matchId}/state`, {
                    headers: { 'Accept': 'application/json' }
                });
                const json = await res.json();
                this.match = json.data;

                if (this.match.status === 'playing') {
                    clearInterval(this.timer);
                    this.startCountdownAndRedirect();
                }
            } catch(e) {
                console.error(e);
            }
        },

        async toggleReady() {
            try {
                const res = await fetch(`/spot-matches/${this.matchId}/ready`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    }
                });
                const json = await res.json();
                this.match = json.data;

                if (this.match.status === 'playing') {
                    clearInterval(this.timer);
                    this.startCountdownAndRedirect();
                }
            } catch(e) {
                console.error(e);
            }
        },

        get isMyStatusReady() {
            const me = this.match.players.find(p => p.is_me);
            return me && me.status === 'ready';
        },

        startCountdownAndRedirect() {
            this.countdown = 3;
            const cdTimer = setInterval(() => {
                this.countdown--;
                if (this.countdown <= 0) {
                    clearInterval(cdTimer);
                    window.location.reload();
                }
            }, 1000);
        }
    }));
});
</script>
@endpush
@endsection
