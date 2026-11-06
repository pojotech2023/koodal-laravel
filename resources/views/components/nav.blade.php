{{-- Public site nav. The member area gets its own <x-member-nav> — the two
     diverge as soon as there's a dashboard link to add, so they were never
     going to stay one component with an `if` in it. --}}
@php
    $links = [
        ['route' => 'browse', 'label' => 'Browse profiles'],
        ['route' => 'membership.plans', 'label' => 'Plans'],
        ['route' => 'page.stories', 'label' => 'Success stories'],
        ['route' => 'page.contact', 'label' => 'Contact'],
    ];
@endphp

<header x-data="{ open: false, scrolled: false }" @scroll.window="scrolled = window.scrollY > 8" class="sticky top-0 z-40">
    <div class="nav-surface relative transition-shadow duration-300"
         :class="scrolled ? 'shadow-lg' : 'shadow-sm'">
        <div class="mx-auto flex max-w-7xl items-center justify-between gap-4 px-4 py-1.5 sm:px-6">
            <a href="{{ route('home') }}" class="group" aria-label="{{ config('app.name') }} home">
                <x-logo class="transition-transform duration-300 group-hover:scale-[1.02]" />
            </a>

            <nav class="hidden items-center gap-1 lg:flex" aria-label="Primary">
                @foreach ($links as $link)
                    <a href="{{ route($link['route']) }}"
                       class="rounded-full px-4 py-2 text-sm font-semibold transition-colors {{ request()->routeIs($link['route']) ? 'bg-primary-50 text-primary-700' : 'text-ink-600 hover:bg-primary-50 hover:text-primary-700' }}">
                        {{ $link['label'] }}
                    </a>
                @endforeach
            </nav>

            <div class="hidden items-center gap-2 lg:flex">
                @auth('web')
                    <a href="{{ route('dashboard') }}" class="btn-outline !py-2">Dashboard</a>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="rounded-xl px-4 py-2 text-sm font-semibold text-ink-600 transition-colors hover:bg-primary-50 hover:text-primary-700">
                            Log out
                        </button>
                    </form>
                @else
                    <a href="{{ route('login') }}" class="rounded-xl px-4 py-2 text-sm font-semibold text-ink-600 transition-colors hover:bg-primary-50 hover:text-primary-700">Log in</a>
                    <a href="{{ route('register') }}" class="btn-primary !py-2.5">
                        Join free
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2.2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" /></svg>
                    </a>
                @endauth
            </div>

            <button type="button" @click="open = !open" class="grid h-11 w-11 place-items-center rounded-xl border bg-white text-ink-700 shadow-sm lg:hidden"
                    style="border-color:var(--border-default)" :aria-expanded="open" aria-label="Toggle menu">
                <svg x-show="!open" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4 7h16M4 12h16M4 17h16" /></svg>
                <svg x-show="open" x-cloak class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 6l12 12M18 6L6 18" /></svg>
            </button>
        </div>
    </div>

    {{-- Mobile drawer --}}
    <nav x-cloak x-show="open" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 -translate-y-2" x-transition:enter-end="opacity-100 translate-y-0"
         @click.outside="open = false"
         class="absolute inset-x-0 top-full border-b bg-white/95 p-4 shadow-lg backdrop-blur-xl lg:hidden" style="border-color:var(--border-default)" aria-label="Primary">
        <div class="mx-auto flex max-w-7xl flex-col gap-1">
            @foreach ($links as $link)
                <a href="{{ route($link['route']) }}" class="rounded-xl px-4 py-3 text-sm font-semibold {{ request()->routeIs($link['route']) ? 'bg-primary-50 text-primary-700' : 'text-ink-700 hover:bg-primary-50' }}">{{ $link['label'] }}</a>
            @endforeach

            <div class="mt-2 grid grid-cols-2 gap-2 border-t pt-4" style="border-color:var(--border-default)">
                @auth('web')
                    <a href="{{ route('dashboard') }}" class="btn-primary col-span-2">Go to dashboard</a>
                    <form method="POST" action="{{ route('logout') }}" class="col-span-2">
                        @csrf
                        <button type="submit" class="btn-outline w-full">Log out</button>
                    </form>
                @else
                    <a href="{{ route('login') }}" class="btn-outline">Log in</a>
                    <a href="{{ route('register') }}" class="btn-primary">Join free</a>
                @endauth
            </div>
        </div>
    </nav>
</header>
