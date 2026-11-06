@extends('layouts.member')

@php
    $tabs = [
        'recommended' => ['label' => 'Recommended', 'route' => 'matches.recommended'],
        'reference' => ['label' => 'Preference', 'route' => 'matches.reference'],
        'preference-match' => ['label' => 'Horoscope matches', 'route' => 'matches.preference-match'],
        'new' => ['label' => 'New', 'route' => 'matches.new'],
        'recently-active' => ['label' => 'Recently active', 'route' => 'matches.recently-active'],
        'mutual' => ['label' => 'Connections', 'route' => 'matches.mutual'],
        'near-me' => ['label' => 'Near me', 'route' => 'matches.near-me'],
        'caste-match' => ['label' => 'Caste matches', 'route' => 'matches.caste-match'],
    ];

    // Only set on the `preference-match` tab (Web\MatchController::preferenceMatch) —
    // the other tabs never render the filter block below.
    $astrologyFilters ??= ['raasi' => [], 'star' => []];
    $raasiOptions = collect(config('matrimony.astrology.raasis'))->map(fn ($name) => ['id' => $name, 'name' => $name])->all();
    $starOptions = collect(config('matrimony.astrology.nakshatras'))->map(fn ($name) => ['id' => $name, 'name' => $name])->all();

    // Only set on the `caste-match` tab (Web\MatchController::casteMatch).
    $casteFilters ??= ['community_id' => [], 'sub_community_id' => [], 'caste' => [], 'gothra' => []];
    $communityOptions ??= [];
    $subCommunityOptions ??= [];
    $casteOptions ??= [];
    $gothraOptions ??= [];
@endphp

@section('title', $tabs[$tab]['label'].' matches — '.config('app.name'))

