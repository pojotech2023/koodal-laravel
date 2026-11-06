@extends('layouts.member')

@section('title', '❤️ Kaadhal Games Vibe Meter — ' . $partner->name)

@section('body')
@php
    $summary = $vibe->games_summary ?? [];

    $anyGamePlayed = !empty($summary['sync']['played'])
        || !empty($summary['find_it']['played'])
        || !empty($summary['memory_snap']['played'])
        || !empty($summary['memory']['played'])
        || !empty($summary['flip']['played']);

    $bars = $compatibilityBars ?? [
        ['label' => 'Compatibility', 'score' => !empty($summary['sync']['played']) ? (int) round($summary['sync']['score'] ?? 0) : 0, 'played' => !empty($summary['sync']['played'])],
        ['label' => 'Memory',        'score' => !empty($summary['memory_snap']['played']) ? (int) round($summary['memory_snap']['score'] ?? 0) : 0, 'played' => !empty($summary['memory_snap']['played'])],
        ['label' => 'Observation',   'score' => !empty($summary['find_it']['played']) ? (int) round($summary['find_it']['score'] ?? 0) : 0, 'played' => !empty($summary['find_it']['played'])],
        ['label' => 'Team Work',     'score' => !empty($summary['flip']['played']) ? (int) round($summary['flip']['score'] ?? 0) : 0, 'played' => !empty($summary['flip']['played'])],
    ];

    // Overall Vibe Percentage: uses game vibe_percentage when played, or average of active bars
    $activeScores = collect($bars)->filter(fn($b) => $b['played'] || $b['score'] > 0)->pluck('score');
    $calculatedOverall = $activeScores->isNotEmpty() ? (int) round($activeScores->avg()) : 0;
    $pct = $anyGamePlayed ? (int) ($vibe->vibe_percentage ?: $calculatedOverall) : $calculatedOverall;
    $pct = max(0, min(100, $pct));

    $matchBadgeText = 'Super Match!';
    $compatSummaryText = 'Great compatibility! Both of you share strong family values and life goals.';
    if (!$anyGamePlayed) {
        $matchBadgeText = 'Play Games!';
        $compatSummaryText = 'Play the 4 Koodal Games together to unlock your full compatibility score and personalized love story!';
    } elseif ($pct < 55) {
        $matchBadgeText = 'Unique Spark!';
        $compatSummaryText = 'Opposites attract! Your unique perspectives make every conversation and game exciting.';
    } elseif ($pct < 75) {
        $matchBadgeText = 'Good Match!';
        $compatSummaryText = 'Solid harmony! Both of you share complementary strengths and mutual understanding.';
    } elseif ($pct < 88) {
        $matchBadgeText = 'Great Match!';
        $compatSummaryText = 'Great compatibility! Both of you share strong family values, teamwork, and life goals.';
    }

    // Default SVG Avatars (Groom & Bride) when member has not uploaded a profile photo
    $defaultMaleAvatar = "data:image/svg+xml;utf8," . rawurlencode('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 200 200"><defs><linearGradient id="gm" x1="0%" y1="0%" x2="100%" y2="100%"><stop offset="0%" stop-color="#fbcfe8"/><stop offset="50%" stop-color="#f472b6"/><stop offset="100%" stop-color="#60a5fa"/></linearGradient></defs><rect width="200" height="200" rx="100" fill="url(#gm)"/><circle cx="100" cy="78" r="36" fill="#fff" opacity="0.95"/><path d="M40 182 C40 136 160 136 160 182 Z" fill="#fff" opacity="0.95"/></svg>');
    $defaultFemaleAvatar = "data:image/svg+xml;utf8," . rawurlencode('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 200 200"><defs><linearGradient id="gf" x1="0%" y1="0%" x2="100%" y2="100%"><stop offset="0%" stop-color="#f9a8d4"/><stop offset="50%" stop-color="#ec4899"/><stop offset="100%" stop-color="#a855f7"/></linearGradient></defs><rect width="200" height="200" rx="100" fill="url(#gf)"/><circle cx="100" cy="78" r="36" fill="#fff" opacity="0.95"/><path d="M42 182 C42 136 158 136 158 182 Z" fill="#fff" opacity="0.95"/></svg>');

    $userDefaultAvatar = ($user->gender ?? 'male') === 'female' ? $defaultFemaleAvatar : $defaultMaleAvatar;
    $partnerDefaultAvatar = ($partner->gender ?? 'female') === 'female' ? $defaultFemaleAvatar : $defaultMaleAvatar;

    $resolvedUserPhoto = !empty($userPhotoUrl) ? $userPhotoUrl : $userDefaultAvatar;
    $resolvedPartnerPhoto = !empty($partnerPhotoUrl) ? $partnerPhotoUrl : $partnerDefaultAvatar;

    $rawSyncQa = $story['sync_qa'] ?? [];
    if (isset($rawSyncQa['all']) && is_array($rawSyncQa['all'])) {
        $rawSyncQa = $rawSyncQa['all'];
    }
    $syncQa = array_values(array_filter((array) $rawSyncQa, fn($item) => is_array($item) && (!empty($item['question_text']) || !empty($item['question_en']))));
