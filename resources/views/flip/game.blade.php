@extends('layouts.app')

@section('title', 'Flip the Tiles — 1v1 Live Match')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/flip-game.css') }}">
@endpush

@section('content')
<link rel="stylesheet" href="{{ asset('css/flip-game.css') }}">
<canvas id="crackers-canvas"></canvas>

<div class="min-h-screen bg-slate-100 py-6 px-3 sm:px-6">
    <div class="flip-main-layout">

        {{-- LEFT COLUMN: Game Title, Score Bar, Board --}}
        <div class="flip-game-container space-y-4">

            {{-- Top Title, 5-Min Timer & Status Banner --}}
            <div class="flex items-center justify-between px-1 bg-white p-3.5 rounded-2xl border border-slate-200 shadow-sm">
                <div>
                    <h1 class="text-xl sm:text-2xl font-black text-slate-800 tracking-tight">Flip the Tiles</h1>
                    <div id="status-text" class="text-xs font-bold text-slate-600 mt-0.5">
                        @if($game->isWaiting())
                            ⏳ Waiting for opponent...
                        @else
                            🎮 Match in progress...
                        @endif
                    </div>
                </div>

                <div class="flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-orange-50 border border-orange-200 text-xs font-black text-orange-700">
                    <span>🧩</span>
                    <span>1v1 Match</span>
                </div>
            </div>

            {{-- Top Row matching uploaded screenshot --}}
            <div class="flip-top-row">
                {{-- Player 1 Score Card --}}
                <div id="p1-card" class="player-score-card p1">
                    <div id="p1-name" class="player-label">{{ $game->p1_name }}</div>
                    <div id="p1-score" class="player-score-val">{{ $game->p1_score }}</div>
                </div>

                {{-- Center Eye Preview Button --}}
                <button id="preview-btn" type="button" class="preview-eye-btn" title="Preview All Tiles (2s)">
                    👁️
                </button>

                {{-- Player 2 Score Card --}}
                <div id="p2-card" class="player-score-card p2">
                    <div id="p2-name" class="player-label">{{ $game->p2_name ?? 'Player 2' }}</div>
                    <div id="p2-score" class="player-score-val">{{ $game->p2_score }}</div>
                </div>
            </div>

            {{-- Ready Section Bar with Start Game Button (Prominent & Visible on Waiting State) --}}
            <div id="ready-section" class="{{ $game->isWaiting() ? '' : 'hidden' }} bg-white rounded-2xl p-4 sm:p-5 border-2 border-orange-200 shadow-md flex flex-col sm:flex-row items-center justify-between gap-4">
                <div class="flex items-center gap-4 sm:gap-6 text-xs sm:text-sm font-bold text-slate-700">
                    <div class="flex items-center gap-2">
                        <span>🤵 Groom:</span>
                        <span id="p1-ready-status" class="ready-status-badge {{ $game->p1_ready ? 'is-ready' : 'not-ready' }}">
                            {{ $game->p1_ready ? 'Ready ✓' : 'Waiting ⏳' }}
                        </span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span>👰 Bride:</span>
                        <span id="p2-ready-status" class="ready-status-badge {{ $game->p2_ready ? 'is-ready' : 'not-ready' }}">
                            {{ $game->p2_ready ? 'Ready ✓' : 'Waiting ⏳' }}
                        </span>
                    </div>
                </div>
                <button id="ready-btn" type="button" class="flip-btn-start">
                    <span>🚀</span>
                    <span>Start the Game</span>
                </button>
            </div>

            {{-- Main Game Board Container --}}
            <div class="relative bg-white rounded-3xl p-3 sm:p-4 shadow-xl border border-slate-200">

                {{-- FULLSCREEN ATTRACTIVE 3-2-1 COUNTDOWN & SECRET LOVE RULE OVERLAY --}}
                <div id="countdown-overlay" class="hidden fixed inset-0 z-50 bg-slate-950/92 backdrop-blur-xl flex items-center justify-center p-4">
                    {{-- Secret Love Rule Splash Card (5-second display before count) --}}
                    <div id="countdown-rule-box" class="max-w-lg w-full mx-auto text-center space-y-5">
                        <div class="relative inline-block">
                            <div class="text-6xl animate-bounce">💖</div>
                        </div>
                        
                        <div class="relative rounded-3xl p-6 sm:p-8 text-center space-y-4 shadow-2xl border-2 border-pink-400 overflow-hidden"
                             style="background: radial-gradient(circle at top, rgba(244, 63, 94, 0.25) 0%, rgba(15, 23, 42, 0.95) 100%), #0f172a;">
                            
                            {{-- Glowing ambient background badge --}}
                            <div class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full bg-gradient-to-r from-pink-500 via-rose-500 to-purple-600 text-white text-xs font-black uppercase tracking-widest shadow-lg">
                                <span>✨</span>
                                <span>Secret Love Rule</span>
                                <span>✨</span>
                            </div>

                            <p class="text-base sm:text-lg font-black text-pink-100 leading-relaxed drop-shadow-md">
                                “The true goal of this game is to make your partner win! 😉✨ Match the tile your partner opens to gift them the point & win their heart!” 💖
                            </p>

                            <div class="pt-2 flex items-center justify-center gap-2 text-xs sm:text-sm font-extrabold text-amber-300">
                                <span>🎮 Match starts in</span>
                                <span id="rule-countdown-sec" class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-amber-400 text-slate-950 text-base font-black shadow-md">5</span>
                                <span>seconds...</span>
                            </div>
                        </div>
                    </div>

                    {{-- 3-2-1 Big Numbers Box --}}
                    <div id="countdown-number-box" class="hidden flex flex-col items-center justify-center text-center space-y-3">
                        <div id="countdown-number" class="text-8xl sm:text-[10rem] font-black text-amber-400 drop-shadow-[0_10px_35px_rgba(251,191,36,0.6)] scale-100 transition-all duration-300">3</div>
                        <div class="text-sm sm:text-base font-black uppercase tracking-widest text-pink-200">Get Ready to Play! 🎮</div>
                    </div>
                </div>

                {{-- Waiting Screen Overlay (Before Player 2 Joins) --}}
                <div id="waiting-overlay" class="{{ $game->isWaiting() && empty($game->p2_token) ? '' : 'hidden' }} absolute inset-0 z-20 bg-slate-900/85 backdrop-blur-sm rounded-3xl flex items-center justify-center p-6 text-center text-white">
                    <div class="max-w-sm space-y-4">
                        <div class="w-16 h-16 rounded-full bg-gradient-to-br from-orange-400 to-pink-500 text-white mx-auto flex items-center justify-center text-3xl shadow-lg animate-bounce">
                            💌
                        </div>
                        <h2 class="text-xl font-black text-white">Invite Partner to Play</h2>
                        <p class="text-xs text-slate-300 font-medium leading-relaxed">
                            Share this game link on WhatsApp or copy it. Once your partner joins, click <strong>Start the Game</strong> to play live!
                        </p>

                        <div class="space-y-2.5 pt-1">
                            <div class="flex items-center gap-2 bg-slate-800/90 p-2 rounded-xl border border-slate-700 shadow-inner">
                                <input type="text"
                                       readonly
                                       id="invite-link-input"
                                       value="{{ route('flip.join', ['code' => $game->code]) }}"
                                       class="bg-slate-950 text-xs text-amber-300 font-mono font-bold w-full px-3 py-2 rounded-lg border border-slate-800 outline-none select-all">
                                <button id="copy-invite-btn"
                                        type="button"
                                        class="shrink-0 bg-orange-500 hover:bg-orange-600 text-white px-3.5 py-2 rounded-lg text-xs font-black uppercase tracking-wider transition-all shadow-md">
                                    Copy
                                </button>
                            </div>

                            @php
                                $waText = "🌸 *Koodal Matrimony* — *Flip the Tiles 1v1 Memory Game!* 🎮\n\nHey! " . ($game->p1_name ?? 'Groom') . " invites you to play 1v1 online tile match game live!\n\nClick link to join now:\n" . route('flip.join', ['code' => $game->code]);
                            @endphp

                            <a href="https://api.whatsapp.com/send?text={{ urlencode($waText) }}"
                               target="_blank"
                               rel="noopener noreferrer"
                               class="flex items-center justify-center gap-2 w-full py-3 px-4 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-black uppercase tracking-wider transition-all shadow-md transform hover:-translate-y-0.5">
                                <span>📲</span>
                                <span>Share Game Link on WhatsApp</span>
                            </a>
                        </div>
                    </div>
                </div>

                {{-- 4x4 Board Grid (16 Tiles) --}}
                <div id="flip-board" class="flip-board">
                    @for($i = 0; $i < 16; $i++)
                        <div class="tile-item" data-index="{{ $i }}">
                            <div class="tile-inner">
                                <div class="tile-face tile-back">
                                    <div class="tile-back-circle"></div>
                                </div>
                                <div class="tile-face tile-front">
                                    <img src="" alt="" loading="lazy">
                                </div>
                            </div>
                        </div>
                    @endfor
                </div>
            </div>

        </div>

    </div>
