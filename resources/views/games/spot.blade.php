@extends('layouts.member')

@section('title', '👆 Koodal Spot — Spot the Symbol')

@section('body')
<div class="min-h-screen py-8 px-4 sm:px-6 lg:px-8" style="background: linear-gradient(135deg, #F0FDF4 0%, #DCFCE7 100%)" x-data="spotGame()">
    <div class="mx-auto max-w-2xl space-y-6">

        {{-- Header Bar --}}
        <div class="flex items-center justify-between">
            <a href="{{ route('games.hub', $partner->id) }}" class="inline-flex items-center gap-1 text-sm font-bold text-emerald-700 hover:underline">
                ← Exit Game
            </a>
            <div class="flex items-center gap-3">
                <span class="rounded-full bg-emerald-600 px-3 py-1 text-xs font-black text-white shadow-sm">
                    👆 KOODAL SPOT
                </span>
                <span class="text-xs font-bold text-emerald-800 bg-emerald-100 px-2.5 py-1 rounded-full border border-emerald-200" x-text="`⏱ ${timeTaken}s`"></span>
            </div>
        </div>

        {{-- Target Banner --}}
        <div class="rounded-3xl bg-gradient-to-r from-emerald-600 to-green-700 p-6 text-center shadow-xl text-white">
            <span class="text-xs font-black uppercase tracking-wider text-emerald-100">TARGET OBJECTIVE</span>
            <h2 class="text-3xl font-black text-white mt-1">
                Find All <span class="inline-block text-4xl mx-1 transform scale-125" x-text="targetSymbol"></span>
            </h2>
            <p class="text-sm text-emerald-100 mt-1 font-semibold">
                Found <span class="font-black text-white text-lg" x-text="correctTaps"></span> / <span x-text="targetCount"></span> targets!
            </p>
        </div>

        {{-- Symbol Grid Area --}}
        <div x-show="!gameEnded" class="overflow-hidden rounded-3xl bg-white p-6 shadow-xl border border-emerald-100">
            <div class="grid grid-cols-6 gap-3">
                <template x-for="(sym, idx) in grid" :key="idx">
                    <button type="button"
                            @click="tapCell(idx, sym)"
                            :disabled="tappedCells[idx] !== undefined"
                            :class="{
                                'bg-emerald-500 text-white border-emerald-600 ring-2 ring-emerald-400': tappedCells[idx] === 'correct',
                                'bg-rose-500 text-white border-rose-600 ring-2 ring-rose-400': tappedCells[idx] === 'wrong',
                                'bg-emerald-50/60 border-emerald-200 text-gray-800 hover:bg-emerald-200 hover:border-emerald-400': tappedCells[idx] === undefined
                            }"
                            class="aspect-square rounded-2xl border-2 text-3xl font-bold flex items-center justify-center transition-all transform active:scale-95 disabled:cursor-not-allowed shadow-sm">
                        <span x-text="sym"></span>
                    </button>
                </template>
            </div>
        </div>

        {{-- Result Screen --}}
        <div x-show="gameEnded" class="overflow-hidden rounded-3xl bg-white p-6 sm:p-8 text-center shadow-2xl border border-emerald-100 space-y-6">
            <div class="mx-auto flex h-20 w-20 items-center justify-center rounded-full bg-emerald-100 text-4xl text-emerald-700 border border-emerald-200">
                ⭐
            </div>

            <div>
                <h2 class="text-2xl sm:text-3xl font-black text-gray-900">Spot Challenge Cleared!</h2>
                <p class="text-sm font-semibold text-gray-600 mt-1">
                    Found all <span class="font-black text-emerald-700" x-text="correctTaps"></span> targets in <span class="font-black text-gray-900" x-text="`${timeTaken}s`"></span>!
                </p>
            </div>

            <div class="rounded-2xl bg-emerald-50 p-6 border-2 border-emerald-200">
                <span class="block text-xs font-black uppercase text-emerald-700 tracking-wider">Spot Accuracy Score</span>
                <span class="text-5xl font-black text-emerald-800" x-text="score === null ? '…' : `${score}%`"></span>
            </div>

            <p x-show="error" x-cloak class="text-sm font-bold text-rose-600" x-text="error"></p>

            <button type="button" @click="submitSpotScore()"
                    class="w-full rounded-xl py-3.5 font-extrabold text-white text-base shadow-lg transition-all hover:scale-105"
                    style="background: linear-gradient(135deg, #16A34A, #15803D)">
                Continue to Vibe Meter →
            </button>
        </div>

    </div>
</div>

<script>
function spotGame() {
    return {
        // The board is dealt by the server, which remembers where the ten
        // targets are and scores the taps itself.
        sessionId: @json($round['session_id']),
        grid: @json($grid),
        targetSymbol: @json($targetSymbol),
        targetCount: {{ (int) $targetCount }},
        tappedCells: {},
        taps: [],
        correctTaps: 0,
        wrongTaps: 0,
        timeTaken: 0,
        timer: null,
        gameEnded: false,
        score: null,
        redirect: null,
        error: '',
        init() {
            this.timer = setInterval(() => {
                if (!this.gameEnded) {
                    this.timeTaken++;
                }
            }, 1000);
        },
        tapCell(idx, sym) {
            if (this.gameEnded || this.tappedCells[idx] !== undefined) return;
            this.taps.push(idx);

            if (sym === this.targetSymbol) {
                this.tappedCells[idx] = 'correct';
                this.correctTaps++;
                if (this.correctTaps >= this.targetCount) {
                    this.endGame();
                }
            } else {
                this.tappedCells[idx] = 'wrong';
                this.wrongTaps++;
            }
        },
        endGame() {
            clearInterval(this.timer);
            this.gameEnded = true;

            fetch('{{ route("games.postSpot", $partner->id) }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json',
                },
                body: JSON.stringify({
                    session_id: this.sessionId,
                    taps: this.taps,
                    time_taken: this.timeTaken
                })
            })
            .then(res => res.json().then(data => ({ ok: res.ok, data })))
            .then(({ ok, data }) => {
                if (ok && data.success) {
                    this.score = data.score;
                    this.correctTaps = data.correct_taps;
                    this.wrongTaps = data.wrong_taps;
                    this.redirect = data.redirect;
                } else {
                    this.error = (data && data.message) || 'Could not save this round. Please play again.';
                }
            })
            .catch(() => {
                this.error = 'Could not save this round. Please play again.';
            });
        },
        submitSpotScore() {
            window.location.href = this.redirect || '{{ route("games.vibe", $partner->id) }}';
        }
    }
}
</script>
@endsection
