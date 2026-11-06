@php
    $navLinks = [
        ['route' => 'dashboard', 'active' => 'dashboard', 'label' => 'Dashboard'],
        ['route' => 'search', 'active' => 'search', 'label' => 'Search'],
        ['route' => 'matches.recommended', 'active' => 'matches.*', 'label' => 'Matches'],
        ['route' => 'interests.index', 'active' => 'interests.*', 'label' => 'Interests'],
        ['route' => 'shortlist.index', 'active' => 'shortlist.*', 'label' => 'Shortlist'],
        ['route' => 'contact-requests.index', 'active' => 'contact-requests.*', 'label' => 'Contact Requests'],
        ['route' => 'messages.index', 'active' => 'messages.*', 'label' => 'Messages'],
        ['route' => 'membership.index', 'active' => 'membership.*', 'label' => 'Membership'],
        ['route' => 'profile.show', 'active' => 'profile.show', 'label' => 'My profile'],
    ];
    $me = Auth::guard('web')->user();
    $initial = strtoupper(mb_substr($me?->name ?? '?', 0, 1));
@endphp

<header x-data="{ open: false }" class="sticky top-0 z-30">
    <div class="nav-surface relative shadow-sm">
        <div class="mx-auto flex max-w-7xl items-center justify-between gap-4 px-4 py-1.5 sm:px-6">
            <a href="{{ route('dashboard') }}" class="group shrink-0" aria-label="{{ config('app.name') }} dashboard">
                <x-logo class="transition-transform duration-300 group-hover:scale-[1.02]" />
            </a>

            <nav class="hidden items-center gap-0.5 xl:flex" aria-label="Member">
                @foreach ($navLinks as $link)
                    <a href="{{ route($link['route']) }}"
                       class="relative rounded-full px-3.5 py-2 text-sm font-semibold transition-all duration-150 {{ request()->routeIs($link['active']) ? 'bg-brand-gradient text-white shadow-glow' : 'text-ink-600 hover:bg-primary-50 hover:text-primary-700' }}">
                        {{ $link['label'] }}
                    </a>
                @endforeach
            </nav>

            <div class="flex items-center gap-2">
                <div class="hidden items-center gap-3 rounded-full border bg-white py-1 pl-1 pr-4 shadow-sm sm:flex" style="border-color:var(--border-default)">
                    <span class="grid h-8 w-8 place-items-center rounded-full bg-brand-gradient text-sm font-bold text-white">{{ $initial }}</span>
                    <span class="max-w-[9rem] truncate text-sm font-semibold text-ink-700">{{ $me?->name }}</span>
                </div>
                <form method="POST" action="{{ route('logout') }}" class="hidden sm:block">
                    @csrf
                    <button type="submit" class="rounded-xl px-3 py-2 text-sm font-semibold text-ink-600 transition-colors hover:bg-primary-50 hover:text-primary-700">Log out</button>
                </form>

                <button type="button" @click="open = !open" class="grid h-11 w-11 place-items-center rounded-xl border bg-white text-ink-700 shadow-sm xl:hidden"
                        style="border-color:var(--border-default)" :aria-expanded="open" aria-label="Toggle menu">
                    <svg x-show="!open" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4 7h16M4 12h16M4 17h16" /></svg>
                    <svg x-show="open" x-cloak class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 6l12 12M18 6L6 18" /></svg>
                </button>
            </div>
        </div>
    </div>

    <nav x-cloak x-show="open" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 -translate-y-2" x-transition:enter-end="opacity-100 translate-y-0"
         @click.outside="open = false"
         class="absolute inset-x-0 top-full border-b bg-white/95 p-4 shadow-lg backdrop-blur-xl xl:hidden" style="border-color:var(--border-default)" aria-label="Member">
        <div class="mx-auto grid max-w-7xl grid-cols-2 gap-2 sm:grid-cols-4">
            @foreach ($navLinks as $link)
                <a href="{{ route($link['route']) }}" class="rounded-xl px-4 py-3 text-center text-sm font-semibold {{ request()->routeIs($link['active']) ? 'bg-brand-gradient text-white shadow-glow' : 'bg-primary-50/60 text-ink-700 hover:bg-primary-50' }}">{{ $link['label'] }}</a>
            @endforeach
        </div>
        <form method="POST" action="{{ route('logout') }}" class="mx-auto mt-3 max-w-7xl">
            @csrf
            <button type="submit" class="btn-outline w-full">Log out{{ $me ? ' ('.$me->name.')' : '' }}</button>
        </form>
    </nav>
</header>
