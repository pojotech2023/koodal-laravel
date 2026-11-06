@extends('layouts.app')

{{-- Unconditional, same rule as layouts.member and for the same reason
     (docs/06-admin-panel-guide.md §1) — nothing behind the staff sidebar is
     ever indexed, regardless of what an individual view does or forgets to
     set. --}}
@section('robots', 'noindex, nofollow')

@section('flash_inline', '1')

@push('meta')
    @vite('resources/js/admin.js')
    <style>
        /* Koodal Admin Royal Rose Pink Theme (Sidebar & Dashboard Hero Card) */
        .bg-ink-gradient {
            background-image: linear-gradient(160deg, #700B33 0%, #4A0520 50%, #20020E 100%) !important;
        }

        /* Gold Shimmer & Glitter Pulse Keyframes */
        @keyframes adminGoldButtonShimmer {
            0% {
                transform: translateX(-140%) skewX(-22deg);
            }
            55%, 100% {
                transform: translateX(240%) skewX(-22deg);
            }
        }

        @keyframes adminGoldBorderPulse {
            0%, 100% {
                border-color: #F5CB66;
                box-shadow:
                    0 0 12px rgba(245, 203, 102, 0.6),
                    0 8px 20px -6px rgba(224, 36, 94, 0.7),
                    inset 0 0 8px rgba(255, 215, 0, 0.25);
            }
            50% {
                border-color: #FFE885;
                box-shadow:
                    0 0 22px rgba(255, 215, 0, 0.92),
                    0 10px 26px -4px rgba(224, 36, 94, 0.85),
                    inset 0 0 14px rgba(255, 232, 133, 0.45);
            }
        }

        /* Sidebar Menu Links — Base, Hover, and Active Page Highlight */
        aside .side-link {
            position: relative;
            overflow: hidden;
            border: 1.5px solid transparent;
            color: #FCE7F3;
            transition: all 0.22s cubic-bezier(0.16, 1, 0.3, 1);
        }

        aside .side-link:hover {
            background: linear-gradient(135deg, rgba(224, 36, 94, 0.9) 0%, rgba(184, 27, 92, 0.9) 55%, rgba(142, 24, 102, 0.9) 100%) !important;
            color: #FFFFFF !important;
            border-color: #F5CB66 !important;
            box-shadow:
                0 0 15px rgba(245, 203, 102, 0.65),
                0 6px 18px -6px rgba(224, 36, 94, 0.7),
                inset 0 0 10px rgba(255, 215, 0, 0.28) !important;
            transform: translateX(3px);
        }

        aside .side-link.is-active {
            background: linear-gradient(135deg, #E0245E 0%, #B81B5C 52%, #8E1866 100%) !important;
            color: #FFFFFF !important;
            font-weight: 700;
            border: 1.5px solid #F5CB66 !important;
            animation: adminGoldBorderPulse 2.2s ease-in-out infinite;
        }

        aside .side-link.is-active svg,
        aside .side-link:hover svg {
            color: #FFE885 !important;
            filter: drop-shadow(0 0 5px rgba(255, 215, 0, 0.65));
        }

        /* Shimmering Light Sweep ("button minura mathiri") on Active & Hovered Menu Items + Upgrade Button */
        aside .side-link.is-active::after,
        aside .side-link:hover::after,
        .admin-upgrade-btn::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 48%;
            height: 100%;
            background: linear-gradient(
                90deg,
                rgba(255, 255, 255, 0) 0%,
                rgba(255, 240, 175, 0.55) 50%,
                rgba(255, 255, 255, 0) 100%
            );
            pointer-events: none;
            animation: adminGoldButtonShimmer 2.5s ease-in-out infinite;
        }

        .admin-upgrade-btn {
            position: relative;
            overflow: hidden;
            border: 1.5px solid #F5CB66 !important;
            animation: adminGoldBorderPulse 2.6s ease-in-out infinite;
        }
    </style>
@endpush

@php
    $staff = Auth::guard('web')->user();
    $staffInitial = strtoupper(mb_substr($staff?->name ?? '?', 0, 1));
    $platformLimit = \App\Models\MatrimonyLimit::current();
@endphp

@section('content')
    <div class="flex min-h-screen" style="background-color:var(--bg-page)" x-data="{ drawer: false }" @keydown.escape.window="drawer = false">

        {{-- Mobile drawer backdrop --}}
        <div x-cloak x-show="drawer" x-transition.opacity @click="drawer = false" class="fixed inset-0 z-40 bg-ink-900/60 backdrop-blur-sm md:hidden"></div>

        {{-- Sidebar: sticky on desktop, slide-in drawer on mobile. Its own
             overflow keeps the logo pinned above a scrollable nav, so a long
             nav never drags the whole document. --}}
        <aside
            class="fixed inset-y-0 left-0 z-50 flex w-72 shrink-0 -translate-x-full flex-col bg-ink-gradient text-ink-100 shadow-xl transition-transform duration-300 md:sticky md:top-0 md:h-screen md:w-64 md:translate-x-0 md:shadow-none"
            :class="drawer ? '!translate-x-0' : ''"
        >
            <div class="relative shrink-0 border-b border-white/10 px-5 py-5">
                <a href="{{ route('admin.dashboard') }}" class="block">
                    <span class="flex justify-center"><x-logo tone="light" size="h-24" /></span>
                    <span class="mt-3 block text-center text-[10px] font-bold uppercase tracking-[0.2em] text-primary-300">Admin panel</span>
                </a>
                <button type="button" @click="drawer = false" class="absolute right-3 top-4 grid h-9 w-9 place-items-center rounded-lg text-ink-200 hover:bg-white/10 md:hidden" aria-label="Close menu">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 6l12 12M18 6L6 18" /></svg>
                </button>
            </div>

            {{-- One definition (config('matrimony.admin_nav')) — label, route,
                 icon, permission. @can is a courtesy: every route behind
                 each link carries its own $this->authorize() too. --}}
            <nav aria-label="Admin" class="flex-1 space-y-1.5 overflow-y-auto px-3 py-4">
                <p class="px-3 pb-2 text-[10px] font-bold uppercase tracking-[0.18em] text-pink-200/80">Menu</p>
                @foreach (config('matrimony.admin_nav') as $item)
                    @can($item['permission'])
                        <a
                            {{-- `params` is optional and only the two
                                 `{type}`-scoped modules (master data,
                                 locations) carry it — their index route needs
                                 a segment, and the nav has to land somewhere
                                 rather than on a route it cannot build. --}}
                            href="{{ route($item['route'], $item['params'] ?? []) }}"
                            class="side-link {{ request()->routeIs($item['route']) || request()->routeIs($item['route'].'.*') ? 'is-active' : '' }}"
                        >
                            <x-admin.icon :name="$item['icon']" class="h-5 w-5 shrink-0" />
                            {{ $item['label'] }}
                        </a>
                    @endcan
                @endforeach
            </nav>

            <div class="shrink-0 space-y-3 border-t border-white/10 p-4">
                <div class="rounded-2xl border border-white/10 bg-white/5 p-3.5">
                    <div class="flex items-center justify-between text-xs">
                        <span class="text-pink-100/80">Current plan</span>
                        <span class="rounded-full bg-gold-gradient px-2.5 py-0.5 text-[10px] font-extrabold uppercase tracking-wide text-ink-900">{{ (isset($platformLimit) && $platformLimit->isUnlimited()) ? 'UNLIMITED' : ($platformLimit->plan_type ?? 'UNLIMITED') }}</span>
                    </div>
                    <a href="{{ route('admin.upgrade.index') }}"
                       class="admin-upgrade-btn mt-3 flex w-full items-center justify-center gap-2 rounded-xl bg-brand-gradient px-3 py-2.5 text-xs font-bold uppercase tracking-wider text-white shadow-glow transition-all hover:-translate-y-0.5 hover:shadow-glow-lg">
                        <svg class="h-4 w-4 text-accent-200" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" /></svg>
                        Upgrade plan
                    </a>
                </div>
            </div>
        </aside>

        <div class="flex min-w-0 flex-1 flex-col">
            <header class="sticky top-0 z-30 flex items-center justify-between gap-3 nav-surface px-4 py-3 shadow-sm md:px-6">
                <div class="flex items-center gap-3">
                    <button type="button" @click="drawer = true" class="grid h-10 w-10 place-items-center rounded-xl border bg-white text-ink-700 shadow-sm md:hidden" style="border-color:var(--border-default)" aria-label="Open menu">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4 7h16M4 12h16M4 17h16" /></svg>
                    </button>
                    <div class="leading-tight">
                        <p class="text-xs font-semibold uppercase tracking-wider text-primary-600">{{ now()->format('l, j F') }}</p>
                        <p class="text-sm font-bold text-ink-800">Welcome back, {{ \Illuminate\Support\Str::of($staff?->name ?? '')->before(' ') }} 👋</p>
                    </div>
                </div>

                <div class="flex items-center gap-2 sm:gap-3">
                    <a href="{{ route('home') }}" target="_blank" rel="noopener" class="hidden items-center gap-1.5 rounded-xl border bg-white px-3 py-2 text-xs font-semibold text-ink-600 shadow-sm transition-colors hover:border-primary-300 hover:text-primary-700 sm:inline-flex" style="border-color:var(--border-default)">
                        View site
                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 003 8.25v10.5A2.25 2.25 0 005.25 21h10.5A2.25 2.25 0 0018 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25" /></svg>
                    </a>

                    <div class="flex items-center gap-2.5 rounded-full border bg-white py-1 pl-1 pr-3 shadow-sm" style="border-color:var(--border-default)">
                        <span class="grid h-8 w-8 place-items-center rounded-full bg-brand-gradient text-sm font-bold text-white">{{ $staffInitial }}</span>
                        <span class="hidden leading-tight sm:block">
                            <span class="block max-w-[9rem] truncate text-xs font-bold text-ink-800">{{ $staff?->name }}</span>
                            <span class="block text-[10px] font-semibold text-ink-400">{{ $staff?->role?->label }}</span>
                        </span>
                    </div>

                    <form method="POST" action="{{ route('admin.logout') }}">
                        @csrf
                        <button type="submit" class="rounded-xl border px-3 py-2 text-xs font-semibold text-primary-700 transition-colors hover:bg-primary-50" style="border-color:#F3B5CB">
                            Log out
                        </button>
                    </form>
                </div>
            </header>

            <main class="flex-1 p-4 md:p-8">
                <x-flash />
                @yield('admin-body')
            </main>
        </div>
    </div>
@endsection
