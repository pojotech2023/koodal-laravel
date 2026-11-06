@extends('layouts.member')

@section('title', '💝 Preference Match — ' . $partner->name)

@section('body')
@php
    $defaultMaleAvatar = "data:image/svg+xml;utf8," . rawurlencode('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 200 200"><defs><linearGradient id="gm" x1="0%" y1="0%" x2="100%" y2="100%"><stop offset="0%" stop-color="#fbcfe8"/><stop offset="50%" stop-color="#f472b6"/><stop offset="100%" stop-color="#60a5fa"/></linearGradient></defs><rect width="200" height="200" rx="100" fill="url(#gm)"/><circle cx="100" cy="78" r="36" fill="#fff" opacity="0.95"/><path d="M40 182 C40 136 160 136 160 182 Z" fill="#fff" opacity="0.95"/></svg>');
    $defaultFemaleAvatar = "data:image/svg+xml;utf8," . rawurlencode('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 200 200"><defs><linearGradient id="gf" x1="0%" y1="0%" x2="100%" y2="100%"><stop offset="0%" stop-color="#f9a8d4"/><stop offset="50%" stop-color="#ec4899"/><stop offset="100%" stop-color="#a855f7"/></linearGradient></defs><rect width="200" height="200" rx="100" fill="url(#gf)"/><circle cx="100" cy="78" r="36" fill="#fff" opacity="0.95"/><path d="M42 182 C42 136 158 136 158 182 Z" fill="#fff" opacity="0.95"/></svg>');

    $userDefaultAvatar = ($user->gender === 'female') ? $defaultFemaleAvatar : $defaultMaleAvatar;
    $partnerDefaultAvatar = ($partner->gender === 'female') ? $defaultFemaleAvatar : $defaultMaleAvatar;

    $resolvedUserPhoto = !empty($userPhotoUrl) ? $userPhotoUrl : $userDefaultAvatar;
    $resolvedPartnerPhoto = !empty($partnerPhotoUrl) ? $partnerPhotoUrl : $partnerDefaultAvatar;

    $pct = (int) ($preferenceMatch['score'] ?? 85);
    $checksList = $preferenceMatch['checks'] ?? [];
    $category = $preferenceMatch['category'] ?? ($pct >= 85 ? 'Perfect Match!' : 'Great Match!');
@endphp

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Great+Vibes&display=swap" rel="stylesheet">

