@extends('layouts.member')

@section('title', '🎯 Live Game Room — Koodal Sync')

@section('body')
<style>
.visible-whatsapp-scroll {
    overflow-y: scroll !important;
    scrollbar-width: thin !important;
    scrollbar-color: #075E54 #CBD5E1 !important;
}
.visible-whatsapp-scroll::-webkit-scrollbar {
    width: 10px !important;
    display: block !important;
}
.visible-whatsapp-scroll::-webkit-scrollbar-track {
    background: #CBD5E1 !important;
    border-radius: 9999px !important;
}
.visible-whatsapp-scroll::-webkit-scrollbar-thumb {
    background-color: #075E54 !important;
    border-radius: 9999px !important;
    border: 2px solid #CBD5E1 !important;
}
.visible-whatsapp-scroll::-webkit-scrollbar-thumb:hover {
    background-color: #128C7E !important;
}
</style>

<div class="min-h-screen py-6 px-4 sm:px-6 lg:px-8" style="background: linear-gradient(135deg, #FFF5F8 0%, #FFF0F5 100%)" x-data="liveGameRoom()">
    <div class="mx-auto max-w-7xl">

        {{-- Top Navigation & Presence Header --}}
        <div class="flex flex-col sm:flex-row items-center justify-between gap-4 mb-6 p-4 sm:p-5 rounded-3xl shadow-md"
             style="background-color: #ffffff; border: 2px solid #FBCFE8;">
            
            <a href="{{ route('games.hub', $partner->id) }}" class="inline-flex items-center gap-1.5 text-sm font-extrabold hover:underline" style="color: #D6246E">
                ← Exit Room
            </a>

            <div class="flex items-center gap-2">
                <span class="rounded-full px-4 py-1.5 text-xs font-black text-white shadow-sm" style="background: linear-gradient(135deg, #E0245E, #B81B5C)">
                    🎯 KOODAL SYNC LIVE ROOM
                </span>
            </div>

            {{-- Sound Mute/Unmute Toggle Button --}}
            <button type="button" @click="toggleSound()"
                    :title="soundMuted ? 'Unmute Sound' : 'Mute Sound'"
                    class="flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-bold transition shadow-sm"
                    :style="soundMuted ? 'background-color: #F3F4F6; color: #6B7280; border: 1.5px solid #D1D5DB;' : 'background-color: #ECFDF5; color: #047857; border: 1.5px solid #A7F3D0;'">
                <span x-text="soundMuted ? '🔇 Off' : '🔊 On'"></span>
            </button>

            {{-- Partner Presence Status --}}
            <div class="flex items-center gap-2 px-3.5 py-1.5 rounded-full text-xs font-bold"
                 style="background-color: #FFF0F5; border: 1.5px solid #FBCFE8;">
                <span class="relative flex h-2.5 w-2.5">
                    <span x-show="partnerPresence.is_online" class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                    <span class="relative inline-flex rounded-full h-2.5 w-2.5" :class="partnerPresence.is_online ? 'bg-emerald-500' : 'bg-gray-400'"></span>
                </span>
                <span class="text-gray-900 font-extrabold" x-text="partnerName"></span>
                <span class="text-[11px] font-semibold text-gray-600" x-text="`(${partnerPresence.label})`"></span>
            </div>
        </div>

        {{-- Main Centered Game Area --}}
        <div class="max-w-3xl mx-auto">

            {{-- ───────────── GAME AREA ───────────── --}}
            <div class="space-y-6">

                {{-- LOBBY SCREEN (Waiting for Ready) --}}
                <div x-show="status === 'lobby'" class="overflow-hidden rounded-3xl p-6 sm:p-8 text-center shadow-xl space-y-6"
                     style="background-color: #ffffff; border: 2px solid #FBCFE8;">
                    <div>
                        <span class="inline-block rounded-full px-3.5 py-1 text-xs font-black uppercase tracking-wider mb-2 border"
                              style="background-color: #FFF0F5; color: #D6246E; border-color: #FBCFE8;">
                            Lobby · Waiting for Players
                        </span>
                        <h2 class="text-2xl sm:text-3xl font-black text-gray-900">Get Ready to Play Live!</h2>
                        <p class="text-xs sm:text-sm text-gray-600 font-bold mt-1">
                            Both players must click "I'm Ready" to start the 3-2-1 countdown.
                        </p>
                    </div>

                    {{-- Side-by-side Player Ready Cards --}}
                    <div class="grid grid-cols-2 gap-4 max-w-md mx-auto pt-2">
                        {{-- User A (You) --}}
                        <div class="rounded-2xl p-4 border-2 text-center space-y-2 transition-all shadow-sm"
                             :style="myReady ? 'background-color: #ECFDF5; border-color: #10B981;' : 'background-color: #FFF0F5; border-color: #FBCFE8;'">
                            <div class="text-3xl">👤</div>
                            <div class="font-extrabold text-xs text-gray-900">You</div>
                            <div class="text-xs font-black" :class="myReady ? 'text-emerald-700' : 'text-amber-700'"
                                 x-text="myReady ? '✓ Ready' : 'Waiting...'"></div>
                        </div>

                        {{-- User B (Partner) --}}
                        <div class="rounded-2xl p-4 border-2 text-center space-y-2 transition-all shadow-sm"
                             :style="partnerReady ? 'background-color: #ECFDF5; border-color: #10B981;' : 'background-color: #FFF0F5; border-color: #FBCFE8;'">
                            <div class="text-3xl">🌸</div>
                            <div class="font-extrabold text-xs text-gray-900" x-text="partnerName"></div>
                            <div class="text-xs font-black" :class="partnerReady ? 'text-emerald-700' : 'text-amber-700'"
                                 x-text="partnerReady ? '✓ Ready' : 'Waiting...'"></div>
                        </div>
                    </div>

                    {{-- 3-2-1 Countdown Animation --}}
                    <div x-show="countdown > 0" class="py-4">
                        <span class="text-6xl font-black text-pink-600 animate-bounce block" x-text="countdown"></span>
                        <span class="text-xs font-black uppercase text-gray-600 tracking-wider">Game Starting...</span>
                    </div>

                    {{-- Ready Toggle Button --}}
                    <button type="button" @click="toggleReady()"
                            class="w-full max-w-sm rounded-2xl py-4 font-black text-white text-base shadow-xl transition-all hover:scale-105 active:scale-95 mx-auto block"
                            :style="(myReady && partnerReady)
                                ? 'background: linear-gradient(135deg, #059669, #10B981); color: #ffffff;'
                                : (myReady
                                    ? 'background-color: #374151; color: #ffffff;'
                                    : 'background: linear-gradient(135deg, #E0245E, #B81B5C); color: #ffffff;')">
                        <span x-text="(myReady && partnerReady) ? '🚀 Both Ready! Starting Game...' : (myReady ? 'Cancel Ready' : '✓ I\'m Ready!')"></span>
                    </button>
                </div>

                {{-- PLAYING SCREEN (Synchronized Live Questions) --}}
                <div x-show="status === 'playing'" class="overflow-hidden rounded-3xl p-6 sm:p-8 shadow-xl space-y-6"
                     style="background-color: #ffffff; border: 2px solid #FBCFE8;">

                    {{-- Progress Bar (Timer Removed) --}}
                    <div class="flex items-center justify-between border-b border-gray-100 pb-4">
                        <span class="text-xs font-black uppercase tracking-wider" style="color: #D6246E">
                            Question <span x-text="currentQuestionIndex + 1"></span> of <span x-text="totalQuestions"></span>
                        </span>

                        <span class="inline-block rounded-full px-3 py-1 text-[11px] font-extrabold uppercase tracking-wider"
                              style="background-color: #FFF0F5; color: #D6246E; border: 1px solid #FBCFE8;">
                            Koodal Sync Live 1v1
                        </span>
                    </div>

                    {{-- Question Text --}}
                    <div class="text-center space-y-2 py-2">
                        <span class="inline-block rounded-full px-3 py-1 text-[10px] font-black uppercase tracking-wider"
                              style="background-color: #FFF0F5; color: #D6246E; border: 1px solid #FBCFE8;">
                            Category: <span x-text="currentQuestion.category || 'General'"></span>
                        </span>
                        <h3 class="text-xl sm:text-2xl font-black text-gray-900" x-text="currentQuestion.question_text_en"></h3>
                        <p class="text-xs font-bold" style="color: #D6246E" x-show="currentQuestion.question_text_ta" x-text="currentQuestion.question_text_ta"></p>
                    </div>

                    {{-- Live Status Indicator --}}
                    <div class="rounded-2xl p-3.5 text-xs font-bold text-center transition-all flex items-center justify-center gap-2"
                         :style="bothAnswered ? 'background-color: #ECFDF5; border: 1.5px solid #A7F3D0; color: #065F46;' : (answerLockCountdown > 0 ? 'background-color: #FEF3C7; border: 1.5px solid #F59E0B; color: #92400E;' : 'background-color: #FFF0F5; border: 1.5px solid #FBCFE8; color: #9D174D;')">
                        <span x-show="!bothAnswered && answerLockCountdown === 0" class="animate-pulse text-base">⏳</span>
                        <span x-show="!bothAnswered && answerLockCountdown > 0" class="animate-spin text-base">⏱️</span>
                        <span x-show="bothAnswered" class="text-base">🎉</span>
                        <span class="font-extrabold" x-text="bothAnswered ? 'Both answered! Moving to next question...' : (answerLockCountdown > 0 ? `Locking your answer in ${answerLockCountdown}s... Tap another option to change!` : (myAnswered ? `Answer locked ✅ · Waiting for ${partnerName}...` : (partnerAnswered ? `${partnerName} has answered ✅ · Choose your answer below!` : `${partnerName} is starting to answer... Select your answer!`)))"></span>
                    </div>

                    {{-- 5-Second Change Timer Progress Bar --}}
                    <div x-show="answerLockCountdown > 0 && !myAnswered" x-transition class="rounded-2xl px-4 py-2.5 bg-amber-50 border border-amber-200 flex items-center justify-between gap-3">
                        <div class="flex-1">
                            <div class="flex items-center justify-between text-[11px] font-extrabold text-amber-800 mb-1">
                                <span>🔄 You have <b x-text="answerLockCountdown"></b>s to change your option</span>
                                <span x-text="`${answerLockCountdown}s`"></span>
                            </div>
                            <div class="w-full h-1.5 bg-amber-200 rounded-full overflow-hidden">
                                <div class="h-full bg-amber-500 transition-all duration-1000" :style="`width: ${(answerLockCountdown / 5) * 100}%`"></div>
                            </div>
                        </div>
                    </div>

                    {{-- Options List --}}
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-2">
                        <template x-for="(opt, idx) in (currentQuestion.options || [])" :key="opt.key || idx">
                            <button type="button"
                                     @click="selectOption(opt.key)"
                                     :disabled="myAnswered"
                                     :style="selectedOption === opt.key ? 'background-color: #FFF0F5; border: 2px solid #E0245E;' : 'background-color: #FFFFFF; border: 2px solid #E5E7EB;'"
                                     class="rounded-2xl p-4 text-left border-2 font-bold transition-all flex items-center justify-between text-xs disabled:cursor-not-allowed shadow-sm hover:border-pink-400 cursor-pointer">
                                <div>
                                    <span class="block text-sm font-extrabold text-gray-900" x-text="opt.label_en"></span>
                                    <span class="block text-[11px] font-semibold text-gray-600 opacity-90" x-show="opt.label_ta" x-text="opt.label_ta"></span>
                                </div>
                                <span class="text-base font-black" style="color: #E0245E" x-show="selectedOption === opt.key" x-text="answerLockCountdown > 0 ? `⏱️ ${answerLockCountdown}s` : '✓'"></span>
                            </button>
                        </template>
                    </div>

                    {{-- BOTH ANSWERED NOTICE (Auto-advancing to next question) --}}
                    <div x-show="bothAnswered" class="rounded-3xl p-5 border-2 text-center space-y-2 animate-fade-in"
                         style="background-color: #ECFDF5; border-color: #A7F3D0;">
                        <div class="text-3xl animate-bounce">🎉</div>
                        <h4 class="text-lg font-black text-emerald-800">Both Answered! ✅</h4>
                        <p class="text-xs font-bold text-gray-600">Moving to next question...</p>
                    </div>

                </div>

                {{-- COMPLETED SCREEN --}}
                <div x-show="status === 'completed'" class="overflow-hidden rounded-3xl p-6 sm:p-8 text-center shadow-2xl space-y-6"
                     style="background-color: #ffffff; border: 2px solid #FBCFE8;">
                    <div class="mx-auto flex h-20 w-20 items-center justify-center rounded-full text-4xl shadow-sm"
                         style="background-color: #FFF0F5; border: 2px solid #FBCFE8; color: #D6246E;">
                        🎯
                    </div>

                    <div>
                        <h2 class="text-2xl sm:text-3xl font-black text-gray-900">Live Game Completed!</h2>
                        <p class="text-sm font-bold text-gray-600 mt-1">
                            Great sync with <span x-text="partnerName"></span>!
                        </p>
                    </div>

                    <div class="rounded-2xl p-6 border-2 text-center space-y-2" style="background: linear-gradient(135deg, #FFF0F5, #FFF5FB); border-color: #FBCFE8;">
                        <span class="block text-xs font-black uppercase text-gray-500 tracking-wider">Final Sync Score</span>
                        <span class="text-5xl font-black" style="color: #D6246E" x-text="`${myScore} pts`"></span>
                        <span class="block text-xs font-bold text-gray-500">out of 100 points (10 pts per matching answer)</span>
                    </div>

                    {{-- All 10 Questions Detailed Review --}}
                    <div class="text-left space-y-3 pt-2">
                        <div class="flex items-center justify-between border-b border-gray-100 pb-2">
                            <span class="text-xs font-black uppercase text-gray-600 tracking-wider">Total Questions Review (10 Questions)</span>
                            <span class="text-[11px] font-extrabold text-pink-700">Same: +10 pts · Different: 0 pts</span>
                        </div>

                        <div class="space-y-3 max-h-[360px] overflow-y-auto pr-1">
                            <template x-for="(q, qIdx) in questionsReview" :key="qIdx">
                                <div class="p-3.5 rounded-2xl border text-xs space-y-2 shadow-sm"
                                     :style="q.is_match ? 'background-color: #F0FDF4; border-color: #BBF7D0;' : 'background-color: #FFFBEB; border-color: #FDE68A;'">
                                    <div class="flex items-start justify-between gap-2">
                                        <div class="min-w-0">
                                            <span class="font-extrabold text-gray-900 block" x-text="`#${q.index}. ${q.question_en}`"></span>
                                            <span x-show="q.question_ta" class="text-[11px] text-gray-500 font-semibold block" x-text="q.question_ta"></span>
                                        </div>
                                        <span class="px-2.5 py-1 rounded-full text-[10px] font-black whitespace-nowrap flex-shrink-0"
                                              :class="q.is_match ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800'"
                                              x-text="q.is_match ? '+10 pts Matched 🎯' : '0 pts Different 🤝'"></span>
                                    </div>
                                    <div class="grid grid-cols-2 gap-2 pt-1 font-semibold text-[11px]">
                                        <div class="p-2 rounded-xl bg-white border border-gray-200">
                                            <span class="text-gray-400 block text-[9px] uppercase font-bold">You answered</span>
                                            <span class="text-gray-900 font-bold block" x-text="q.my_label"></span>
                                        </div>
                                        <div class="p-2 rounded-xl bg-white border border-gray-200">
                                            <span class="text-gray-400 block text-[9px] uppercase font-bold" x-text="partnerName"></span>
                                            <span class="text-gray-900 font-bold block" x-text="q.partner_label"></span>
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>

                    {{-- Action Buttons: Next Game, Game Hub, Vibe Meter --}}
                    <div class="flex flex-col sm:flex-row gap-3 pt-3">
                        <button type="button" @click="replayGame()" :disabled="sendingReplayInvite"
                                class="flex-1 rounded-xl py-3 px-3 text-xs font-extrabold text-white shadow-lg transition-transform hover:scale-105 cursor-pointer"
                                style="background: linear-gradient(135deg, #10B981, #059669)">
                            <span x-text="sendingReplayInvite ? '⏳ Replay invite sent...' : '🔄 Replay Game'"></span>
                        </button>
                        <button type="button" @click="playNextGame('find_it')" :disabled="sendingNextInvite"
                                class="flex-1 rounded-xl py-3 px-3 text-xs font-extrabold text-white shadow-lg transition-transform hover:scale-105 cursor-pointer"
                                style="background: linear-gradient(135deg, #0891B2, #0E7490)">
                            <span x-text="sendingNextInvite ? '⏳ Sending Invite & Opening...' : '🎮 Next Game (Find It) →'"></span>
                        </button>
                        <a href="{{ route('games.hub', $partner->id) }}"
                           class="flex-1 rounded-xl border-2 border-gray-200 py-3 px-3 text-xs font-extrabold text-gray-700 hover:bg-gray-50 transition-colors">
                            🏠 Game Hub
                        </a>
                        <a href="{{ route('games.vibe', $partner->id) }}"
                           class="flex-1 rounded-xl py-3 px-3 text-xs font-extrabold text-white shadow-lg transition-transform hover:scale-105"
                           style="background: linear-gradient(135deg, #E0245E, #B81B5C)">
                            💙 View Vibe Meter →
                        </a>
                    </div>
                </div>

            </div>

        </div>

    </div>

    {{-- Floating Game Chat Icon (Default reactions + Paid custom chat) --}}
    <x-floating-chat />