</div>

{{-- Result Overlay Modal --}}
<div id="result-modal" class="hidden fixed inset-0 z-50 bg-slate-900/80 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="bg-white rounded-3xl p-6 sm:p-7 max-w-sm w-full text-center space-y-3.5 shadow-2xl border border-slate-100 transform transition-all scale-100">
        <div class="w-16 h-16 rounded-full bg-amber-100 text-amber-500 mx-auto flex items-center justify-center text-3xl shadow-inner">
            🏆
        </div>
        <h2 id="result-title" class="text-xl sm:text-2xl font-black text-slate-900">Game Over!</h2>
        <p id="result-desc" class="text-xs font-bold text-slate-600 leading-relaxed"></p>

        {{-- Couple Chemistry Score Box --}}
        <div id="result-chemistry" class="rounded-2xl p-3.5 border-2 border-pink-200 text-center shadow-sm" style="background: linear-gradient(135deg, #FFF0F5 0%, #FFF5F8 100%);">
            <div class="text-[11px] font-black uppercase tracking-wider text-pink-600 flex items-center justify-center gap-1.5 mb-1">
                <span>💖</span><span>Couple Chemistry Score</span><span>💖</span>
            </div>
            <div id="chemistry-score-badge" class="text-3xl font-black tracking-tight" style="color: #E0245E;">
                100%
            </div>
            <div id="chemistry-score-detail" class="text-xs font-bold text-slate-600 mt-0.5">
                Perfect Team Balance · 4 vs 4 Pairs
            </div>
            <div class="w-full bg-pink-100 rounded-full h-2 mt-2 overflow-hidden border border-pink-200">
                <div id="chemistry-progress-bar" class="h-2 rounded-full transition-all duration-700" style="width: 100%; background: linear-gradient(90deg, #E0245E, #F43F5E, #FB7185);"></div>
            </div>
        </div>

        <div class="pt-2 space-y-2.5">
            {{-- Replay Game Button (Fresh Round with Same Partner) --}}
            <button type="button" 
                    id="flip-replay-game-btn"
                    class="w-full py-3.5 px-6 rounded-2xl font-black text-xs uppercase tracking-wider shadow-md transition-all cursor-pointer"
                    style="background: linear-gradient(135deg, #10B981 0%, #059669 100%) !important; color: #FFFFFF !important; border: none !important; display: block; width: 100%; font-size: 13px; font-weight: 900;">
                🔄 Replay Game (Fresh Match with Partner)
            </button>

            {{-- Next Game (Memory Snap) --}}
            <button type="button" 
                    id="flip-next-game-btn"
                    class="w-full py-3.5 px-6 rounded-2xl font-black text-xs uppercase tracking-wider shadow-md transition-all cursor-pointer"
                    style="background: linear-gradient(135deg, #7C3AED 0%, #6D28D9 100%) !important; color: #FFFFFF !important; border: none !important; display: block; width: 100%; font-size: 13px; font-weight: 900;">
                🎮 Next Game (Memory Snap) →
            </button>

            <div class="grid grid-cols-2 gap-2 pt-1">
                <a href="{{ $partnerId ? route('games.vibe', $partnerId) : route('dashboard') }}" 
                   id="flip-vibe-btn"
                   class="inline-flex items-center justify-center gap-1.5 py-3 px-3 rounded-2xl font-extrabold text-xs uppercase tracking-wider transition-all cursor-pointer shadow-sm"
                   style="background: linear-gradient(135deg, #FDF2F8 0%, #FCE7F3 100%); color: #BE185D; border: 1.5px solid #FBCFE8; text-decoration: none;">
                    📊 Vibe Meter
                </a>
                <a href="{{ $partnerId ? route('games.hub', $partnerId) : route('dashboard') }}" 
                   id="flip-hub-btn"
                   class="inline-flex items-center justify-center gap-1.5 py-3 px-3 rounded-2xl font-extrabold text-xs uppercase tracking-wider transition-all cursor-pointer shadow-sm"
                   style="background-color: #F8FAFC; color: #334155; border: 1.5px solid #CBD5E1; text-decoration: none;">
                    🏠 Games Hub
                </a>
            </div>
        </div>
    </div>
</div>

{{-- Toast Notification --}}
<div id="flip-toast" class="flip-toast"></div>

<x-floating-chat />
@endsection

@push('scripts')
<script src="{{ asset('js/flip-game.js') }}"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    new FlipGameApp({
        code: @json($game->code),
        you: @json($you),
        partnerId: @json($partnerId ?? 1),
        csrfToken: @json(csrf_token())
    });
});
</script>
@endpush