@section('body')
    <div class="mx-auto max-w-6xl px-4 py-8">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h1 class="font-serif text-3xl font-bold text-ink-900">Matches</h1>
            <a href="{{ route('profile.wizard.step', 'preferences') }}" class="inline-flex items-center gap-1.5 rounded-md border border-primary-300 bg-primary-50 px-3.5 py-2 text-xs font-semibold text-primary-700 hover:bg-primary-100">
                <span>&#9881; Edit Partner Preference</span>
            </a>
        </div>

        <nav class="card mt-6 flex flex-wrap gap-1 p-1">
            @foreach ($tabs as $key => $meta)
                <a
                    href="{{ route($meta['route']) }}"
                    class="rounded-lg px-3 py-2 text-sm font-medium transition-colors duration-150 {{ $tab === $key ? 'bg-primary-500 text-white shadow-sm' : 'hover:bg-primary-50 hover:text-primary-600' }}"
                >
                    {{ $meta['label'] }}
                </a>
            @endforeach
        </nav>

        @if ($tab === 'reference')
            @php
                $prefs = $userPreferences ?? $viewer->preferences;
                $lookup = $prefMasterData ?? [];
                $chips = [];

                if ($prefs) {
                    if ($prefs->age_min || $prefs->age_max) {
                        $chips[] = 'Age: '.($prefs->age_min ?? '18').'–'.($prefs->age_max ?? '70').' yrs';
                    }
                    if ($prefs->height_min_cm || $prefs->height_max_cm) {
                        $chips[] = 'Height: '.($prefs->height_min_cm ?? '122').'–'.($prefs->height_max_cm ?? '213').' cm';
                    }
                    if ($prefs->weight_min_kg || $prefs->weight_max_kg) {
                        $chips[] = 'Weight: '.($prefs->weight_min_kg ?? '35').'–'.($prefs->weight_max_kg ?? '140').' kg';
                    }
                    if (!empty($prefs->marital_status_ids)) {
                        $names = collect($prefs->marital_status_ids)->map(fn($id) => $lookup['marital_statuses'][$id] ?? null)->filter()->implode(', ');
                        if ($names) $chips[] = 'Marital Status: '.$names;
                    }
                    if (!empty($prefs->occupation_ids)) {
                        $names = collect($prefs->occupation_ids)->map(fn($id) => $lookup['occupations'][$id] ?? null)->filter()->implode(', ');
                        if ($names) $chips[] = 'Job: '.$names;
                    }
                    if (!empty($prefs->income_range_ids)) {
                        $names = collect($prefs->income_range_ids)->map(fn($id) => $lookup['income_ranges'][$id] ?? null)->filter()->implode(', ');
                        if ($names) $chips[] = 'Salary: '.$names;
                    }
                    if (!empty($prefs->complexions)) {
                        $chips[] = 'Color: '.implode(', ', $prefs->complexions);
                    }
                    if (!empty($prefs->mother_tongue_ids)) {
                        $names = collect($prefs->mother_tongue_ids)->map(fn($id) => $lookup['mother_tongues'][$id] ?? null)->filter()->implode(', ');
                        if ($names) $chips[] = 'Mother Tongue: '.$names;
                    }
                    if (!empty($prefs->state_ids)) {
                        $names = collect($prefs->state_ids)->map(fn($id) => $lookup['states'][$id] ?? null)->filter()->implode(', ');
                        if ($names) $chips[] = 'State: '.$names;
                    }
                    if (!empty($prefs->raasis)) {
                        $chips[] = 'Rasi: '.implode(', ', $prefs->raasis);
                    }
                    if (!empty($prefs->stars)) {
                        $chips[] = 'Natchathiram: '.implode(', ', $prefs->stars);
                    }
                    if (!empty($prefs->castes)) {
                        $chips[] = 'Caste: '.implode(', ', $prefs->castes);
                    }
                }
            @endphp
            <div class="card mt-6 p-5">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h2 class="text-base font-bold text-ink-900">Your Partner Preferences (Preference Matches)</h2>
                        <p class="text-xs" style="color:var(--text-muted)">Showing profiles matching your saved preference criteria ({{ $results->total() }} matching {{ Str::plural('profile', $results->total()) }}).</p>
                    </div>
                    <a href="{{ route('profile.wizard.step', 'preferences') }}" class="rounded-md bg-primary-500 px-3.5 py-1.5 text-xs font-semibold text-white hover:bg-primary-600">
                        Edit Preference
                    </a>
                </div>
                @if (!empty($chips))
                    <div class="mt-3 flex flex-wrap gap-2">
                        @foreach ($chips as $chip)
                            <span class="inline-flex items-center rounded-full bg-primary-50 border border-primary-200 px-3 py-1 text-xs font-medium text-primary-800">
                                {{ $chip }}
                            </span>
                        @endforeach
                    </div>
                @endif
            </div>
        @endif

        @if ($tab === 'preference-match')
            <form method="GET" action="{{ route('matches.preference-match') }}" class="card mt-6 grid gap-4 p-6 sm:grid-cols-3">
                <x-form.multi-select-dropdown name="raasi" label="Raasi / Moon sign" :options="$raasiOptions" :selected="$astrologyFilters['raasi']" />
                <x-form.multi-select-dropdown name="star" label="Star / Nakshatra" :options="$starOptions" :selected="$astrologyFilters['star']" />
                <div class="flex items-end gap-2">
                    <button type="submit" class="rounded-md bg-primary-500 px-4 py-2 text-sm font-medium text-white hover:bg-primary-600">Filter</button>
                    <a href="{{ route('matches.preference-match') }}" class="rounded-md border px-4 py-2 text-sm font-medium" style="border-color:var(--border-default)">Clear</a>
                </div>
            </form>
        @endif

        @if ($tab === 'caste-match')
            <form method="GET" action="{{ route('matches.caste-match') }}" class="card mt-6 grid gap-4 p-6 sm:grid-cols-2 lg:grid-cols-4">
                <x-form.multi-select-dropdown name="community_id" label="Community" :options="$communityOptions" :selected="$casteFilters['community_id']" />
                <x-form.multi-select-dropdown name="sub_community_id" label="Sub-community" :options="$subCommunityOptions" :selected="$casteFilters['sub_community_id']" />
                <x-form.multi-select-dropdown name="caste" label="Caste" :options="$casteOptions" :selected="$casteFilters['caste']" valueKey="name" />
                <x-form.multi-select-dropdown name="gothra" label="Gothra" :options="$gothraOptions" :selected="$casteFilters['gothra']" valueKey="name" />
                <div class="flex items-end gap-2 sm:col-span-2 lg:col-span-4">
                    <button type="submit" class="rounded-md bg-primary-500 px-4 py-2 text-sm font-medium text-white hover:bg-primary-600">Filter</button>
                    <a href="{{ route('matches.caste-match') }}" class="rounded-md border px-4 py-2 text-sm font-medium" style="border-color:var(--border-default)">Clear</a>
                </div>
            </form>
        @endif

        @if ($notice)
            <p class="mt-4 text-sm" style="color:var(--text-muted)">{{ $notice }}</p>
        @endif

        @if ($results->isEmpty())
            <p class="mt-6 text-sm" style="color:var(--text-muted)">Nothing to show here yet.</p>
        @else
            <div class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ($results as $profile)
                    <x-profile.card :profile="$profile" :viewer="$viewer" />
                @endforeach
            </div>

            <div class="mt-6">
                {{ $results->appends($paginationAppends ?? [])->links() }}
            </div>
        @endif
    </div>
@endsection