</div>

<script>
function liveGameRoom() {
    return {
        userId: {{ auth()->id() }},
        partnerId: {{ $partner->id }},
        isUserA: {{ auth()->id() === $room->user_a_id ? 'true' : 'false' }},
        partnerName: '{{ addslashes($partner->name) }}',
        roomCode: '{{ $room->room_code }}',
        status: '{{ $room->status }}',
        questions: @json($questions),
        currentQuestionIndex: {{ $room->current_question_index }},
        totalQuestions: {{ count($questions) }},
        countdown: 0,
        myReady: false,
        partnerReady: false,
        myAnswered: false,
        partnerAnswered: false,
        bothAnswered: false,
        advancingQuestion: false,
        selectedOption: null,
        answerLockCountdown: 0,
        answerLockTimer: null,
        userAAnswers: {},
        userBAnswers: {},
        myScore: 0,
        partnerPresence: @json($partnerPresence),
        messages: [],
        chatInput: '',
        questionsReview: [],
        sendingNextInvite: false,
        sendingReplayInvite: false,
        soundMuted: (function() {
            try { return localStorage.getItem('koodal_game_sound_muted') === 'true'; } catch(e) { return false; }
        })(),
        toggleSound() {
            if (window.KoodalGameAudio && typeof window.KoodalGameAudio.toggleMute === 'function') {
                this.soundMuted = window.KoodalGameAudio.toggleMute();
            } else {
                this.soundMuted = !this.soundMuted;
                try { localStorage.setItem('koodal_game_sound_muted', this.soundMuted ? 'true' : 'false'); } catch(e){}
            }
        },

        get currentQuestion() {
            return this.questions[this.currentQuestionIndex] || {};
        },

        init() {
            this.pollState();
            setInterval(() => this.pollState(), 2000);
            window.addEventListener('koodal-sound-mute-changed', (e) => {
                if (e.detail && typeof e.detail.muted !== 'undefined') {
                    this.soundMuted = e.detail.muted;
                }
            });
            window.addEventListener('koodal-quick-chat-send', (e) => {
                if (e.detail && e.detail.message && this.roomCode) {
                    fetch(`/games/room/${this.roomCode}/messages`, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                        body: JSON.stringify({ message: e.detail.message, type: e.detail.type || 'quick_reply' })
                    }).then(() => {
                        this.pollState();
                        setTimeout(() => this.scrollChatToBottom(), 100);
                    });
                }
            });
        },

        pollState() {
            fetch(`/games/room/${this.roomCode}/poll`)
                .then(r => r.json())
                .then(d => {
                    const prevStatus = this.status;
                    const prevQuestionIndex = this.currentQuestionIndex;
                    this.status = d.status;
                    this.currentQuestionIndex = d.current_question_index;
                    if (prevQuestionIndex !== d.current_question_index) {
                        if (this.answerLockTimer) {
                            clearInterval(this.answerLockTimer);
                            this.answerLockTimer = null;
                        }
                        this.answerLockCountdown = 0;
                        this.selectedOption = null;
                    }
                    this.myReady = d.my_ready;
                    this.partnerReady = d.partner_ready;
                    if (this.answerLockCountdown === 0) {
                        this.myAnswered = d.my_answered;
                    }
                    this.partnerAnswered = d.partner_answered;
                    this.bothAnswered = d.my_answered && d.partner_answered;
                    this.userAAnswers = d.user_a_answers;
                    this.userBAnswers = d.user_b_answers;
                    this.partnerPresence = d.partner_presence;
                    this.messages = d.messages || [];
                    if (window.syncKoodalChatMessages) {
                        window.syncKoodalChatMessages(this.messages, this.userId, this.partnerName);
                    }
                    this.myScore = this.isUserA ? d.user_a_score : d.user_b_score;
                    if (d.questions_review && d.questions_review.length > 0) {
                        this.questionsReview = d.questions_review;
                    }

                    // Play final clap & fanfare when game transitions to completed
                    if (this.status === 'completed' && prevStatus !== 'completed') {
                        if (window.KoodalGameAudio) {
                            if (typeof window.KoodalGameAudio.playWin === 'function') {
                                window.KoodalGameAudio.playWin();
                            } else {
                                window.KoodalGameAudio.playFinalClapAndFanfare();
                            }
                        }
                    }

                    // Auto-advance to next question when both have answered
                    if (this.bothAnswered && this.status === 'playing' && !this.advancingQuestion) {
                        this.advancingQuestion = true;
                        if (window.KoodalGameAudio) {
                            const curQ = this.currentQuestion;
                            const ansA = this.userAAnswers && curQ ? this.userAAnswers[curQ.id] : null;
                            const ansB = this.userBAnswers && curQ ? this.userBAnswers[curQ.id] : null;
                            if (ansA && ansB && ansA === ansB) {
                                window.KoodalGameAudio.playMatchSuccess();
                            } else if (typeof window.KoodalGameAudio.playWrong === 'function') {
                                window.KoodalGameAudio.playWrong();
                            } else {
                                window.KoodalGameAudio.playMatchSuccess();
                            }
                        }
                        setTimeout(() => {
                            this.nextQuestion();
                        }, 1300);
                    }

                    // If both players became ready and game is starting/playing
                    if (d.my_ready && d.partner_ready && prevStatus === 'lobby' && this.countdown === 0) {
                        this.countdown = 3;
                        if (window.KoodalGameAudio) window.KoodalGameAudio.playCountdown(3);
                        const cTimer = setInterval(() => {
                            if (this.countdown > 1) {
                                this.countdown--;
                                if (window.KoodalGameAudio) window.KoodalGameAudio.playCountdown(this.countdown);
                            } else {
                                clearInterval(cTimer);
                                this.status = 'playing';
                                this.countdown = 0;
                                if (window.KoodalGameAudio) {
                                    if (typeof window.KoodalGameAudio.playStart === 'function') {
                                        window.KoodalGameAudio.playStart();
                                    } else {
                                        window.KoodalGameAudio.playCountdown('go');
                                    }
                                }
                            }
                        }, 1000);
                    }
                })
                .catch(() => {});
        },

        scrollChatToBottom() {},

        toggleReady() {
            if (window.KoodalGameAudio) window.KoodalGameAudio.playSelect();
            fetch(`/games/room/${this.roomCode}/ready`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
            })
            .then(r => r.json())
            .then(d => {
                this.myReady = this.isUserA ? d.user_a_ready : d.user_b_ready;
                this.partnerReady = this.isUserA ? d.user_b_ready : d.user_a_ready;
                if (d.user_a_ready && d.user_b_ready && this.countdown === 0) {
                    this.countdown = 3;
                    if (window.KoodalGameAudio) window.KoodalGameAudio.playCountdown(3);
                    const cTimer = setInterval(() => {
                        if (this.countdown > 1) {
                            this.countdown--;
                            if (window.KoodalGameAudio) window.KoodalGameAudio.playCountdown(this.countdown);
                        } else {
                            clearInterval(cTimer);
                            this.status = 'playing';
                            this.countdown = 0;
                            if (window.KoodalGameAudio) window.KoodalGameAudio.playCountdown('go');
                        }
                    }, 1000);
                }
            });
        },

        selectOption(optKey) {
            if (this.myAnswered) return;
            this.selectedOption = optKey;
            if (window.KoodalGameAudio) {
                window.KoodalGameAudio.playSelect();
            }

            // Start 5-second timer if not already running; if already running, keep counting down and use the newly selected option
            if (!this.answerLockTimer) {
                this.answerLockCountdown = 5;
                if (window.KoodalGameAudio) window.KoodalGameAudio.playCountdown(5);
                this.answerLockTimer = setInterval(() => {
                    if (this.answerLockCountdown > 1) {
                        this.answerLockCountdown--;
                        if (window.KoodalGameAudio) window.KoodalGameAudio.playCountdown(this.answerLockCountdown);
                    } else {
                        clearInterval(this.answerLockTimer);
                        this.answerLockTimer = null;
                        this.answerLockCountdown = 0;
                        if (window.KoodalGameAudio) window.KoodalGameAudio.playCountdown('go');
                        this.commitSelectedAnswer();
                    }
                }, 1000);
            }
        },

        commitSelectedAnswer() {
            if (this.myAnswered || !this.selectedOption) return;
            const optKey = this.selectedOption;
            this.myAnswered = true;

            fetch(`/games/room/${this.roomCode}/answer`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                body: JSON.stringify({
                    question_id: this.currentQuestion.id,
                    selected_option: optKey
                })
            })
            .then(r => r.json())
            .then(d => {
                this.bothAnswered = d.both_answered;
                if (this.bothAnswered && !this.advancingQuestion) {
                    this.advancingQuestion = true;
                    setTimeout(() => {
                        this.nextQuestion();
                    }, 1300);
                }
            });
        },

        nextQuestion() {
            if (this.answerLockTimer) {
                clearInterval(this.answerLockTimer);
                this.answerLockTimer = null;
            }
            this.answerLockCountdown = 0;

            fetch(`/games/room/${this.roomCode}/next-question`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                body: JSON.stringify({
                    from_index: this.currentQuestionIndex
                })
            })
            .then(r => r.json())
            .then(d => {
                this.status = d.status;
                this.currentQuestionIndex = d.current_question_index;
                this.myAnswered = false;
                this.bothAnswered = false;
                this.selectedOption = null;
                this.advancingQuestion = false;
            })
            .catch(() => {
                this.advancingQuestion = false;
            });
        },

        getMyAnswerOption() {
            if (this.selectedOption) return this.selectedOption;
            const myAnswers = this.isUserA ? this.userAAnswers : this.userBAnswers;
            const myAnsObj = myAnswers[this.currentQuestionIndex];
            return myAnsObj ? myAnsObj.option : null;
        },

        getPartnerAnswerOption() {
            const partnerAnswers = this.isUserA ? this.userBAnswers : this.userAAnswers;
            const partnerAnsObj = partnerAnswers[this.currentQuestionIndex];
            return partnerAnsObj ? partnerAnsObj.option : null;
        },

        getMyOptionLabel() {
            const myOptKey = this.getMyAnswerOption();
            const opt = (this.currentQuestion.options || []).find(o => o.key === myOptKey);
            return opt ? opt.label_en : '';
        },

        getPartnerOptionLabel() {
            const pOptKey = this.getPartnerAnswerOption();
            const opt = (this.currentQuestion.options || []).find(o => o.key === pOptKey);
            return opt ? opt.label_en : '';
        },

        sendMessage() {
            if (!this.chatInput.trim() || !this.roomCode) return;
            const text = this.chatInput;
            this.chatInput = '';

            if (window.triggerKoodalCelebration) {
                window.triggerKoodalCelebration(text);
            }
            if (window.pushKoodalChatStream) {
                window.pushKoodalChatStream(text, true);
            }

            fetch(`/games/room/${this.roomCode}/messages`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                body: JSON.stringify({ message: text, type: 'text' })
            }).then(() => {
                this.pollState();
                setTimeout(() => this.scrollChatToBottom(), 100);
            });
        },

        sendQuickReply(text) {
            if (!this.roomCode) return;
            if (window.triggerKoodalCelebration) {
                window.triggerKoodalCelebration(text);
            }
            if (window.pushKoodalChatStream) {
                window.pushKoodalChatStream(text, true);
            }
            fetch(`/games/room/${this.roomCode}/messages`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                body: JSON.stringify({ message: text, type: 'quick_reply' })
            }).then(() => {
                this.pollState();
                setTimeout(() => this.scrollChatToBottom(), 100);
            });
        },

        playNextGame(nextGameType = 'find_it') {
            if (this.sendingNextInvite) return;
            this.sendingNextInvite = true;

            fetch(`/games/${this.partnerId}/invite`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({ game_type: nextGameType, force_fresh: true })
            })
            .then(r => r.json().catch(() => null))
            .then(d => {
                if (d && d.redirect_url) {
                    window.location.href = d.redirect_url;
                } else {
                    window.location.href = '{{ route("games.find_it", $partner->id) }}';
                }
            })
            .catch(() => {
                window.location.href = '{{ route("games.find_it", $partner->id) }}';
            });
        },

        replayGame() {
            if (this.sendingReplayInvite) return;
            this.sendingReplayInvite = true;

            fetch(`/games/${this.partnerId}/invite`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({ game_type: 'sync', force_fresh: true })
            })
            .then(r => r.json().catch(() => null))
            .then(d => {
                if (d && d.redirect_url) {
                    window.location.href = d.redirect_url;
                } else if (d && d.room_code) {
                    window.location.href = `/games/room/${d.room_code}`;
                }
            })
            .catch(() => {
                this.sendingReplayInvite = false;
            });
        }
    }
}
</script>
@endsection
