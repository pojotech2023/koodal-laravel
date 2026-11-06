@extends('layouts.app')

@section('title', 'Spot the Difference Arena')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/animations.css') }}">
@endpush

@section('content')
<div x-data="gameArena({ matchId: {{ $match->id }}, driver: '{{ $driver }}' })" class="max-w-7xl mx-auto px-4 py-6 space-y-6 select-none">

    {{-- Scoreboard & Timer Bar --}}
    <div class="rounded-3xl border bg-white p-5 shadow-lg flex flex-col md:flex-row items-center justify-between gap-6" style="border-color:var(--border-default)">

        {{-- Player 1 (You) --}}
        <div class="flex items-center gap-4 w-full md:w-1/3">
            <div class="h-14 w-14 rounded-2xl bg-blue-600 text-white grid place-items-center font-black text-xl shadow-md shrink-0">
                <span x-text="me ? me.name.charAt(0).toUpperCase() : 'Y'"></span>
            </div>
            <div class="min-w-0">
                <div class="flex items-center gap-2">
                    <span class="font-extrabold text-ink-900 truncate text-base" x-text="me ? me.name : 'You'"></span>
                    <span class="rounded-full bg-blue-100 px-2 py-0.5 text-[10px] font-black text-blue-700">YOU</span>
                </div>
                <div class="flex items-center gap-3 text-xs mt-1 font-bold">
                    <span class="text-blue-600 text-lg" x-text="me ? me.score : 0">0</span> pts
                    <span class="text-slate-400">&bull;</span>
                    <span class="text-emerald-600" x-text="me ? me.correct_count : 0">0</span> found
                </div>
            </div>
        </div>

        {{-- Center Timer & Status --}}
        <div class="flex flex-col items-center justify-center shrink-0">
            <div class="rounded-2xl bg-slate-900 px-6 py-2 text-center text-white shadow-inner">
                <span class="block text-[10px] font-black uppercase tracking-widest text-slate-400">Time Remaining</span>
                <span class="font-mono text-3xl font-black text-amber-400" x-text="formattedTime">00:00</span>
            </div>
            <p class="text-xs font-bold text-ink-500 mt-2">
                Found: <span x-text="claimedCount"></span> / <span x-text="totalDiffs"></span>
            </p>
        </div>

        {{-- Player 2 (Opponent) --}}
        <div class="flex items-center justify-end gap-4 w-full md:w-1/3 text-right">
            <div class="min-w-0">
                <div class="flex items-center justify-end gap-2">
                    <span class="rounded-full bg-amber-100 px-2 py-0.5 text-[10px] font-black text-amber-700">OPPONENT</span>
                    <span class="font-extrabold text-ink-900 truncate text-base" x-text="opponent ? opponent.name : 'Opponent'"></span>
                </div>
                <div class="flex items-center justify-end gap-3 text-xs mt-1 font-bold">
                    <span class="text-amber-600 text-lg" x-text="opponent ? opponent.score : 0">0</span> pts
                    <span class="text-slate-400">&bull;</span>
                    <span class="text-emerald-600" x-text="opponent ? opponent.correct_count : 0">0</span> found
                </div>
            </div>
            <div class="h-14 w-14 rounded-2xl bg-amber-500 text-white grid place-items-center font-black text-xl shadow-md shrink-0">
                <span x-text="opponent ? opponent.name.charAt(0).toUpperCase() : 'O'"></span>
            </div>
        </div>
    </div>

    {{-- Cooldown Banner --}}
    <div x-show="cooldownSeconds > 0" x-cloak class="rounded-2xl border border-rose-200 bg-rose-50 p-3 text-center text-xs font-bold text-rose-700 animate-bounce">
        Wrong click penalty! Cooldown active for <span x-text="cooldownSeconds"></span>s
    </div>

    {{-- Dual Game Canvas Arena --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

        {{-- Original Image Panel --}}
        <div class="space-y-2">
            <div class="flex items-center justify-between text-xs font-bold text-ink-600 uppercase tracking-wider">
                <span>Original Image</span>
            </div>

            <div class="relative rounded-3xl overflow-hidden border-4 border-white bg-slate-900 shadow-xl cursor-crosshair"
                 :class="{ 'animate-shake': shakingPanel === 'original' }"
                 @click="handleTap($event, 'original')" x-ref="origPanel">

                <template x-if="puzzle.original_image_url">
                    <img :src="puzzle.original_image_url" class="w-full h-auto block select-none pointer-events-none" @load="updateDimensions()">
                </template>

                {{-- Claimed Spot Layers & Animations on Original --}}
                <template x-for="claim in match.claims" :key="claim.id">
                    <div class="absolute inset-0 pointer-events-none">
                        {{-- Ring Marker --}}
                        <div class="absolute -translate-x-1/2 -translate-y-1/2 grid h-8 w-8 place-items-center rounded-full border-4 shadow-xl z-20"
                             :class="claim.is_me ? 'border-blue-500 bg-blue-500/30' : 'border-amber-500 bg-amber-500/30'"
                             :style="`left: ${claim.x_pct}%; top: ${claim.y_pct}%;`">
                            <span class="h-2 w-2 rounded-full" :class="claim.is_me ? 'bg-blue-600' : 'bg-amber-600'"></span>
                        </div>

                        {{-- Patch masking layer if animate_on === original --}}
                        <template x-if="claim.animation && claim.animation.animate_on === 'original'">
                            <div class="spot-patch-mask"
                                 :style="`left: ${claim.animation.sprite_bbox.x_pct}%; top: ${claim.animation.sprite_bbox.y_pct}%; width: ${claim.animation.sprite_bbox.w_pct}%; height: ${claim.animation.sprite_bbox.h_pct}%;`">
                                <div class="w-full h-full bg-cover"
                                     :style="`background-image: url('${puzzle.modified_image_url}'); background-position: ${claim.animation.sprite_bbox.x_pct}% ${claim.animation.sprite_bbox.y_pct}%;`"></div>
                                <template x-if="claim.animation.sprite_url">
                                    <img :src="claim.animation.sprite_url" class="absolute inset-0 w-full h-full object-contain" :class="getAnimClass(claim.animation.type)">
                                </template>
                            </div>
                        </template>
                    </div>
                </template>

                {{-- Temporary Wrong Click Red X Indicators --}}
                <template x-for="mark in wrongMarks" :key="mark.id">
                    <div class="absolute -translate-x-1/2 -translate-y-1/2 grid h-10 w-10 place-items-center rounded-full bg-rose-600 text-white font-black text-xl shadow-2xl z-40 animate-fade-mark pointer-events-none"
                         :style="`left: ${mark.x_pct}%; top: ${mark.y_pct}%;`">
                        &times;
                    </div>
                </template>
            </div>
        </div>

        {{-- Modified Image Panel --}}
        <div class="space-y-2">
            <div class="flex items-center justify-between text-xs font-bold text-ink-600 uppercase tracking-wider">
                <span>Modified Image</span>
            </div>

            <div class="relative rounded-3xl overflow-hidden border-4 border-white bg-slate-900 shadow-xl cursor-crosshair"
                 :class="{ 'animate-shake': shakingPanel === 'modified' }"
                 @click="handleTap($event, 'modified')" x-ref="modPanel">

                <template x-if="puzzle.modified_image_url">
                    <img :src="puzzle.modified_image_url" class="w-full h-auto block select-none pointer-events-none">
                </template>

                {{-- Claimed Spot Layers & Animations on Modified --}}
                <template x-for="claim in match.claims" :key="claim.id">
                    <div class="absolute inset-0 pointer-events-none">
                        <div class="absolute -translate-x-1/2 -translate-y-1/2 grid h-8 w-8 place-items-center rounded-full border-4 shadow-xl z-20"
                             :class="claim.is_me ? 'border-blue-500 bg-blue-500/30' : 'border-amber-500 bg-amber-500/30'"
                             :style="`left: ${claim.x_pct}%; top: ${claim.y_pct}%;`">
                            <span class="h-2 w-2 rounded-full" :class="claim.is_me ? 'bg-blue-600' : 'bg-amber-600'"></span>
                        </div>

                        <template x-if="claim.animation && claim.animation.animate_on === 'modified'">
                            <div class="spot-patch-mask"
                                 :style="`left: ${claim.animation.sprite_bbox.x_pct}%; top: ${claim.animation.sprite_bbox.y_pct}%; width: ${claim.animation.sprite_bbox.w_pct}%; height: ${claim.animation.sprite_bbox.h_pct}%;`">
                                <div class="w-full h-full bg-cover"
                                     :style="`background-image: url('${puzzle.original_image_url}'); background-position: ${claim.animation.sprite_bbox.x_pct}% ${claim.animation.sprite_bbox.y_pct}%;`"></div>
                                <template x-if="claim.animation.sprite_url">
                                    <img :src="claim.animation.sprite_url" class="absolute inset-0 w-full h-full object-contain" :class="getAnimClass(claim.animation.type)">
                                </template>
                            </div>
                        </template>
                    </div>
                </template>

                <template x-for="mark in wrongMarks" :key="mark.id">
                    <div class="absolute -translate-x-1/2 -translate-y-1/2 grid h-10 w-10 place-items-center rounded-full bg-rose-600 text-white font-black text-xl shadow-2xl z-40 animate-fade-mark pointer-events-none"
                         :style="`left: ${mark.x_pct}%; top: ${mark.y_pct}%;`">
                        &times;
                    </div>
                </template>
            </div>
        </div>

    </div>
</div>

@push('scripts')
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('gameArena', (cfg) => ({
        matchId: cfg.matchId,
        driver: cfg.driver,
        match: { claims: [], players: [], puzzle: {} },
        secondsLeft: 0,
        timer: null,
        cooldownSeconds: 0,
        shakingPanel: null,
        wrongMarks: [],

        init() {
            this.fetchState();
            this.timer = setInterval(() => this.tick(), 1000);
            if (this.driver === 'polling') {
                setInterval(() => this.fetchState(), 1500);
            }
        },

        tick() {
            if (this.secondsLeft > 0) this.secondsLeft--;
            if (this.cooldownSeconds > 0) this.cooldownSeconds--;
        },

        async fetchState() {
            try {
                const res = await fetch(`/spot-matches/${this.matchId}/state`, {
                    headers: { 'Accept': 'application/json' }
                });
                const json = await res.json();
                this.match = json.data;
                this.secondsLeft = this.match.seconds_left || 0;

                if (this.match.status === 'finished') {
                    window.location.reload();
                }
            } catch(e) {
                console.error(e);
            }
        },

        async handleTap(e, panelType) {
            if (this.cooldownSeconds > 0) return;

            const rect = e.currentTarget.getBoundingClientRect();
            const x = e.clientX - rect.left;
            const y = e.clientY - rect.top;

            const xPct = Math.round((x / rect.width) * 10000) / 100;
            const yPct = Math.round((y / rect.height) * 10000) / 100;

            try {
                const res = await fetch(`/spot-matches/${this.matchId}/claim`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ x_pct: xPct, y_pct: yPct })
                });
                const data = await res.json();

                if (data.success) {
                    if (!data.is_correct) {
                        // Wrong tap
                        this.cooldownSeconds = data.cooldown_seconds || 2;
                        this.shakingPanel = panelType;
                        setTimeout(() => this.shakingPanel = null, 500);

                        this.wrongMarks.push({ id: Date.now(), x_pct: xPct, y_pct: yPct });
                        setTimeout(() => this.wrongMarks.shift(), 1200);
                    }
                    this.fetchState();
                } else if (data.reason === 'cooldown') {
                    this.cooldownSeconds = data.cooldown_remaining || 2;
                }
            } catch(e) {
                console.error(e);
            }
        },

        get me() {
            return this.match.players.find(p => p.is_me);
        },

        get opponent() {
            return this.match.players.find(p => !p.is_me);
        },

        get puzzle() {
            return this.match.puzzle || {};
        },

        get claimedCount() {
            return this.puzzle.found_differences_count || 0;
        },

        get totalDiffs() {
            return this.puzzle.total_differences || 0;
        },

        get formattedTime() {
            const m = Math.floor(this.secondsLeft / 60).toString().padStart(2, '0');
            const s = (this.secondsLeft % 60).toString().padStart(2, '0');
            return `${m}:${s}`;
        },

        getAnimClass(type) {
            const map = {
                'fly_away': 'animate-fly-away',
                'fall': 'animate-fall',
                'swing_open': 'animate-swing-open',
                'drift': 'animate-drift',
                'pulse': 'animate-pulse-custom',
                'blink': 'animate-blink',
                'fade_out': 'animate-fade-out'
            };
            return map[type] || 'animate-pulse-custom';
        }
    }));
});
</script>
@endpush
@endsection
