@extends('layouts.member')

@section('title', '🧠 Koodal Memory — Sequence Recall Game')

@section('body')
<div class="min-h-screen py-8 px-4 sm:px-6 lg:px-8" style="background: linear-gradient(135deg, #F3E8FF 0%, #FAF5FF 100%)" x-data="memoryGame()">
    <div class="mx-auto max-w-2xl space-y-6">

        {{-- Header Bar --}}
        <div class="flex items-center justify-between">
            <a href="{{ route('games.hub', $partner->id) }}" class="inline-flex items-center gap-1 text-sm font-bold text-purple-700 hover:underline">
                ← Exit Game
            </a>
            <div class="flex items-center gap-2">
                <span class="rounded-full bg-purple-600 px-3 py-1 text-xs font-black text-white shadow-sm">
                    🧠 KOODAL MEMORY
                </span>
                <span class="text-xs font-bold text-purple-800">with {{ $partner->name }}</span>
            </div>
        </div>

        {{-- STAGE 1: Display Phase (Memorize) --}}
        <div x-show="stage === 'display'" class="overflow-hidden rounded-3xl bg-white p-6 sm:p-8 text-center shadow-xl border border-purple-100 space-y-6">
            <div>
                <span class="inline-block rounded-full bg-purple-100 px-3 py-1 text-xs font-black text-purple-800 uppercase tracking-wider border border-purple-200">
                    STAGE 1: MEMORIZE SEQUENCE
                </span>
                <h2 class="text-2xl sm:text-3xl font-black text-gray-900 mt-2">Remember the Sequence!</h2>
                <p class="text-sm font-bold text-gray-600 mt-1">
                    Symbols hiding in <span class="font-black text-purple-700 text-base" x-text="`${countdown}s`"></span>...
                </p>
            </div>

            <div class="grid grid-cols-4 sm:grid-cols-7 gap-3">
                <template x-for="(item, idx) in sequence" :key="idx">
                    <div class="rounded-2xl bg-purple-50 p-3 border-2 border-purple-200 text-center shadow-sm">
                        <span class="block text-[10px] font-mono text-purple-700 font-black" x-text="`#${idx+1}`"></span>
                        <span class="block text-3xl my-2" x-text="item.symbol"></span>
                    </div>
                </template>
            </div>
        </div>

        {{-- STAGE 2: Quiz Recall Phase --}}
        <div x-show="stage === 'quiz'" class="overflow-hidden rounded-3xl bg-white p-6 sm:p-8 shadow-xl border border-purple-100 space-y-6">
            <div class="flex items-center justify-between border-b border-purple-100 pb-4">
                <span class="text-xs font-black text-purple-700 uppercase tracking-wider">
                    Question <span x-text="currentQuestion + 1"></span> of <span x-text="quizQuestions.length"></span>
                </span>
                <span class="text-xs text-emerald-700 font-black bg-emerald-50 px-3 py-1 rounded-full border border-emerald-200" x-text="`Score: ${correctCount}`"></span>
            </div>

            <div class="text-center">
                <h3 class="text-xl sm:text-2xl font-black text-gray-900" x-text="quizQuestions[currentQuestion].prompt"></h3>
                <p class="text-xs text-gray-500 font-semibold mt-1">Select the symbol that was at this exact sequence position.</p>
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 pt-2">
                <template x-for="opt in quizQuestions[currentQuestion].options" :key="opt.id">
                    <button type="button"
                            @click="answerQuiz(opt)"
                            class="rounded-2xl bg-purple-50/70 p-5 text-center border-2 border-purple-200 hover:bg-purple-600 hover:text-white hover:border-purple-600 transition-all shadow-sm group">
                        <span class="block text-4xl group-hover:scale-110 transition-transform" x-text="opt.symbol"></span>
                        <span class="block text-xs text-gray-700 group-hover:text-purple-100 mt-2 font-extrabold" x-text="opt.name"></span>
                    </button>
                </template>
            </div>
        </div>

        {{-- STAGE 3: Final Result --}}
        <div x-show="stage === 'result'" class="overflow-hidden rounded-3xl bg-white p-6 sm:p-8 text-center shadow-2xl border border-purple-100 space-y-6">
            <div class="mx-auto flex h-20 w-20 items-center justify-center rounded-full bg-purple-100 text-4xl text-purple-700 border border-purple-200">
                🧠
            </div>

            <div>
                <h2 class="text-2xl sm:text-3xl font-black text-gray-900">Sequence Recall Completed!</h2>
                <p class="text-sm font-semibold text-gray-600 mt-1">
                    You remembered <span class="font-black text-purple-700" x-text="correctCount"></span> out of <span x-text="quizQuestions.length"></span> symbols correctly!
                </p>
            </div>

            <div class="rounded-2xl bg-purple-50 p-6 border-2 border-purple-200">
                <span class="block text-xs font-black uppercase text-purple-700 tracking-wider">Memory Score</span>
                <span class="text-5xl font-black text-purple-800" x-text="score === null ? '…' : `${score}%`"></span>
            </div>

            <p x-show="error" x-cloak class="text-sm font-bold text-rose-600" x-text="error"></p>

            <button type="button" @click="submitMemoryScore()"
                    class="w-full rounded-xl py-3.5 font-extrabold text-white text-base shadow-lg transition-all hover:scale-105"
                    style="background: linear-gradient(135deg, #7C3AED, #5B21B6)">
                Continue to Vibe Meter →
            </button>
        </div>

    </div>