<style>
    .vm-page {
        position: relative;
        overflow-x: hidden;
        background: radial-gradient(circle at 20% 15%, #ffe4f2 0%, #f8d7ff 38%, #e2dcff 75%, #fce7f3 100%);
    }

    #vmHearts { position: fixed; inset: 0; pointer-events: none; z-index: 1; overflow: hidden; }
    .vm-heart { position: absolute; bottom: -40px; opacity: 0; animation: vmFloatUp linear forwards; }
    @keyframes vmFloatUp {
        0%   { transform: translateY(0) translateX(0) rotate(0deg) scale(.6); opacity: 0; }
        12%  { opacity: .8; }
        50%  { transform: translateY(-50vh) translateX(22px) rotate(12deg) scale(1); }
        100% { transform: translateY(-110vh) translateX(-18px) rotate(-10deg) scale(1.15); opacity: 0; }
    }

    .kg-poster {
        position: relative;
        max-width: 580px;
        margin: 0 auto;
        border-radius: 2.25rem;
        padding: 1.75rem 1.35rem 2rem;
        background:
            radial-gradient(circle at 50% 36%, rgba(255, 255, 255, 0.55) 0%, rgba(244, 114, 182, 0.22) 45%, rgba(139, 92, 246, 0.28) 100%),
            linear-gradient(165deg, #ffdde9 0%, #f5c6ec 35%, #d8c7ff 70%, #fbcfe8 100%);
        box-shadow:
            0 25px 60px -12px rgba(190, 24, 93, 0.32),
            0 0 0 2px rgba(255, 255, 255, 0.75) inset;
        overflow: hidden;
    }
    .kg-poster::before {
        content: '';
        position: absolute;
        inset: 0;
        background-image:
            radial-gradient(rgba(255,255,255,0.75) 1.5px, transparent 1.5px),
            radial-gradient(rgba(255,182,219,0.65) 2px, transparent 2px);
        background-size: 38px 38px, 64px 64px;
        background-position: 0 0, 19px 24px;
        opacity: 0.55;
        pointer-events: none;
    }

    .kg-script-title {
        font-family: 'Great Vibes', cursive, Georgia, serif;
        font-size: clamp(2rem, 6vw, 3.2rem);
        line-height: 1.05;
        color: #be185d;
        text-shadow: 0 2px 10px rgba(255, 255, 255, 0.9), 0 4px 18px rgba(244, 63, 140, 0.35);
    }

    .kg-avatar-ring {
        position: relative;
        width: 110px;
        height: 110px;
        border-radius: 9999px;
        padding: 4px;
        background: linear-gradient(135deg, #f43f8e 0%, #ffffff 50%, #ec4899 100%);
        box-shadow:
            0 0 22px rgba(244, 63, 140, 0.65),
            0 0 36px rgba(236, 72, 153, 0.45);
        flex-shrink: 0;
    }
    .kg-avatar-ring img {
        width: 100%;
        height: 100%;
        border-radius: 9999px;
        object-fit: cover;
        border: 3px solid #ffffff;
        background: #fdf2f8;
    }

    .kg-name-pill {
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        background: rgba(255, 255, 255, 0.92);
        backdrop-filter: blur(8px);
        border: 1.5px solid #fbcfe8;
        border-radius: 9999px;
        padding: 0.35rem 0.85rem;
        font-size: 0.85rem;
        font-weight: 900;
        color: #9d174d;
        box-shadow: 0 4px 12px rgba(157, 23, 77, 0.12);
        margin-top: 0.55rem;
        max-width: 150px;
    }

    .kg-meter-wrap {
        position: relative;
        width: 220px;
        height: 220px;
        margin: 0 auto;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .kg-meter-glow {
        position: absolute;
        inset: 12px;
        border-radius: 9999px;
        background: radial-gradient(circle, rgba(244, 63, 140, 0.4) 0%, rgba(236, 72, 153, 0.25) 55%, transparent 75%);
        filter: blur(14px);
        animation: kgPulse 2.8s ease-in-out infinite;
    }
    @keyframes kgPulse {
        0%, 100% { transform: scale(0.92); opacity: 0.65; }
        50%      { transform: scale(1.08); opacity: 0.95; }
    }
    .kg-meter-svg {
        position: absolute;
        inset: 0;
        width: 100%;
        height: 100%;
        transform: rotate(-90deg);
    }
    .kg-meter-inner {
        position: relative;
        z-index: 5;
        width: 148px;
        height: 148px;
        border-radius: 9999px;
        background: radial-gradient(circle at 35% 30%, #ffffff 0%, #fff1f2 60%, #ffe4e6 100%);
        box-shadow:
            0 10px 25px rgba(157, 23, 77, 0.18),
            0 0 0 4px rgba(255, 255, 255, 0.85) inset;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        text-align: center;
        padding: 0.5rem;
    }
    .kg-pct-text {
        font-size: 2.25rem;
        font-weight: 900;
        line-height: 1;
        letter-spacing: -0.03em;
        background: linear-gradient(135deg, #e11d48 0%, #ec4899 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
    }
    .kg-super-pill {
        display: inline-block;
        background: linear-gradient(135deg, #ec4899, #be185d);
        color: #ffffff;
        font-size: 0.72rem;
        font-weight: 900;
        letter-spacing: 0.04em;
        padding: 0.2rem 0.65rem;
        border-radius: 9999px;
        box-shadow: 0 4px 10px rgba(190, 24, 93, 0.35);
        margin-top: 0.3rem;
    }

    .kg-details-card {
        background: rgba(255, 255, 255, 0.94);
        backdrop-filter: blur(12px);
        border: 2px solid rgba(255, 255, 255, 0.95);
        border-radius: 1.75rem;
        padding: 1.25rem 1.15rem;
        box-shadow: 0 16px 36px -8px rgba(190, 24, 93, 0.18);
        position: relative;
        z-10;
    }

    .vm-story-card {
        background: #ffffff;
        border-radius: 2rem;
        box-shadow: 0 20px 45px -10px rgba(190, 24, 93, 0.15);
        border: 2px solid #fce7f3;
    }
</style>

<div class="vm-page min-h-screen py-6 sm:py-10 px-3 sm:px-6">
    {{-- Floating Hearts Animation Background --}}
    <div id="vmHearts" aria-hidden="true"></div>

    <div class="max-w-3xl mx-auto space-y-6 sm:space-y-8 relative z-10">

        {{-- Top Back & Navigation Bar --}}
        <div class="flex flex-wrap items-center justify-between gap-3">
            <a href="{{ route('profile.public', $partner->profile_code) }}"
               class="inline-flex items-center gap-1.5 text-xs sm:text-sm font-bold text-pink-700 hover:text-pink-900 bg-white/80 backdrop-blur px-3.5 py-1.5 rounded-full border border-pink-200 shadow-xs transition hover:scale-105">
                <span>←</span>
                <span>Back to {{ $partner->name }}'s Profile</span>
            </a>

            <div class="flex flex-wrap items-center gap-2">
                <span class="rounded-full px-3.5 py-1.5 text-xs font-black text-white shadow-xs" style="background: linear-gradient(135deg, #ec4899, #be185d)">
                    💝 Preference Match
                </span>
                <a href="{{ route('profile.rasi', $partner->profile_code) }}"
                   class="rounded-full bg-white/80 border border-purple-200 px-3.5 py-1.5 text-xs font-bold text-purple-700 hover:bg-purple-50 transition">
                    🔮 Rasi Porutham
                </a>
                <a href="{{ route('games.vibe', $partner->id) }}"
                   class="rounded-full bg-white/80 border border-pink-200 px-3.5 py-1.5 text-xs font-bold text-pink-700 hover:bg-pink-50 transition">
                    ❤️ Vibe Meter
                </a>
                <a href="{{ route('games.hub', $partner->id) }}"
                   class="rounded-full bg-white/80 border border-purple-200 px-3.5 py-1.5 text-xs font-bold text-purple-700 hover:bg-purple-50 transition">
                    🎮 Games Hub
                </a>
            </div>
        </div>

        {{-- ====================================================================
             MAIN POSTER CARD (VIBE METER DESIGN)
             ==================================================================== --}}
        <div class="kg-poster text-center">

            <div class="relative z-10 space-y-1 mb-4">
                <h1 class="kg-script-title">Partner Preference Match</h1>
                <p class="text-[11px] sm:text-xs font-extrabold uppercase tracking-widest text-pink-900/80">
                    Personalized Criteria &amp; Lifestyle Compatibility ✨
                </p>
            </div>

            {{-- Couple Avatars Side-by-side --}}
            <div class="relative z-10 flex items-center justify-between gap-2 sm:gap-4 my-2 px-1">
                {{-- User (Viewer) --}}
                <div class="flex flex-col items-center text-center flex-1 min-w-0">
                    <div class="kg-avatar-ring">
                        <img src="{{ $resolvedUserPhoto }}" alt="{{ $user->name }}">
                    </div>
                    <div class="kg-name-pill" title="{{ $user->name }}">
                        <span class="truncate">{{ \Illuminate\Support\Str::words($user->name, 2, '') }}</span>
                    </div>
                    @if(!empty($userLocation))
                    <div class="mt-1 flex items-center justify-center gap-1 text-[10px] sm:text-xs font-bold text-pink-950/80">
                        <span>📍</span>
                        <span class="truncate max-w-[110px]">{{ $userLocation }}</span>
                    </div>
                    @endif
                </div>

                {{-- Center Heart Icon --}}
                <div class="shrink-0 flex flex-col items-center justify-center px-1">
                    <span class="text-3xl animate-bounce">💝</span>
                    <span class="text-[10px] font-black text-rose-600 uppercase tracking-wider mt-1">Preference</span>
                </div>

                {{-- Partner --}}
                <div class="flex flex-col items-center text-center flex-1 min-w-0">
                    <div class="kg-avatar-ring">
                        <img src="{{ $resolvedPartnerPhoto }}" alt="{{ $partner->name }}">
                    </div>
                    <div class="kg-name-pill" title="{{ $partner->name }}">
                        <span class="truncate">{{ \Illuminate\Support\Str::words($partner->name, 2, '') }}</span>
                    </div>
                    @if(!empty($partnerLocation))
                    <div class="mt-1 flex items-center justify-center gap-1 text-[10px] sm:text-xs font-bold text-pink-950/80">
                        <span>📍</span>
                        <span class="truncate max-w-[110px]">{{ $partnerLocation }}</span>
                    </div>
                    @endif
                </div>
            </div>

            {{-- Center Neon Circular Vibe Score Ring --}}
            <div class="relative z-10 my-3">
                <div class="kg-meter-wrap">
                    <div class="kg-meter-glow"></div>
                    <svg class="kg-meter-svg" viewBox="0 0 200 200">
                        <defs>
                            <linearGradient id="kgPrefGrad" x1="0%" y1="0%" x2="100%" y2="100%">
                                <stop offset="0%" stop-color="#ff2e93"/>
                                <stop offset="50%" stop-color="#fb7185"/>
                                <stop offset="100%" stop-color="#c084fc"/>
                            </linearGradient>
                        </defs>
                        <circle cx="100" cy="100" r="92" fill="none" stroke="rgba(255,255,255,0.65)" stroke-width="2" stroke-dasharray="3 6"></circle>
                        <circle cx="100" cy="100" r="80" fill="none" stroke="rgba(255,255,255,0.45)" stroke-width="14"></circle>
                        <circle id="prefRingFg"
                                cx="100" cy="100" r="80"
                                fill="none"
                                stroke="url(#kgPrefGrad)"
                                stroke-width="14"
                                stroke-linecap="round"
                                stroke-dasharray="502.65"
                                stroke-dashoffset="{{ 502.65 - (502.65 * $pct / 100) }}"></circle>
                    </svg>
                    <div class="kg-meter-inner">
                        <div class="kg-pct-text">
                            {{ $pct }}%
                        </div>
                        <div class="kg-super-pill">
                            {{ $category }}
                        </div>
                        <span class="text-rose-600 text-[11px] font-bold mt-0.5">
                            Match Score
                        </span>
                    </div>
                </div>
            </div>

            {{-- Quick Summary White Card --}}
            <div class="kg-details-card space-y-3">
                <div class="flex items-center justify-center gap-1.5 border-b border-pink-100 pb-2">
                    <span class="text-rose-600 text-base">💖</span>
                    <h2 class="text-sm sm:text-base font-black text-pink-950 tracking-tight">
                        Preference Match: {{ $pct }}% {{ $category }}
                    </h2>
                    <span class="text-rose-600 text-base">💖</span>
                </div>

                <p class="text-xs sm:text-sm font-medium text-gray-700 leading-relaxed">
                    {{ $partner->name }} fulfills your key partner expectations in age, height, education, profession, and family lifestyle values!
                </p>

                <div class="grid grid-cols-2 gap-2 pt-1">
                    <form method="POST" action="{{ route('messages.start') }}">
                        @csrf
                        <input type="hidden" name="profile_code" value="{{ $partner->profile_code }}">
                        <button type="submit" class="w-full rounded-full py-2.5 px-3 text-center text-xs font-black text-white shadow transition hover:opacity-95 cursor-pointer" style="background: linear-gradient(135deg, #ec4899, #be185d);">
                            💬 Message {{ \Illuminate\Support\Str::words($partner->name, 1, '') }}
                        </button>
                    </form>
                    <a href="{{ route('profile.wizard.step', 'preferences') }}"
                       class="rounded-full py-2.5 px-3 text-center text-xs font-black text-pink-800 bg-pink-100 hover:bg-pink-200 border border-pink-200 transition">
                        ⚙️ Edit My Preference
                    </a>
                </div>
            </div>

        </div>

        {{-- ====================================================================
             DETAILED REPORT SECTION
             ==================================================================== --}}
        <div class="vm-story-card p-5 sm:p-8 space-y-6">

            {{-- Matched Criteria Checks Header --}}
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-pink-100 pb-3">
                <div>
                    <h2 class="text-lg sm:text-xl font-black text-pink-950 flex items-center gap-2">
                        <span>🎯</span>
                        <span>பொருந்திய விருப்பங்கள் (Criteria Checks)</span>
                    </h2>
                    <p class="text-xs font-bold text-pink-600">
                        Overall Compatibility: {{ $pct }}% Perfect Match
                    </p>
                </div>
                <a href="{{ route('profile.wizard.step', 'preferences') }}"
                   class="rounded-full bg-white border border-pink-200 px-3.5 py-1 text-xs font-bold text-pink-700 hover:bg-pink-50 transition shadow-xs">
                    ⚙️ Edit Preferences
                </a>
            </div>

            {{-- Criteria Checks Grid Badges --}}
            @if(!empty($checksList))
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5 text-xs font-bold">
                    @foreach($checksList as $chk)
                        <div class="flex items-center justify-between p-3 rounded-xl border {{ !empty($chk['matched']) ? 'bg-emerald-50/70 border-emerald-200 text-emerald-950' : 'bg-amber-50/70 border-amber-200 text-amber-950' }}">
                            <div class="flex items-center gap-2">
                                <span class="inline-flex items-center justify-center w-5 h-5 rounded-full font-black text-xs {{ !empty($chk['matched']) ? 'bg-emerald-600 text-white' : 'bg-amber-500 text-white' }}">
                                    {{ !empty($chk['matched']) ? '✓' : '•' }}
                                </span>
                                <span class="font-bold text-gray-700">{{ $chk['label'] }}</span>
                            </div>
                            <span class="font-black {{ !empty($chk['matched']) ? 'text-emerald-900' : 'text-amber-900' }}">
                                {{ $chk['actual'] }}
                            </span>
                        </div>
                    @endforeach
                </div>
            @endif

            {{-- Personalized Preference Story Narrative --}}
            <div class="bg-gradient-to-br from-rose-50/80 via-pink-50/50 to-white rounded-2xl p-5 sm:p-6 border-2 border-pink-200 shadow-xs space-y-3.5">
                <div class="flex items-center gap-2 border-b border-pink-200/80 pb-2.5">
                    <span class="text-xl">📖</span>
                    <h3 class="text-sm sm:text-base font-black text-pink-950 uppercase tracking-wider">
                        விருப்பப் பொருத்தக் கதை (Preference Story)
                    </h3>
                </div>

                <div class="text-xs sm:text-sm leading-relaxed text-gray-800 space-y-3">
                    {!! nl2br(preg_replace('/\*\*(.*?)\*\*/', '<strong class="font-black text-rose-950">$1</strong>', e($preferenceMatch['story_text'] ?? ''))) !!}
                </div>
            </div>

            {{-- Call to Action Card --}}
            <div class="pt-2 flex flex-col sm:flex-row items-center justify-between gap-4 p-5 rounded-2xl bg-gradient-to-r from-pink-50 to-rose-50 border border-pink-200">
                <div class="text-left">
                    <span class="text-xs font-extrabold text-pink-600 uppercase tracking-wider block">Recommended Next Step:</span>
                    <span class="text-sm font-black text-gray-900">Connect with {{ $partner->name }} and take your journey forward!</span>
                </div>
                <form method="POST" action="{{ route('messages.start') }}" class="w-full sm:w-auto">
                    @csrf
                    <input type="hidden" name="profile_code" value="{{ $partner->profile_code }}">
                    <button type="submit" class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-6 py-3 rounded-xl font-black text-sm text-white shadow-lg transition hover:scale-105 cursor-pointer" style="background: linear-gradient(135deg, #ec4899, #be185d)">
                        💬 START CONVERSATION →
                    </button>
                </form>
            </div>

        </div>

    </div>
</div>

<script>
    // Floating hearts animation
    (function () {
        const heartsWrap = document.getElementById('vmHearts');
        if (!heartsWrap) return;
        const emojis = ['💖', '✨', '🌸', '💝', '🌹', '❤️'];
        for (let i = 0; i < 18; i++) {
            const h = document.createElement('span');
            h.className = 'vm-heart';
            h.textContent = emojis[Math.floor(Math.random() * emojis.length)];
            h.style.left = (Math.random() * 96) + '%';
            h.style.fontSize = (12 + Math.random() * 16) + 'px';
            h.style.animationDuration = (8 + Math.random() * 8) + 's';
            h.style.animationDelay = (Math.random() * 9) + 's';
            heartsWrap.appendChild(h);
        }
    })();
</script>
@endsection