@endphp

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Great+Vibes&display=swap" rel="stylesheet">

<style>
    /* ============ KAADHAL GAMES VIBE METER — IMAGE 2 DESIGN + STORY ============ */
    .vm-page {
        position: relative;
        overflow-x: hidden;
        background: radial-gradient(circle at 20% 15%, #ffe4f2 0%, #f8d7ff 38%, #e2dcff 75%, #fce7f3 100%);
    }

    /* Floating hearts & sparkles background */
    #vmHearts { position: fixed; inset: 0; pointer-events: none; z-index: 1; overflow: hidden; }
    .vm-heart { position: absolute; bottom: -40px; opacity: 0; animation: vmFloatUp linear forwards; }
    @keyframes vmFloatUp {
        0%   { transform: translateY(0) translateX(0) rotate(0deg) scale(.6); opacity: 0; }
        12%  { opacity: .8; }
        50%  { transform: translateY(-50vh) translateX(22px) rotate(12deg) scale(1); }
        100% { transform: translateY(-110vh) translateX(-18px) rotate(-10deg) scale(1.15); opacity: 0; }
    }

    #vmConfetti { position: fixed; inset: 0; pointer-events: none; z-index: 60; overflow: hidden; }
    .vm-confetti { position: absolute; top: -20px; width: 10px; height: 14px; opacity: 0; animation: vmConfetti linear forwards; }
    @keyframes vmConfetti {
        0%   { transform: translateY(0) rotate(0deg); opacity: 1; }
        100% { transform: translateY(105vh) rotate(720deg); opacity: 0; }
    }

    /* Main Kaadhal Games Poster Card */
    .kg-poster {
        position: relative;
        max-width: 560px;
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
        font-size: clamp(1.9rem, 6vw, 3.1rem);
        line-height: 1.05;
        color: #9d174d;
        text-shadow: 0 2px 10px rgba(255, 255, 255, 0.9), 0 4px 18px rgba(236, 72, 153, 0.35);
    }

    /* Glowing Profile Avatar Frame */
    .kg-avatar-ring {
        position: relative;
        width: 112px;
        height: 112px;
        border-radius: 9999px;
        padding: 4px;
        background: linear-gradient(135deg, #ff4db8 0%, #ffffff 50%, #38bdf8 100%);
        box-shadow:
            0 0 22px rgba(244, 63, 140, 0.65),
            0 0 36px rgba(56, 189, 248, 0.45);
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

    /* Name Pill Badge */
    .kg-name-pill {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 5px;
        margin-top: -10px;
        position: relative;
        z-index: 3;
        padding: 4px 14px;
        border-radius: 9999px;
        background: linear-gradient(90deg, #ec4899 0%, #db2777 50%, #fb7185 100%);
        color: #fff;
        font-size: 0.85rem;
        font-weight: 800;
        box-shadow: 0 6px 16px rgba(219, 39, 119, 0.4), 0 0 0 2px rgba(255,255,255,0.85);
        max-width: 165px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    /* Center Neon Heart Connector */
    .kg-heart-bridge {
        position: relative;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        min-width: 68px;
        flex-shrink: 0;
    }
    .kg-neon-heart {
        width: 54px;
        height: 54px;
        border-radius: 9999px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: radial-gradient(circle, rgba(255,255,255,0.95) 0%, rgba(251,113,133,0.35) 65%, transparent 100%);
        filter: drop-shadow(0 0 12px #ff2e93) drop-shadow(0 0 22px #38bdf8);
        animation: vmHeartbeat 2s ease-in-out infinite;
    }

    /* Neon Circular Vibe Meter Ring */
    .kg-meter-wrap {
        position: relative;
        width: 215px;
        height: 215px;
        margin: 0.35rem auto 0.75rem;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .kg-meter-glow {
        position: absolute;
        inset: -12px;
        border-radius: 9999px;
        background: radial-gradient(circle, rgba(236,72,153,0.32) 0%, rgba(56,189,248,0.28) 55%, transparent 74%);
        filter: blur(8px);
        animation: vmPulseSoft 3.5s ease-in-out infinite;
    }
    .kg-meter-svg {
        width: 100%;
        height: 100%;
        transform: rotate(-90deg);
        filter: drop-shadow(0 0 10px rgba(236, 72, 153, 0.65)) drop-shadow(0 0 18px rgba(56, 189, 248, 0.55));
    }
    .kg-meter-inner {
        position: absolute;
        inset: 24px;
        border-radius: 9999px;
        background: radial-gradient(circle at 35% 30%, rgba(255, 255, 255, 0.92) 0%, rgba(252, 231, 243, 0.88) 55%, rgba(224, 231, 255, 0.85) 100%);
        box-shadow:
            0 8px 25px rgba(190, 24, 93, 0.22),
            0 0 0 3px rgba(255, 255, 255, 0.9) inset;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        text-align: center;
    }
    .kg-pct-text {
        font-size: 3.1rem;
        line-height: 1;
        font-weight: 900;
        background: linear-gradient(180deg, #831843 0%, #be185d 55%, #db2777 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        filter: drop-shadow(0 2px 4px rgba(255,255,255,0.9));
    }
    .kg-super-pill {
        margin-top: 6px;
        padding: 4px 14px;
        border-radius: 9999px;
        background: linear-gradient(90deg, #ec4899, #db2777);
        color: #fff;
        font-size: 0.78rem;
        font-weight: 800;
        box-shadow: 0 4px 12px rgba(219, 39, 119, 0.35);
    }

    /* Compatibility Details Inner White Card */
    .kg-details-card {
        position: relative;
        z-index: 2;
        border-radius: 1.65rem;
        background: rgba(255, 255, 255, 0.92);
        backdrop-filter: blur(12px);
        padding: 1.35rem 1.25rem 1.45rem;
        box-shadow:
            0 16px 38px rgba(157, 23, 77, 0.14),
            0 0 0 1.5px rgba(255, 255, 255, 0.95) inset;
    }

    @media (max-width: 480px) {
        .kg-poster {
            border-radius: 1.5rem;
            padding: 1.15rem 0.65rem 1.35rem;
        }
        .kg-avatar-ring {
            width: 74px;
            height: 74px;
            padding: 3px;
        }
        .kg-avatar-ring img {
            border-width: 2px;
        }
        .kg-name-pill {
            font-size: 0.7rem;
            padding: 3px 8px;
            gap: 3px;
            max-width: 105px;
            margin-top: -8px;
        }
        .kg-heart-bridge {
            min-width: 34px;
        }
        .kg-neon-heart {
            width: 36px;
            height: 36px;
        }
        .kg-meter-wrap {
            width: 175px;
            height: 175px;
            margin: 0.25rem auto 0.5rem;
        }
        .kg-meter-inner {
            inset: 20px;
        }
        .kg-pct-text {
            font-size: 2.35rem;
        }
        .kg-super-pill {
            font-size: 0.68rem;
            padding: 3px 10px;
            margin-top: 4px;
        }
        .kg-details-card {
            border-radius: 1.25rem;
            padding: 1rem 0.75rem 1.1rem;
        }
    }

    @media (max-width: 350px) {
        .kg-avatar-ring {
            width: 66px;
            height: 66px;
        }
        .kg-name-pill {
            max-width: 92px;
            font-size: 0.65rem;
            padding: 2px 6px;
        }
        .kg-heart-bridge {
            min-width: 28px;
        }
        .kg-neon-heart {
            width: 30px;
            height: 30px;
        }
    }
    .kg-bar-track {
        height: 11px;
        width: 100%;
        border-radius: 9999px;
        background: #fce7f3;
        overflow: hidden;
        box-shadow: inset 0 1px 3px rgba(190, 24, 93, 0.12);
    }
    .kg-bar-fill {
        height: 100%;
        width: 0;
        border-radius: 9999px;
        background: linear-gradient(90deg, #f43f5e 0%, #ec4899 55%, #f472b6 100%);
        transition: width 1.5s cubic-bezier(.22,.9,.3,1);
        position: relative;
    }
    .kg-bar-fill::after {
        content: '';
        position: absolute;
        inset: 0;
        background: linear-gradient(90deg, transparent, rgba(255,255,255,0.55), transparent);
        transform: translateX(-100%);
        animation: vmBarShine 2.6s ease-in-out infinite;
    }

    .kg-btn-gold {
        background: linear-gradient(180deg, #fde68a 0%, #f59e0b 45%, #d97706 100%);
        color: #78350f;
        border: 2px solid #fef3c7;
        box-shadow: 0 8px 20px rgba(217, 119, 6, 0.32);
        transition: transform .2s ease, box-shadow .2s ease;
    }
    .kg-btn-gold:hover { transform: translateY(-2px); box-shadow: 0 12px 24px rgba(217, 119, 6, 0.42); }

    .kg-btn-pink {
        background: linear-gradient(180deg, #f472b6 0%, #ec4899 45%, #be185d 100%);
        color: #ffffff;
        border: 2px solid #fbcfe8;
        box-shadow: 0 8px 20px rgba(219, 39, 119, 0.38);
        transition: transform .2s ease, box-shadow .2s ease;
    }
    .kg-btn-pink:hover { transform: translateY(-2px); box-shadow: 0 12px 26px rgba(219, 39, 119, 0.5); }

    /* Story card below Vibe Meter */
    .vm-story-card {
        position: relative;
        border-radius: 1.75rem;
        background: rgba(255, 255, 255, 0.96);
        box-shadow: 0 20px 50px rgba(190, 24, 93, 0.16);
        border: 2px solid #fbcfe8;
    }
    .vm-para {
        position: relative;
        transition: transform .3s ease, box-shadow .3s ease;
        border-left: 4px solid #f472b6;
    }
    .vm-para:hover {
        transform: translateX(4px);
        box-shadow: 0 10px 24px rgba(224,36,94,.12);
        border-left-color: #e0245e;
    }
    .vm-para-num {
        position: absolute;
        top: -11px;
        left: -11px;
        width: 26px;
        height: 26px;
        border-radius: 9999px;
        background: linear-gradient(135deg,#e0245e,#b81b5c);
        color: #fff;
        font-size: 11px;
        font-weight: 900;
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 4px 10px rgba(224,36,94,.35);
    }

    @keyframes vmHeartbeat { 0%,100% { transform: scale(1); } 15% { transform: scale(1.08); } 30% { transform: scale(1); } 45% { transform: scale(1.05); } }
    @keyframes vmPulseSoft { 0%,100% { transform: scale(1); opacity: .75; } 50% { transform: scale(1.1); opacity: 1; } }
    @keyframes vmBarShine { 0% { transform: translateX(-100%); } 60%,100% { transform: translateX(100%); } }
</style>

<div id="vmHearts" aria-hidden="true"></div>
<div id="vmConfetti" aria-hidden="true"></div>

<div class="vm-page min-h-screen py-4 sm:py-10 px-2.5 sm:px-6 lg:px-8" id="vmPage">
    <div class="relative mx-auto max-w-3xl space-y-6 sm:space-y-8" style="z-index: 2;">

        {{-- Top Navigation Bar --}}
        <div class="flex items-center justify-between gap-2">
            <a href="{{ route('games.hub', $partner->id) }}" class="inline-flex items-center gap-1 rounded-full bg-white/80 backdrop-blur px-3 sm:px-4 py-1.5 text-[11px] sm:text-sm font-bold text-pink-700 shadow-sm hover:bg-white transition truncate">
                ← Back to Games Hub
            </a>
            <span class="shrink-0 rounded-full px-3 sm:px-4 py-1.5 text-[11px] sm:text-xs font-black text-white shadow-md" style="background: linear-gradient(135deg, #E0245E, #B81B5C)">
                ❤️ VIBE METER
            </span>
        </div>

        {{-- ====================================================================
             1) KAADHAL GAMES VIBE METER CARD (MATCHES UPLOADED IMAGE 2)
             ==================================================================== --}}
        <div class="kg-poster">

            {{-- Header: ❤️ Kaadhal Games ✨ --}}
            <div class="relative z-10 flex items-center justify-center gap-2 mb-4 sm:mb-5">
                <span class="text-xl sm:text-3xl drop-shadow">❤️</span>
                <h1 class="kg-script-title">Kaadhal Games</h1>
                <span class="text-lg sm:text-2xl drop-shadow">✨</span>
            </div>

            {{-- Couple Circular Photos + Neon Heart Bridge --}}
            <div class="relative z-10 flex items-start justify-between gap-1 sm:gap-4 px-0.5 sm:px-4">
                {{-- Member 1 (Current User) --}}
                <div class="flex flex-col items-center text-center flex-1 min-w-0">
                    <div class="kg-avatar-ring">
                        <img src="{{ $resolvedUserPhoto }}"
                             alt="{{ $user->name }}"
                             onerror="this.onerror=null;this.src='{{ $userDefaultAvatar }}';">
                    </div>
                    <div class="kg-name-pill" title="{{ $user->name }}">
                        <span class="shrink-0">♡</span>
                        <span class="truncate">{{ \Illuminate\Support\Str::words($user->name, 2, '') }}</span>
                    </div>
                    @if(!empty($userLocation))
                    <div class="mt-1.5 flex items-center justify-center gap-0.5 sm:gap-1 text-[10px] sm:text-xs font-bold text-pink-950/80 w-full px-0.5">
                        <span class="text-rose-600 shrink-0">📍</span>
                        <span class="truncate max-w-[95px] sm:max-w-[135px]">{{ $userLocation }}</span>
                    </div>
                    @endif
                </div>

                {{-- Center Glowing Neon Heart Wave --}}
                <div class="kg-heart-bridge pt-4 sm:pt-8">
                    <div class="kg-neon-heart">
                        <svg viewBox="0 0 64 64" class="w-8 h-8 sm:w-12 sm:h-12">
                            <defs>
                                <linearGradient id="neonHeartGrad" x1="0%" y1="0%" x2="100%" y2="100%">
                                    <stop offset="0%" stop-color="#ff2e93"/>
                                    <stop offset="50%" stop-color="#ffffff"/>
                                    <stop offset="100%" stop-color="#38bdf8"/>
                                </linearGradient>
                            </defs>
                            <path d="M32 54 C32 54 10 38 10 22 C10 13 17 8 24 8 C28.5 8 32 11 32 14 C32 11 35.5 8 40 8 C47 8 54 13 54 22 C54 38 32 54 32 54 Z"
                                  fill="rgba(244, 63, 140, 0.25)"
                                  stroke="url(#neonHeartGrad)"
                                  stroke-width="3.5"
                                  stroke-linecap="round"
                                  stroke-linejoin="round"/>
                        </svg>
                    </div>
                </div>

                {{-- Member 2 (Partner) --}}
                <div class="flex flex-col items-center text-center flex-1 min-w-0">
                    <div class="kg-avatar-ring">
                        <img src="{{ $resolvedPartnerPhoto }}"
                             alt="{{ $partner->name }}"
                             onerror="this.onerror=null;this.src='{{ $partnerDefaultAvatar }}';">
                    </div>
                    <div class="kg-name-pill" title="{{ $partner->name }}">
                        <span class="shrink-0">♡</span>
                        <span class="truncate">{{ \Illuminate\Support\Str::words($partner->name, 2, '') }}</span>
                    </div>
                    @if(!empty($partnerLocation))
                    <div class="mt-1.5 flex items-center justify-center gap-0.5 sm:gap-1 text-[10px] sm:text-xs font-bold text-pink-950/80 w-full px-0.5">
                        <span class="text-rose-600 shrink-0">📍</span>
                        <span class="truncate max-w-[95px] sm:max-w-[135px]">{{ $partnerLocation }}</span>
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
                            <linearGradient id="kgRingGrad" x1="0%" y1="0%" x2="100%" y2="100%">
                                <stop offset="0%" stop-color="#ff2e93"/>
                                <stop offset="50%" stop-color="#c084fc"/>
                                <stop offset="100%" stop-color="#38bdf8"/>
                            </linearGradient>
                        </defs>
                        {{-- Outer decorative dashed ticks --}}
                        <circle cx="100" cy="100" r="92" fill="none" stroke="rgba(255,255,255,0.65)" stroke-width="2" stroke-dasharray="3 6"></circle>
                        {{-- Background track --}}
                        <circle cx="100" cy="100" r="80" fill="none" stroke="rgba(255,255,255,0.45)" stroke-width="14"></circle>
                        {{-- Animated neon foreground arc (2 * PI * 80 = 502.65) --}}
                        <circle id="vmRingFg"
                                cx="100" cy="100" r="80"
                                fill="none"
                                stroke="url(#kgRingGrad)"
                                stroke-width="14"
                                stroke-linecap="round"
                                stroke-dasharray="502.65"
                                stroke-dashoffset="502.65"
                                data-pct="{{ $pct }}"></circle>
                    </svg>
                    <div class="kg-meter-inner">
                        <div class="kg-pct-text">
                            <span class="vm-count" data-count="{{ $pct }}">0</span>%
                        </div>
                        <div class="kg-super-pill">
                            {{ $matchBadgeText }}
                        </div>
                        <span class="text-amber-400 text-xs mt-1">✨</span>
                    </div>
                </div>
            </div>

            {{-- ✨ Compatibility Details White Card --}}
            <div class="kg-details-card space-y-3.5 sm:space-y-4">
                <div class="flex items-center justify-center gap-1.5 sm:gap-2 border-b border-pink-100 pb-2">
                    <span class="text-amber-400 text-sm sm:text-base">✨</span>
                    <h2 class="text-sm sm:text-lg font-black text-pink-950 tracking-tight">Compatibility Details</h2>
                    <span class="text-amber-400 text-sm sm:text-base">✨</span>
                </div>

                {{-- 7 Compatibility Progress Bars --}}
                <div class="space-y-2.5">
                    @foreach($bars as $bar)
                        <div class="grid grid-cols-12 items-center gap-1.5 sm:gap-3 text-[11px] sm:text-sm">
                            <div class="col-span-5 sm:col-span-4 font-extrabold text-pink-950 truncate">
                                {{ $bar['label'] }}
                            </div>
                            <div class="col-span-5 sm:col-span-6">
                                <div class="kg-bar-track">
                                    <div class="kg-bar-fill vm-bar-fill" data-w="{{ max(0, min(100, (int) $bar['score'])) }}"></div>
                                </div>
                            </div>
                            <div class="col-span-2 sm:col-span-2 text-right font-black text-pink-900">
                                <span class="vm-count" data-count="{{ (int) $bar['score'] }}">0</span>%
                            </div>
                        </div>
                    @endforeach

                    @php
                        $webPrefScore = isset($preferenceMatch['score']) ? (int) $preferenceMatch['score'] : null;
                        $webRasiReady = ($rasiPorutham['status'] ?? '') === 'ready';
                        $webRasiScore = ($webRasiReady && isset($rasiPorutham['score']) && $rasiPorutham['score'] !== null) ? (int) $rasiPorutham['score'] : null;
                    @endphp

                    {{-- Preference Match Progress Bar (Same Pink Bar Style as Games) --}}
                    <div class="grid grid-cols-12 items-center gap-1.5 sm:gap-3 text-[11px] sm:text-sm">
                        <div class="col-span-5 sm:col-span-4 font-extrabold text-pink-950 truncate">
                            Preference Match
                        </div>
                        <div class="col-span-5 sm:col-span-6">
                            <div class="kg-bar-track">
                                <div class="kg-bar-fill vm-bar-fill" data-w="{{ $webPrefScore !== null ? max(0, min(100, (int) $webPrefScore)) : 0 }}"></div>
                            </div>
                        </div>
                        <div class="col-span-2 sm:col-span-2 text-right font-black text-pink-900">
                            @if($webPrefScore !== null)
                                <span class="vm-count" data-count="{{ (int) $webPrefScore }}">0</span>%
                            @else
                                <span class="text-xs text-gray-400 font-bold">—</span>
                            @endif
                        </div>
                    </div>

                    {{-- Rasi Porutham Progress Bar (Same Pink Bar Style as Games) --}}
                    <div class="grid grid-cols-12 items-center gap-1.5 sm:gap-3 text-[11px] sm:text-sm">
                        <div class="col-span-5 sm:col-span-4 font-extrabold text-pink-950 truncate">
                            <span>Rasi Porutham</span>
                            @if($webRasiScore === null)
                                <span class="text-[9px] text-pink-500 font-normal block leading-tight">விவரங்கள் தேவை</span>
                            @endif
                        </div>
                        <div class="col-span-5 sm:col-span-6">
                            <div class="kg-bar-track">
                                <div class="kg-bar-fill vm-bar-fill" data-w="{{ $webRasiScore !== null ? max(0, min(100, (int) $webRasiScore)) : 0 }}"></div>
                            </div>
                        </div>
                        <div class="col-span-2 sm:col-span-2 text-right font-black text-pink-900">
                            @if($webRasiScore !== null)
                                <span class="vm-count" data-count="{{ (int) $webRasiScore }}">0</span>%
                            @else
                                <span class="text-xs text-pink-600 font-bold" title="விவரங்கள் தேவை">—</span>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- Summary Quote Box --}}
                <div class="rounded-2xl bg-pink-50/90 border border-pink-200/80 px-3 sm:px-4 py-2.5 sm:py-3 text-center">
                    <p class="text-[11px] sm:text-sm font-bold text-pink-950 leading-relaxed">
                        {{ $compatSummaryText }}
                    </p>
                </div>

                {{-- Action Buttons: Play Again & Share Result --}}
                <div class="grid grid-cols-2 gap-2 sm:gap-3 pt-1">
                    <a href="{{ route('games.hub', $partner->id) }}"
                       class="kg-btn-gold rounded-full py-2 sm:py-2.5 px-2.5 sm:px-4 text-center text-[11px] sm:text-sm font-black tracking-wide flex items-center justify-center gap-1 sm:gap-1.5 whitespace-nowrap">
                        <span>🎮</span>
                        <span>Play Again</span>
                    </a>
                    <button type="button"
                            onclick="shareVibeResult()"
                            class="kg-btn-pink rounded-full py-2 sm:py-2.5 px-2.5 sm:px-4 text-center text-[11px] sm:text-sm font-black tracking-wide flex items-center justify-center gap-1 sm:gap-1.5 cursor-pointer whitespace-nowrap">
                        <span>💌</span>
                        <span id="shareBtnLabel">Share Result</span>
                    </button>
                </div>
            </div>
        </div>

        {{-- ====================================================================
             2) PERSONALIZED KOODAL VIBE STORY & PREFERENCE MATCH STORY
             ==================================================================== --}}
        @if(!empty($story))
        <div class="vm-story-card p-4 sm:p-8 space-y-5 sm:space-y-6">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-pink-100 pb-4">
                <div class="space-y-1">
                    <span class="inline-block rounded-full bg-pink-100 px-3 py-1 text-xs font-black uppercase tracking-wider text-pink-700">
                        📖 உங்க Koodal Vibe &amp; Chemistry Story
                    </span>
                    <h2 class="text-xl sm:text-2xl font-black text-gray-900">
                        {{ $story['title'] }}
                    </h2>
                    <p class="text-xs sm:text-sm font-bold text-pink-600">
                        {{ $story['tagline'] }}
                    </p>
                </div>
            </div>

            {{-- Story Paragraphs (Chemistry Story) --}}
            <div class="space-y-4 text-gray-800 text-sm sm:text-base leading-relaxed pl-2">
                @foreach($story['paragraphs'] as $i => $para)
                    <div class="vm-para p-4 sm:p-5 rounded-2xl bg-gradient-to-r from-pink-50/80 via-rose-50/40 to-pink-50/60 border border-pink-100 shadow-xs">
                        <span class="vm-para-num">{{ $i + 1 }}</span>
                        @php
                            $rawPara = str_replace(['("', '")', '"[💬', '" [💬', 'Now]"', 'Now] "'], ['(', ')', '[💬', '[💬', 'Now]', 'Now]'], $para);
                            $formattedPara = e($rawPara);
                            $formattedPara = str_replace(['&quot;[', ']&quot;', '(&quot;', '&quot;)'], ['[', ']', '(', ')'], $formattedPara);
                            $formattedPara = preg_replace('/\*\*(.*?)\*\*/', '<strong class="font-black text-pink-900">$1</strong>', $formattedPara);
                            $formattedPara = preg_replace('/\[(.*?)\]\((.*?)\)/', '<a href="$2" class="inline-flex items-center gap-1 font-black text-white bg-gradient-to-r from-pink-500 to-rose-600 px-3 py-1 rounded-full shadow-xs hover:opacity-90 ml-1 text-xs">$1</a>', $formattedPara);
                        @endphp
                        {!! nl2br($formattedPara) !!}
                    </div>
                @endforeach
            </div>

            @if(!$anyGamePlayed)
                <div class="rounded-2xl border border-pink-200 bg-pink-50/60 p-4 text-center space-y-2">
                    <p class="text-xs sm:text-sm font-bold text-pink-800">
                        🎮 இன்னும் 4 Koodal Games விளையாடி உங்க Compatibility, Memory, Observation &amp; Teamwork scores-ஐயும் இந்த Vibe Story-ல் சேர்க்கலாம்!
                    </p>
                    <div class="flex flex-wrap items-center justify-center gap-2 pt-1">
                        <a href="{{ route('games.sync', $partner->id) }}" class="inline-flex items-center gap-1.5 rounded-xl px-4 py-2 text-xs font-black text-white shadow" style="background: linear-gradient(135deg, #E0245E, #B81B5C)">
                            🎯 Play Compatibility Test →
                        </a>
                        <a href="{{ route('games.hub', $partner->id) }}" class="inline-flex items-center gap-1.5 rounded-xl border border-pink-200 bg-white px-4 py-2 text-xs font-bold text-pink-700 hover:bg-pink-50">
                            🎮 View All 4 Games
                        </a>
                    </div>
                </div>
            @endif

            {{-- Dynamic CTA from Story --}}
            @if(!empty($story['call_to_action']))
            <div class="pt-2 flex flex-col sm:flex-row items-center justify-between gap-4 p-5 rounded-2xl bg-gradient-to-r from-rose-50 to-pink-50 border border-pink-200">
                <div class="text-left">
                    <span class="text-xs font-extrabold text-pink-600 uppercase tracking-wider block">Recommended Next Step:</span>
                    <span class="text-sm font-black text-gray-900">Connect with {{ $partner->name }} and take your journey forward!</span>
                </div>
                <a href="{{ $story['cta_url'] ?? route('messages.index') }}" class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-6 py-3 rounded-xl font-black text-sm text-white shadow-lg transition hover:scale-105" style="background: linear-gradient(135deg, #E0245E, #B81B5C)">
                    {{ $story['call_to_action'] }} →
                </a>
            </div>
            @endif
        </div>
        @endif

    </div>
</div>

<script>
function shareVibeResult() {
    var shareText = "💖 Kaadhal Games Vibe Meter: {{ addslashes($user->name) }} & {{ addslashes($partner->name) }} scored {{ $pct }}% ({{ $matchBadgeText }}) on Koodal Matrimony!";
    var shareUrl = window.location.href;
    if (navigator.share) {
        navigator.share({
            title: 'Kaadhal Games Vibe Meter',
            text: shareText,
            url: shareUrl
        }).catch(function () {});
    } else if (navigator.clipboard) {
        navigator.clipboard.writeText(shareText + ' ' + shareUrl).then(function () {
            var lbl = document.getElementById('shareBtnLabel');
            if (lbl) {
                lbl.textContent = 'Copied Link! ✓';
                setTimeout(function () { lbl.textContent = 'Share Result'; }, 2200);
            }
        });
    }
}

(function () {
    var page = document.getElementById('vmPage');
    if (!page) return;
    var reduce = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    /* ---------- Count-up numbers ---------- */
    function countUp(el) {
        if (el.dataset.done) return;
        el.dataset.done = '1';
        var target = parseFloat(el.dataset.count) || 0;
        var isInt = Math.floor(target) === target;
        if (reduce) { el.textContent = isInt ? target : target.toFixed(1); return; }
        var dur = 1500, start = null;
        function tick(ts) {
            if (!start) start = ts;
            var p = Math.min((ts - start) / dur, 1);
            var eased = 1 - Math.pow(1 - p, 3);
            var val = target * eased;
            el.textContent = isInt ? Math.round(val) : val.toFixed(1);
            if (p < 1) requestAnimationFrame(tick);
        }
        requestAnimationFrame(tick);
    }

    /* ---------- Score ring (2 * PI * 80 = 502.65) ---------- */
    var ring = document.getElementById('vmRingFg');
    if (ring) {
        var pct = parseFloat(ring.dataset.pct) || 0;
        var circ = 502.65;
        setTimeout(function () {
            ring.style.transition = 'stroke-dashoffset 1.8s cubic-bezier(.22,.9,.3,1)';
            ring.style.strokeDashoffset = circ - (circ * pct / 100);
        }, 180);
    }

    /* ---------- Animate all counters and bars ---------- */
    setTimeout(function () {
        page.querySelectorAll('.vm-count').forEach(countUp);
        page.querySelectorAll('.vm-bar-fill').forEach(function (b) {
            b.style.width = (b.dataset.w || 0) + '%';
        });
    }, 220);

    if (reduce) return;

    /* ---------- Floating hearts ---------- */
    var heartsBox = document.getElementById('vmHearts');
    var emojis = ['❤️', '💕', '💖', '💗', '✨'];
    function spawnHeart() {
        if (!heartsBox || document.hidden) return;
        var h = document.createElement('span');
        h.className = 'vm-heart';
        h.textContent = emojis[Math.floor(Math.random() * emojis.length)];
        h.style.left = Math.random() * 100 + 'vw';
        h.style.fontSize = (13 + Math.random() * 16) + 'px';
        var d = 8 + Math.random() * 6;
        h.style.animationDuration = d + 's';
        heartsBox.appendChild(h);
        setTimeout(function () { h.remove(); }, d * 1000 + 200);
    }
    for (var i = 0; i < 5; i++) setTimeout(spawnHeart, i * 400);
    setInterval(spawnHeart, 1300);

    /* ---------- Confetti burst on load ---------- */
    var cBox = document.getElementById('vmConfetti');
    var colors = ['#E0245E', '#F9A8D4', '#FBBF24', '#A855F7', '#38BDF8', '#FFFFFF'];
    function burst(n) {
        for (var i = 0; i < n; i++) {
            var c = document.createElement('span');
            c.className = 'vm-confetti';
            c.style.left = Math.random() * 100 + 'vw';
            c.style.background = colors[Math.floor(Math.random() * colors.length)];
            c.style.borderRadius = Math.random() > .5 ? '50%' : '2px';
            var d = 2.4 + Math.random() * 2.2;
            c.style.animationDuration = d + 's';
            c.style.animationDelay = (Math.random() * 0.8) + 's';
            cBox.appendChild(c);
            (function (el, t) { setTimeout(function () { el.remove(); }, t); })(c, (d + 1.1) * 1000);
        }
    }
    setTimeout(function () { burst({{ $pct >= 70 ? 70 : 35 }}); }, 900);
})();
</script>
@endsection