</div>

<script>
function memoryGame() {
    return {
        // Dealt by the server, which keeps the answer key and grades the
        // picks itself — the page never reports its own score.
        sessionId: @json($round['session_id']),
        sequence: @json($round['sequence']),
        quizQuestions: @json($round['quiz']),
        stage: 'display', // display, quiz, result
        countdown: {{ (int) $round['display_seconds'] }},
        timer: null,
        startedAt: Date.now(),
        currentQuestion: 0,
        correctCount: 0,
        answers: [],
        score: null,
        redirect: null,
        submitting: false,
        error: '',
        init() {
            this.startDisplayTimer();
        },
        startDisplayTimer() {
            this.timer = setInterval(() => {
                if (this.countdown > 1) {
                    this.countdown--;
                } else {
                    clearInterval(this.timer);
                    this.stage = 'quiz';
                }
            }, 1000);
        },
        answerQuiz(option) {
            const q = this.quizQuestions[this.currentQuestion];
            this.answers.push({ position: q.position, symbol_id: option.id });

            // Running tally for the header only; the recorded score is the server's.
            const target = this.sequence[q.position - 1];
            if (target && target.id === option.id) {
                this.correctCount++;
            }

            if (this.currentQuestion < this.quizQuestions.length - 1) {
                this.currentQuestion++;
            } else {
                this.stage = 'result';
                this.submitAnswers();
            }
        },
        submitAnswers() {
            if (this.submitting) return;
            this.submitting = true;
            this.error = '';

            fetch('{{ route("games.postMemory", $partner->id) }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json',
                },
                body: JSON.stringify({
                    session_id: this.sessionId,
                    answers: this.answers,
                    time_taken: Math.round((Date.now() - this.startedAt) / 1000)
                })
            })
            .then(res => res.json().then(data => ({ ok: res.ok, data })))
            .then(({ ok, data }) => {
                if (ok && data.success) {
                    this.score = data.score;
                    this.correctCount = data.correct_count;
                    this.redirect = data.redirect;
                } else {
                    this.error = (data && data.message) || 'Could not save this round. Please play again.';
                }
            })
            .catch(() => {
                this.error = 'Could not save this round. Please play again.';
            });
        },
        submitMemoryScore() {
            window.location.href = this.redirect || '{{ route("games.vibe", $partner->id) }}';
        }
    }
}
</script>
@endsection
