@extends('layouts.member')

@section('title', '🎯 Koodal Sync — Compatibility Test')

@section('body')
<style>
[x-cloak] { display: none !important; }

/* ───────────── Design tokens ───────────── */
.ks-root {
    --ks-rose: #E0245E;
    --ks-rose-deep: #B81B5C;
    --ks-rose-ink: #D6246E;
    --ks-blush: #FFF0F5;
    --ks-blush-line: #FBCFE8;
    --ks-wa-dark: #075E54;
    --ks-wa-mid: #128C7E;
    --ks-wa-green: #25D366;
    position: relative;
    overflow: hidden;
    background:
        radial-gradient(circle at 12% 8%, rgba(251, 207, 232, .55) 0, transparent 38%),
        radial-gradient(circle at 92% 90%, rgba(224, 36, 94, .10) 0, transparent 40%),
        linear-gradient(135deg, #FFF5F8 0%, #FFF0F5 100%);
    padding-bottom: max(1.5rem, env(safe-area-inset-bottom));
}

/* ───────────── Keyframes ───────────── */
@keyframes ks-rise    { from { opacity: 0; transform: translateY(18px); } to { opacity: 1; transform: translateY(0); } }
@keyframes ks-pop     { 0% { opacity: 0; transform: scale(.92); } 60% { transform: scale(1.02); } 100% { opacity: 1; transform: scale(1); } }
@keyframes ks-swap    { from { opacity: 0; transform: translateX(22px); } to { opacity: 1; transform: translateX(0); } }
@keyframes ks-opt-in  { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
@keyframes ks-shimmer { 0% { background-position: -200% 0; } 100% { background-position: 200% 0; } }
@keyframes ks-float   { 0% { transform: translateY(0) rotate(0deg); opacity: 0; } 15% { opacity: .55; } 100% { transform: translateY(-110vh) rotate(25deg); opacity: 0; } }
@keyframes ks-ring    { 0% { box-shadow: 0 0 0 0 rgba(224, 36, 94, .35); } 100% { box-shadow: 0 0 0 14px rgba(224, 36, 94, 0); } }
@keyframes ks-check   { 0% { transform: scale(0) rotate(-40deg); } 70% { transform: scale(1.3) rotate(8deg); } 100% { transform: scale(1) rotate(0); } }
@keyframes ks-bubble  { from { opacity: 0; transform: translateY(8px) scale(.96); } to { opacity: 1; transform: translateY(0) scale(1); } }
@keyframes ks-bob     { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(-6px); } }
@keyframes ks-glow    { 0%, 100% { box-shadow: 0 6px 16px rgba(224, 36, 94, .30); } 50% { box-shadow: 0 8px 26px rgba(224, 36, 94, .55); } }

/* ───────────── Floating background hearts ───────────── */
.ks-float {
    position: absolute;
    bottom: -40px;
    font-size: 1.4rem;
    pointer-events: none;
    user-select: none;
    animation: ks-float linear infinite;
    z-index: 0;
}
.ks-float:nth-child(1) { left: 6%;  animation-duration: 16s; animation-delay: 0s; }
.ks-float:nth-child(2) { left: 22%; animation-duration: 21s; animation-delay: 4s;  font-size: 1rem; }
.ks-float:nth-child(3) { left: 45%; animation-duration: 18s; animation-delay: 8s; }
.ks-float:nth-child(4) { left: 63%; animation-duration: 24s; animation-delay: 2s;  font-size: 1.1rem; }
.ks-float:nth-child(5) { left: 80%; animation-duration: 19s; animation-delay: 6s; }
.ks-float:nth-child(6) { left: 93%; animation-duration: 23s; animation-delay: 11s; font-size: 1rem; }

.ks-content { position: relative; z-index: 1; }

/* ───────────── Entrance (single orchestrated moment) ───────────── */
.ks-topbar   { animation: ks-rise .55s cubic-bezier(.2, .8, .2, 1) both; }
.ks-qcard    { animation: ks-rise .6s cubic-bezier(.2, .8, .2, 1) .08s both; }
.ks-chat     { animation: ks-rise .6s cubic-bezier(.2, .8, .2, 1) .18s both; }
.ks-banner   { animation: ks-rise .5s ease .04s both; }
.ks-result   { animation: ks-pop .6s cubic-bezier(.2, .9, .3, 1.1) both; }

/* Question swap: retriggered from markup via x-effect (presentation only) */
.ks-swap { animation: ks-swap .38s cubic-bezier(.2, .8, .2, 1) both; }
.ks-swap .ks-opt { animation: ks-opt-in .4s ease both; }
.ks-swap .ks-opt:nth-child(2) { animation-delay: .05s; }
.ks-swap .ks-opt:nth-child(3) { animation-delay: .10s; }
.ks-swap .ks-opt:nth-child(4) { animation-delay: .15s; }
.ks-swap .ks-opt:nth-child(5) { animation-delay: .20s; }

/* ───────────── Top bar ───────────── */
.ks-topbar-inner { display: grid; grid-template-columns: 1fr; gap: .75rem; justify-items: center; }
@media (min-width: 640px) {
    .ks-topbar-inner { grid-template-columns: auto 1fr auto; justify-items: stretch; align-items: center; }
    .ks-topbar-mid   { justify-content: center; }
}
.ks-presence { max-width: 100%; }
.ks-presence span:nth-child(2) { max-width: 9rem; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }

/* Invite button glow when idle */
.ks-invite:not(:disabled) { animation: ks-glow 2.6s ease-in-out infinite; }

/* ───────────── Progress ───────────── */
.ks-progress-fill {
    background-image: linear-gradient(90deg, #E0245E, #FF6B9A, #B81B5C, #E0245E);
    background-size: 200% 100%;
    animation: ks-shimmer 3s linear infinite;
}

/* ───────────── Options ───────────── */
.ks-opt {
    position: relative;
    min-height: 56px;
    transition: transform .18s ease, box-shadow .18s ease, border-color .18s ease, background-color .18s ease;
    -webkit-tap-highlight-color: transparent;
}
@media (hover: hover) {
    .ks-opt:hover { transform: translateY(-2px); box-shadow: 0 10px 22px rgba(224, 36, 94, .14); }
}
.ks-opt:active { transform: scale(.985); }
.ks-opt:focus-visible,
.ks-btn:focus-visible,
.ks-chip:focus-visible { outline: 3px solid rgba(224, 36, 94, .45); outline-offset: 2px; }
.ks-opt-selected { animation: ks-ring .55s ease-out; }
.ks-check { display: inline-block; animation: ks-check .4s cubic-bezier(.2, .9, .3, 1.4) both; }

/* ───────────── Buttons ───────────── */
.ks-btn { transition: transform .18s ease, box-shadow .18s ease, opacity .18s ease; -webkit-tap-highlight-color: transparent; }
@media (hover: hover) {
    .ks-btn:not(:disabled):hover { transform: translateY(-2px); box-shadow: 0 10px 24px rgba(224, 36, 94, .28); }
}
.ks-btn:not(:disabled):active { transform: scale(.96); }
.ks-nav { display: flex; gap: .75rem; }
.ks-nav > button { min-height: 46px; }
@media (max-width: 479px) {
    .ks-nav > button { flex: 1 1 0%; padding-left: .75rem; padding-right: .75rem; }
}

/* ───────────── Result ───────────── */
.ks-trophy { display: inline-block; animation: ks-bob 2.2s ease-in-out infinite; }

/* ───────────── Chat panel ───────────── */
.ks-chat {
    background-color: #E5DDD5;
    border: 2px solid #075E54;
    display: flex;
    flex-direction: column;
    position: relative;
    overflow: hidden;
    border-radius: 1.5rem;
    box-shadow: 0 18px 40px rgba(7, 94, 84, .18);
    height: min(70vh, 560px);
    min-height: 420px;
}
@media (min-width: 1024px) {
    .ks-chat { position: sticky; top: 1.5rem; height: 560px; max-height: calc(100vh - 3rem); }
}

.ks-msg { animation: ks-bubble .28s ease-out both; }
.ks-msg-in  { transform-origin: top left; }
.ks-msg-out { transform-origin: top right; }

.visible-whatsapp-scroll {
    overflow-y: scroll !important;
    overscroll-behavior: contain;
    -webkit-overflow-scrolling: touch;
    scrollbar-width: thin !important;
    scrollbar-color: #075E54 #CBD5E1 !important;
}
.visible-whatsapp-scroll::-webkit-scrollbar { width: 10px !important; display: block !important; }
.visible-whatsapp-scroll::-webkit-scrollbar-track { background: #CBD5E1 !important; border-radius: 9999px !important; }
.visible-whatsapp-scroll::-webkit-scrollbar-thumb { background-color: #075E54 !important; border-radius: 9999px !important; border: 2px solid #CBD5E1 !important; }
.visible-whatsapp-scroll::-webkit-scrollbar-thumb:hover { background-color: #128C7E !important; }

.ks-scrollx { scrollbar-width: none; -webkit-overflow-scrolling: touch; }
.ks-scrollx::-webkit-scrollbar { display: none; }

.ks-chip { transition: transform .15s ease, background-color .15s ease; -webkit-tap-highlight-color: transparent; }
.ks-chip:active { transform: scale(.9); background-color: #DCF8C6 !important; }
@media (hover: hover) { .ks-chip:hover { transform: translateY(-2px); background-color: #F0FDF4 !important; } }

.ks-input { font-size: 16px; }
@media (min-width: 1024px) { .ks-input { font-size: 13px; } }
.ks-input:focus { box-shadow: 0 0 0 3px rgba(18, 140, 126, .30); border-color: #128C7E !important; }

.ks-jump { transition: transform .15s ease; }
.ks-jump:hover { transform: scale(1.12); }
.ks-jump:active { transform: scale(.9); }

/* ───────────── Reduced motion ───────────── */
@media (prefers-reduced-motion: reduce) {
    .ks-root *, .ks-root *::before, .ks-root *::after {
        animation-duration: .01ms !important;
        animation-iteration-count: 1 !important;
        transition-duration: .01ms !important;
    }
    .ks-float { display: none; }
}
</style>

<div class="ks-root min-h-screen py-4 sm:py-6 px-3 sm:px-6 lg:px-8" x-data="koodalSync()">

    {{-- Decorative floating hearts (aria-hidden, pointer-events none) --}}
    <div aria-hidden="true">
        <span class="ks-float">💗</span>
        <span class="ks-float">✨</span>
        <span class="ks-float">💕</span>
        <span class="ks-float">🎯</span>
        <span class="ks-float">💖</span>
        <span class="ks-float">✨</span>
    </div>

    <div class="ks-content mx-auto max-w-7xl">

        {{-- Top Navigation & Partner Header Bar --}}
        <div class="ks-topbar mb-4 sm:mb-6 p-3.5 sm:p-5 rounded-3xl shadow-md"
             style="background-color: rgba(255,255,255,.95); border: 2px solid #FBCFE8; backdrop-filter: blur(6px);">
            <div class="ks-topbar-inner">

                <a href="{{ route('games.hub', $partner->id) }}"
                   class="inline-flex items-center gap-1.5 text-sm font-extrabold hover:underline" style="color: #D6246E">
                    ← Exit Game
                </a>

                <div class="ks-topbar-mid flex flex-wrap items-center justify-center gap-2">
                    <span class="rounded-full px-4 py-1.5 text-xs font-black text-white shadow-sm" style="background: linear-gradient(135deg, #E0245E, #B81B5C)">
                        🎯 KOODAL SYNC
                    </span>
                    <span class="text-sm font-bold text-gray-800">with {{ $partner->name }}</span>
                </div>

                {{-- Partner Presence & Invite Live Button --}}
                <div class="flex flex-wrap items-center justify-center gap-2 sm:gap-3">
                    <div class="ks-presence flex items-center gap-2 px-3.5 py-1.5 rounded-full text-xs font-bold"
                         style="background-color: #FFF0F5; border: 1.5px solid #FBCFE8;">
                        <span class="relative flex h-2.5 w-2.5 flex-shrink-0">
                            <span x-show="partnerPresence.is_online" class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                            <span class="relative inline-flex rounded-full h-2.5 w-2.5" :class="partnerPresence.is_online ? 'bg-emerald-500' : 'bg-gray-400'"></span>
                        </span>
                        <span class="text-gray-900 font-extrabold" x-text="partnerName"></span>
                        <span class="text-[11px] font-semibold text-gray-600" x-text="`(${partnerPresence.label})`"></span>
                    </div>

                    {{-- Sound Mute/Unmute Toggle Button --}}
                    <button type="button" @click="toggleSound()"
                            :title="soundMuted ? 'Unmute Sound' : 'Mute Sound'"
                            class="ks-btn flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-bold transition shadow-sm"
                            :style="soundMuted ? 'background-color: #F3F4F6; color: #6B7280; border: 1.5px solid #D1D5DB;' : 'background-color: #ECFDF5; color: #047857; border: 1.5px solid #A7F3D0;'">
                        <span x-text="soundMuted ? '🔇 Off' : '🔊 On'"></span>
                    </button>

                    <button type="button" @click="sendLiveInvite()"
                            :disabled="invitePending"
                            class="ks-btn ks-invite rounded-xl px-4 py-2 font-extrabold text-white text-xs shadow-md disabled:opacity-75 disabled:cursor-not-allowed"
                            :style="!invitePending ? 'background: linear-gradient(135deg, #E0245E, #B81B5C);' : 'background-color: #D97706;'">
                        <span x-text="invitePending ? '⏳ Invite Sent' : '🚀 Invite Live'"></span>
                    </button>
                </div>
            </div>
        </div>

        {{-- Invite Feedback Notification Badge --}}
        <div x-show="inviteNotice" x-cloak
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 -translate-y-2 scale-95"
             x-transition:enter-end="opacity-100 translate-y-0 scale-100"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="mb-4 text-center">
            <span class="inline-block max-w-full rounded-full px-4 py-1.5 text-xs font-extrabold shadow-sm"
                  style="background-color: #FFF0F5; color: #D6246E; border: 1.5px solid #FBCFE8;"
                  x-text="inviteNotice"></span>
        </div>

        {{-- MAIN CONTAINER: Centered Compatibility Test Card --}}
        <div class="max-w-3xl mx-auto space-y-4 sm:space-y-6">

            {{-- ───────────── QUESTIONS & RESULTS ───────────── --}}
            <div class="space-y-4 sm:space-y-6 min-w-0">

                {{-- Progress Bar --}}
                <div x-show="!completed" class="rounded-full p-1" style="background-color: #FCE7F3; border: 1px solid #FBCFE8;">
                    <div class="ks-progress-fill h-3 rounded-full transition-all duration-700 ease-out"
                         :style="`width: ${(currentIndex + 1) / totalQuestions * 100}%`"></div>
                </div>

                {{-- Live Partner Answer Status Banner --}}
                <div x-show="!completed" class="ks-banner rounded-2xl p-3 sm:p-3.5 text-xs font-bold text-center transition-all duration-500 flex items-center justify-center gap-2"
                     :style="partnerAnswered || partnerAlreadyPlayed ? 'background-color: #ECFDF5; border: 1.5px solid #A7F3D0; color: #065F46;' : 'background-color: #FFF0F5; border: 1.5px solid #FBCFE8; color: #9D174D;'">
                    <span x-show="!partnerAnswered && !partnerAlreadyPlayed" class="animate-pulse text-base">💬</span>
                    <span x-show="partnerAnswered || partnerAlreadyPlayed" class="text-base ks-trophy">⭐</span>
                    <span class="font-extrabold leading-snug" x-text="partnerAnswered || partnerAlreadyPlayed ? `${partnerName} has answered! Choose your options to sync scores.` : `Choose your answer below · Use the floating Chat icon 💬 in the bottom corner for reactions!`"></span>
                </div>

                {{-- QUESTION CARD --}}
                <div x-show="!completed" class="ks-qcard overflow-hidden rounded-3xl p-4 sm:p-8 shadow-xl"
                     style="background-color: #ffffff; border: 2px solid #FBCFE8;">

                    <div class="flex flex-wrap items-center justify-between gap-2 text-xs font-bold uppercase tracking-wider mb-3">
                        <span style="color: #D6246E" class="font-black">Question <span x-text="currentIndex + 1"></span> of <span x-text="totalQuestions"></span></span>
                        <span class="rounded-full px-3 py-1 font-bold text-gray-700" style="background-color: #FFF0F5; border: 1px solid #FBCFE8;" x-text="questions[currentIndex]?.category || 'Compatibility'"></span>
                    </div>

                    <div>
                        <h2 class="text-lg sm:text-2xl font-extrabold text-gray-900 leading-snug" x-text="questions[currentIndex]?.question_text_en || ''"></h2>
                        <p x-show="questions[currentIndex]?.question_text_ta" class="text-sm font-bold mt-1" style="color: #D6246E" x-text="questions[currentIndex]?.question_text_ta || ''"></p>

                        {{-- 5-Second Change Timer Progress Bar --}}
                        <div x-show="answerLockCountdown > 0" x-transition class="mt-3 rounded-2xl px-4 py-2.5 bg-amber-50 border border-amber-200 flex items-center justify-between gap-3">
                            <div class="flex-1">
                                <div class="flex items-center justify-between text-[11px] font-extrabold text-amber-800 mb-1">
                                    <span>⏱️ Locking answer in <b x-text="answerLockCountdown"></b>s... Tap another option within 5s to change!</span>
                                    <span x-text="`${answerLockCountdown}s`"></span>
                                </div>
                                <div class="w-full h-1.5 bg-amber-200 rounded-full overflow-hidden">
                                    <div class="h-full bg-amber-500 transition-all duration-1000" :style="`width: ${(answerLockCountdown / 5) * 100}%`"></div>
                                </div>
                            </div>
                        </div>

                        {{-- Options --}}
                        <div class="space-y-2.5 sm:space-y-3 pt-4">
                            <template x-for="(opt, optIdx) in (questions[currentIndex]?.options || [])" :key="opt.key || optIdx">
                                <button type="button"
                                        @click="selectOption(opt.key)"
                                        :style="isOptionSelected(opt.key)
                                            ? 'background-color: #FFF0F5; border: 2px solid #E0245E;'
                                            : 'background-color: #FFFFFF; border: 2px solid #E5E7EB;'"
                                        :class="isOptionSelected(opt.key) ? 'ks-opt-selected' : ''"
                                        class="ks-opt w-full text-left rounded-2xl p-3 sm:p-4 flex items-center justify-between gap-3 group shadow-sm hover:border-pink-400 cursor-pointer">
                                    <div class="flex items-center gap-3 min-w-0">
                                        <span class="grid h-9 w-9 flex-shrink-0 place-items-center rounded-xl font-black text-xs transition-all duration-300"
                                              :style="isOptionSelected(opt.key)
                                                  ? 'background-color: #E0245E; color: #FFFFFF; transform: rotate(-6deg) scale(1.08);'
                                                  : 'background-color: #F3F4F6; color: #374151;'"
                                              x-text="opt.key"></span>
                                        <div class="min-w-0">
                                            <span class="block text-sm sm:text-base font-extrabold text-gray-900 break-words" x-text="opt.label_en"></span>
                                            <span x-show="opt.label_ta" class="block text-xs text-gray-600 font-semibold break-words" x-text="opt.label_ta"></span>
                                        </div>
                                    </div>
                                    <span x-show="isOptionSelected(opt.key)" class="ks-check text-sm sm:text-base font-black flex-shrink-0" style="color: #E0245E" x-text="answerLockCountdown > 0 ? `⏱️ ${answerLockCountdown}s` : '✓ Selected'"></span>
                                </button>
                            </template>
                        </div>
                    </div>

                    {{-- Navigation --}}
                    <div class="ks-nav mt-6 sm:mt-8 items-center justify-between border-t border-gray-200 pt-4 sm:pt-5">
                        <button type="button" @click="prevQuestion()" :disabled="currentIndex === 0"
                                class="ks-btn rounded-xl border-2 border-gray-300 px-5 py-2.5 text-sm font-bold text-gray-700 disabled:opacity-30 cursor-pointer">
                            ← Previous
                        </button>

                        <button type="button"
                                x-show="currentIndex < totalQuestions - 1"
                                @click="nextQuestion()"
                                :disabled="!hasCurrentAnswer()"
                                class="ks-btn rounded-xl px-6 py-2.5 text-sm font-extrabold text-white shadow-md disabled:opacity-40 cursor-pointer"
                                style="background: linear-gradient(135deg, #E0245E, #B81B5C)">
                            Next →
                        </button>

                        <button type="button"
                                x-show="currentIndex === totalQuestions - 1"
                                @click="submitGame()"
                                :disabled="!hasCurrentAnswer() || submitting"
                                class="ks-btn rounded-xl px-5 sm:px-7 py-2.5 text-sm font-extrabold text-white shadow-lg disabled:opacity-40 cursor-pointer"
                                style="background: linear-gradient(135deg, #E0245E, #74123C)">
                            <span x-show="!submitting">✓ Finish Koodal Sync</span>
                            <span x-show="submitting" class="animate-pulse">Saving...</span>
                        </button>
                    </div>
                </div>

                {{-- RESULT SCREEN --}}
                <div x-show="completed" class="space-y-5">
                    <div class="ks-result overflow-hidden rounded-3xl bg-white shadow-2xl border-2 border-pink-200">
                        <div class="px-6 sm:px-8 py-7 sm:py-8 text-center text-white"
                             style="background: linear-gradient(135deg, #E0245E 0%, #B81B5C 100%)">
                            <div class="ks-trophy text-5xl mb-2" x-text="result.waiting_for_partner ? '⏳' : '🎯'"></div>
                            <h2 class="text-xl sm:text-2xl font-black" x-text="result.waiting_for_partner ? `Waiting for ${partnerName}'s Answer!` : 'Koodal Sync Complete!'"></h2>
                            <p class="text-pink-100 text-sm mt-1 font-medium" x-text="result.waiting_for_partner ? 'Your 10 answers have been recorded safely' : 'Both players have completed the compatibility test!'"></p>
                        </div>

                        <div class="p-4 sm:p-8 space-y-4 sm:space-y-5">
                            {{-- When waiting for partner --}}
                            <template x-if="result.waiting_for_partner">
                                <div class="space-y-4">
                                    {{-- Final Score Placeholder: Waiting for Opposite Answer --}}
                                    <div class="text-center rounded-2xl p-4 sm:p-5" style="background: #FFFBEB; border: 2px solid #FDE68A">
                                        <div class="text-xs sm:text-sm font-bold text-amber-700 uppercase tracking-wider mb-1">Final Compatibility Score</div>
                                        <div class="text-2xl sm:text-3xl font-black text-amber-600 py-1">⏳ Waiting for Opposite Answer</div>
                                        <div class="text-xs font-bold text-amber-800 mt-1">
                                            Your 10 answers are saved! Final score will unlock as soon as <span x-text="partnerName"></span> answers.
                                        </div>
                                    </div>

                                    <div class="rounded-2xl p-5 sm:p-6 text-center space-y-3" style="background: #FFF0F5; border: 2px solid #FBCFE8">
                                        <div class="text-4xl">💌</div>
                                        <h3 class="text-lg font-black text-gray-900">Invite Sent to <span x-text="partnerName"></span> (Accept / Decline)</h3>
                                        <p class="text-xs sm:text-sm font-bold text-gray-600 max-w-md mx-auto leading-relaxed">
                                            We have sent an invitation link with <strong>Accept &amp; Decline</strong> options to <strong><span x-text="partnerName"></span></strong>:
                                            <span class="block mt-2 p-3 rounded-xl bg-white border border-pink-200 text-pink-700 font-extrabold text-xs sm:text-sm shadow-xs">
                                                "{{ auth()->user()->name }}: Already your game is played, waiting for your answer! Please play 🎯"
                                            </span>
                                        </p>
                                        <div class="pt-1 flex flex-wrap items-center justify-center gap-2">
                                            <button type="button" @click="resendOfflineInvite()"
                                                    class="inline-flex items-center gap-1.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 px-4 py-2 text-xs font-extrabold text-white shadow">
                                                <span>📤</span> <span x-text="offlineInviteText || 'Resend & Copy Invite Link'"></span>
                                            </button>
                                        </div>
                                        <p class="text-[11px] font-semibold text-gray-500">
                                            When <span x-text="partnerName"></span> clicks <strong>Accept</strong> on the invite, they will answer the same 10 questions and your compatibility score will be calculated!
                                        </p>
                                    </div>

                                    <div class="flex flex-col sm:flex-row gap-3 pt-2">
                                        <button type="button" @click="playNextGame('find_it')" :disabled="sendingNextInvite"
                                                class="ks-btn flex-1 text-center rounded-xl py-3 px-4 text-xs sm:text-sm font-extrabold text-white shadow-lg transition-transform hover:scale-105 cursor-pointer"
                                                style="background: linear-gradient(135deg, #0891B2, #0E7490)">
                                            <span x-text="sendingNextInvite ? '⏳ Sending Invite & Opening...' : '🎮 Next Game (Find It) →'"></span>
                                        </button>
                                        <a href="{{ route('games.hub', $partner->id) }}"
                                           class="ks-btn flex-1 text-center rounded-xl border-2 border-gray-200 px-5 py-3 text-xs sm:text-sm font-bold text-gray-700 hover:bg-gray-50">
                                            🏠 Games Hub
                                        </a>
                                        <a href="{{ route('games.vibe', $partner->id) }}"
                                           class="ks-btn flex-1 text-center rounded-xl px-6 py-3 text-xs sm:text-sm font-bold text-white shadow-lg"
                                           style="background: linear-gradient(135deg, #E0245E, #B81B5C)">
                                            💙 Vibe Meter →
                                        </a>
                                    </div>
                                </div>
                            </template>

                            {{-- When both have played --}}
                            <template x-if="!result.waiting_for_partner">
                                <div class="space-y-4">
                                    <div class="text-center rounded-2xl p-4 sm:p-5" style="background: #FFF0F5; border: 2px solid #FBCFE8">
                                        <div class="text-xs sm:text-sm font-bold text-gray-500 uppercase tracking-wider mb-1">Total Compatibility Score</div>
                                        <div class="text-5xl font-black" style="color: #D6246E" x-text="`${result.score} pts`"></div>
                                        <div class="text-sm font-semibold text-gray-500 mt-1">out of 100 points (10 pts per matching answer)</div>
                                        <div class="mt-3">
                                            <div class="w-full bg-pink-100 rounded-full h-3 overflow-hidden">
                                                <div class="ks-progress-fill h-3 rounded-full transition-all duration-1000 ease-out"
                                                     :style="`width: ${result.score}%`"></div>
                                            </div>
                                        </div>
                                    </div>

                                    {{-- 10 Questions Detailed Review --}}
                                    <div class="text-left space-y-3 pt-2" x-show="result.questions_review && result.questions_review.length > 0">
                                        <div class="flex items-center justify-between border-b border-gray-100 pb-2">
                                            <span class="text-xs font-black uppercase text-gray-600 tracking-wider">Total Questions Review (10 Questions)</span>
                                            <span class="text-[11px] font-extrabold text-pink-700">Same: +10 pts · Different: 0 pts</span>
                                        </div>

                                        <div class="space-y-3 max-h-[360px] overflow-y-auto pr-1">
                                            <template x-for="(q, qIdx) in result.questions_review" :key="qIdx">
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
                                                            <span class="text-gray-900 font-bold block" x-text="q.user_answer"></span>
                                                        </div>
                                                        <div class="p-2 rounded-xl bg-white border border-gray-200">
                                                            <span class="text-gray-400 block text-[9px] uppercase font-bold" x-text="partnerName"></span>
                                                            <span class="text-gray-900 font-bold block" x-text="q.partner_answer"></span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </template>
                                        </div>
                                    </div>

                                    {{-- Action Buttons: Replay Live, Next Game, Game Hub, Vibe Meter --}}
                                    <div class="flex flex-col sm:flex-row gap-3 pt-3">
                                        <button type="button" @click="replayLiveGame()" :disabled="sendingLiveInvite"
                                                class="ks-btn flex-1 text-center rounded-xl py-3 px-3 text-xs sm:text-sm font-extrabold text-white shadow-lg transition-transform hover:scale-105 cursor-pointer"
                                                style="background: linear-gradient(135deg, #10B981, #059669)">
                                            <span x-text="sendingLiveInvite ? '⏳ Sending Invite...' : '🔄 Replay Game Live'"></span>
                                        </button>
                                        <button type="button" @click="playNextGame('find_it')" :disabled="sendingNextInvite"
                                                class="ks-btn flex-1 text-center rounded-xl py-3 px-3 text-xs sm:text-sm font-extrabold text-white shadow-lg transition-transform hover:scale-105 cursor-pointer"
                                                style="background: linear-gradient(135deg, #0891B2, #0E7490)">
                                            <span x-text="sendingNextInvite ? '⏳ Sending Invite & Opening...' : '🎮 Next Game (Find It) →'"></span>
                                        </button>
                                        <a href="{{ route('games.hub', $partner->id) }}"
                                           class="ks-btn flex-1 text-center rounded-xl border-2 border-gray-200 px-5 py-3 text-xs sm:text-sm font-bold text-gray-700 hover:bg-gray-50">
                                            🏠 Games Hub
                                        </a>
                                        <a href="{{ route('games.vibe', $partner->id) }}"
                                           class="ks-btn flex-1 text-center rounded-xl px-6 py-3 text-xs sm:text-sm font-bold text-white shadow-lg transition-transform hover:scale-105"
                                           style="background: linear-gradient(135deg, #E0245E, #B81B5C)">
                                            💙 Vibe Meter →
                                        </a>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>

            </div>

        </div>

    </div>

    {{-- Floating Game Chat Icon (Default reactions + Paid custom chat) --}}
    <x-floating-chat />
</div>

<script>
function koodalSync() {
    return {
        userId: {{ auth()->id() }},
        partnerId: {{ $partner->id }},
        partnerName: '{{ addslashes($partner->name) }}',
        roomCode: '{{ $room->room_code }}',
        partnerAlreadyPlayed: {{ $partnerAlreadyPlayed ? 'true' : 'false' }},
        questions: @json($questions),
        currentIndex: 0,
        currentSelectedOption: null,
        selectedAnswers: {},
        lockedQuestions: {},
        answerLockCountdown: 0,
        answerLockTimer: null,
        submitting: false,
        completed: false,
        result: { score: 0, max_score: 100, score_pct: 0, matched_count: 0, total_questions: 10, vibe_percentage: 0, waiting_for_partner: false, questions_review: [], invite_id: null, invite_sent: false },
        partnerPresence: @json($partnerPresence),
        partnerAnswered: false,
        messages: [],
        chatInput: '',
        invitePending: false,
        inviteNotice: '',
        sendingNextInvite: false,
        sendingLiveInvite: false,
        offlineInviteText: '',
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

        get totalQuestions() { return this.questions ? this.questions.length : 0; },

        init() {
            this.syncCurrentSelection();
            this.pollState();
            setInterval(() => this.pollState(), 2000);

            // Game start sound
            setTimeout(() => {
                if (window.KoodalGameAudio && typeof window.KoodalGameAudio.playStart === 'function') {
                    window.KoodalGameAudio.playStart();
                }
            }, 300);

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
                    });
                }
            });
        },

        pollState() {
            fetch(`/games/room/${this.roomCode}/poll`)
                .then(r => r.json())
                .then(d => {
                    this.partnerPresence = d.partner_presence;
                    this.partnerAnswered = d.partner_answered;
                    this.messages = d.messages || [];
                    if (window.syncKoodalChatMessages) {
                        window.syncKoodalChatMessages(this.messages, this.userId, this.partnerName);
                    }
                })
                .catch(() => {});
        },

        scrollChatToBottom() {},

        syncCurrentSelection() {
            const q = this.questions[this.currentIndex];
            this.currentSelectedOption = q ? (this.selectedAnswers[q.id] || null) : null;
        },

        isOptionSelected(optKey) {
            if (this.currentSelectedOption === optKey) return true;
            const q = this.questions[this.currentIndex];
            return q ? this.selectedAnswers[q.id] === optKey : false;
        },

        hasCurrentAnswer() {
            if (this.currentSelectedOption) return true;
            const q = this.questions[this.currentIndex];
            return q ? Boolean(this.selectedAnswers[q.id]) : false;
        },

        selectOption(key) {
            const q = this.questions[this.currentIndex];
            if (!q) return;
            const qId = q.id;

            // Update both primitive state and answers map so Alpine updates UI immediately
            this.currentSelectedOption = key;
            this.selectedAnswers = Object.assign({}, this.selectedAnswers, { [qId]: key });
            if (window.KoodalGameAudio) {
                window.KoodalGameAudio.playSelect();
            }

            // Start 5-second timer if not already running for this question
            if (!this.answerLockTimer) {
                this.answerLockCountdown = 5;
                if (window.KoodalGameAudio) {
                    window.KoodalGameAudio.playCountdown(5);
                }
                this.answerLockTimer = setInterval(() => {
                    if (this.answerLockCountdown > 1) {
                        this.answerLockCountdown--;
                        if (window.KoodalGameAudio) {
                            window.KoodalGameAudio.playCountdown(this.answerLockCountdown);
                        }
                    } else {
                        clearInterval(this.answerLockTimer);
                        this.answerLockTimer = null;
                        this.answerLockCountdown = 0;
                        if (window.KoodalGameAudio) {
                            window.KoodalGameAudio.playCountdown('go');
                        }
                        this.lockedQuestions = Object.assign({}, this.lockedQuestions, { [qId]: true });
                        if (this.currentIndex < this.totalQuestions - 1) {
                            this.currentIndex++;
                            this.syncCurrentSelection();
                        }
                    }
                }, 1000);
            }
        },
        nextQuestion() {
            const q = this.questions[this.currentIndex];
            if (q && this.selectedAnswers[q.id]) {
                this.lockedQuestions = Object.assign({}, this.lockedQuestions, { [q.id]: true });
            }
            if (this.answerLockTimer) {
                clearInterval(this.answerLockTimer);
                this.answerLockTimer = null;
            }
            this.answerLockCountdown = 0;
            if (window.KoodalGameAudio) {
                window.KoodalGameAudio.playSelect();
            }
            if (this.currentIndex < this.totalQuestions - 1) {
                this.currentIndex++;
                this.syncCurrentSelection();
            }
        },
        prevQuestion() {
            if (this.answerLockTimer) {
                clearInterval(this.answerLockTimer);
                this.answerLockTimer = null;
            }
            this.answerLockCountdown = 0;
            if (window.KoodalGameAudio) {
                window.KoodalGameAudio.playSelect();
            }
            if (this.currentIndex > 0) {
                this.currentIndex--;
                const prevQ = this.questions[this.currentIndex];
                if (prevQ) {
                    const updatedLocks = Object.assign({}, this.lockedQuestions);
                    delete updatedLocks[prevQ.id];
                    this.lockedQuestions = updatedLocks;
                }
                this.syncCurrentSelection();
            }
        },
        resetTest() {
            window.location.reload();
        },

        submitGame() {
            if (this.submitting) return;
            this.submitting = true;

            fetch('{{ route("games.postSync", $partner->id) }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ answers: this.selectedAnswers })
            })
            .then(res => res.json())
            .then(data => {
                this.submitting = false;
                if (data.success) {
                    const r = data.result;
                    const maxScore = r.max_score || (r.total_questions * 10);
                    const numericScore = r.score !== null && r.score !== undefined ? r.score : 0;
                    this.result = {
                        score: numericScore,
                        max_score: maxScore,
                        score_pct: maxScore > 0 ? Math.round((numericScore / maxScore) * 100) : 0,
                        matched_count: r.matched_count,
                        total_questions: r.total_questions,
                        vibe_percentage: r.vibe_percentage,
                        waiting_for_partner: !!r.waiting_for_partner,
                        questions_review: r.questions_review || [],
                        invite_id: r.invite_id || null,
                        invite_sent: !!r.invite_sent,
                    };
                    this.completed = true;
                    if (window.KoodalGameAudio) {
                        if (r.matched_count > 0 || numericScore > 0) {
                            if (typeof window.KoodalGameAudio.playMatch === 'function') {
                                window.KoodalGameAudio.playMatch();
                            }
                        } else {
                            if (typeof window.KoodalGameAudio.playWrong === 'function') {
                                window.KoodalGameAudio.playWrong();
                            }
                        }
                        if (typeof window.KoodalGameAudio.playWin === 'function') {
                            window.KoodalGameAudio.playWin();
                        } else {
                            window.KoodalGameAudio.playFinalClapAndFanfare();
                        }
                    }
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                } else {
                    alert('Error saving answers. Please try again.');
                }
            })
            .catch(err => {
                this.submitting = false;
                alert('Network error. Please check your connection and try again.');
                console.error(err);
            });
        },

        resendOfflineInvite() {
            this.offlineInviteText = 'Sending Invite... ⏳';
            fetch(`/games/${this.partnerId}/invite`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({ game_type: 'sync', mode: 'offline' })
            })
            .then(r => r.json())
            .then(d => {
                const invId = (d.invite && d.invite.id) ? d.invite.id : this.result.invite_id;
                const acceptUrl = invId
                    ? `${window.location.origin}/games/invites/${invId}/accept`
                    : `${window.location.origin}/games/${this.userId}/sync`;
                if (navigator.clipboard && navigator.clipboard.writeText) {
                    navigator.clipboard.writeText(acceptUrl).catch(() => {});
                }
                this.offlineInviteText = '✅ Invite Sent & Link Copied!';
                setTimeout(() => { this.offlineInviteText = ''; }, 4000);
            })
            .catch(() => {
                this.offlineInviteText = '✅ Invite Sent!';
                setTimeout(() => { this.offlineInviteText = ''; }, 3000);
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

        sendLiveInvite() {
            this.inviteNotice = 'Sending live invitation... ⌛';

            fetch(`/games/${this.partnerId}/invite`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                body: JSON.stringify({ game_type: 'sync' })
            })
            .then(r => r.json())
            .then(d => {
                if (d.success) {
                    this.invitePending = true;
                    this.inviteNotice = d.message || `Invitation sent to ${this.partnerName}! 🚀 Entering live room...`;
                    if (d.redirect_url) {
                        window.location.href = d.redirect_url;
                    }
                } else {
                    this.inviteNotice = d.message || 'Could not send invitation.';
                }

                setTimeout(() => {
                    this.inviteNotice = '';
                }, 6000);
            })
            .catch(() => {
                this.inviteNotice = 'Network error sending invitation.';
            });
        },

        replayLiveGame() {
            if (this.sendingLiveInvite) return;
            this.sendingLiveInvite = true;
            fetch(`/games/${this.partnerId}/invite`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
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
                this.sendingLiveInvite = false;
            });
        }
    }
}
</script>
@endsection