{{-- Step 8 — Partner Preferences (`preferences`).
     Stores desired partner criteria in `user_preferences` and powers the
     "Reference" tab on `/matches/reference`. --}}
@php
    $prefs = $preferences ?? $user->preferences;

    $ageOptions = collect(range(18, 70))->map(fn ($y) => ['id' => $y, 'name' => $y.' yrs'])->all();

    $heightOptions = collect(range(122, 213))->map(function ($cm) {
        $totalInches = (int) round($cm / 2.54);
        $feet = intdiv($totalInches, 12);
        $inches = $totalInches % 12;
        return ['id' => $cm, 'name' => "{$cm} cm ({$feet}'{$inches}\")"];
    })->all();

    $weightOptions = collect(range(35, 140))->map(fn ($kg) => ['id' => $kg, 'name' => $kg.' kg'])->all();

    $selectedMaritalStatuses = old('marital_status_ids', $prefs?->marital_status_ids ?? []);
    $selectedOccupations = old('occupation_ids', $prefs?->occupation_ids ?? []);
    $selectedIncomeRanges = old('income_range_ids', $prefs?->income_range_ids ?? []);
    $selectedComplexions = old('complexions', $prefs?->complexions ?? []);
    $selectedMotherTongues = old('mother_tongue_ids', $prefs?->mother_tongue_ids ?? []);
    $selectedStates = old('state_ids', $prefs?->state_ids ?? []);
    $selectedRaasis = old('raasis', $prefs?->raasis ?? []);
    $selectedStars = old('stars', $prefs?->stars ?? []);
    $selectedCastes = array_map('strval', (array) old('castes', $prefs?->castes ?? []));
@endphp

<div>
    <div class="flex flex-wrap items-center justify-between gap-3 border-b pb-4" style="border-color:var(--border-default)">
        <div>
            <h2 class="text-xl font-bold text-ink-900">Partner Preference</h2>
            <p class="mt-1 text-sm" style="color:var(--text-muted)">
                Set your partner preferences below. Matching profiles will automatically appear under <strong>Matches &rarr; Preference</strong>.
            </p>
        </div>
        <a href="{{ route('matches.reference') }}" class="inline-flex items-center gap-1.5 rounded-full bg-primary-50 border border-primary-200 px-3.5 py-1.5 text-xs font-semibold text-primary-700 hover:bg-primary-100">
            <span>View Preference Matches</span>
            <span>&rarr;</span>
        </a>
    </div>

    <form method="POST" action="{{ route('profile.wizard.preferences') }}" class="mt-6 space-y-6">
        @csrf

        {{-- 1. Age, Height & Weight Ranges --}}
        <div>
            <h3 class="text-sm font-semibold uppercase tracking-wider" style="color:var(--text-muted)">Age, Height &amp; Weight Preference</h3>
            <div class="mt-3 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                {{-- Age Range --}}
                <div class="rounded-lg border p-3.5" style="border-color:var(--border-default)">
                    <label class="block text-sm font-semibold text-ink-900 mb-2">Age Range (Years)</label>
                    <div class="grid grid-cols-2 gap-2">
                        <x-form.select name="age_min" label="Min Age" placeholder="Any" :options="$ageOptions" :value="old('age_min', $prefs?->age_min)" />
                        <x-form.select name="age_max" label="Max Age" placeholder="Any" :options="$ageOptions" :value="old('age_max', $prefs?->age_max)" />
                    </div>
                </div>

                {{-- Height Range --}}
                <div class="rounded-lg border p-3.5" style="border-color:var(--border-default)">
                    <label class="block text-sm font-semibold text-ink-900 mb-2">Height Range (cm)</label>
                    <div class="grid grid-cols-2 gap-2">
                        <x-form.select name="height_min_cm" label="Min Height" placeholder="Any" :options="$heightOptions" :value="old('height_min_cm', $prefs?->height_min_cm)" />
                        <x-form.select name="height_max_cm" label="Max Height" placeholder="Any" :options="$heightOptions" :value="old('height_max_cm', $prefs?->height_max_cm)" />
                    </div>
                </div>

                {{-- Weight Range --}}
                <div class="rounded-lg border p-3.5 sm:col-span-2 lg:col-span-1" style="border-color:var(--border-default)">
                    <label class="block text-sm font-semibold text-ink-900 mb-2">Weight Range (kg)</label>
                    <div class="grid grid-cols-2 gap-2">
                        <x-form.select name="weight_min_kg" label="Min Weight" placeholder="Any" :options="$weightOptions" :value="old('weight_min_kg', $prefs?->weight_min_kg)" />
                        <x-form.select name="weight_max_kg" label="Max Weight" placeholder="Any" :options="$weightOptions" :value="old('weight_max_kg', $prefs?->weight_max_kg)" />
                    </div>
                </div>
            </div>
        </div>

        {{-- 2. Personal, Career, Location & Background Dropdown Checklists --}}
        <div>
            <h3 class="text-sm font-semibold uppercase tracking-wider" style="color:var(--text-muted)">Basic, Career &amp; Location Preferences</h3>
            <div class="mt-3 grid gap-4 sm:grid-cols-2">
                {{-- Marital Status (Dropdown Checklist) --}}
                <x-form.multi-select-dropdown
                    name="marital_status_ids"
                    label="Marital Status"
                    :options="$options['marital_statuses'] ?? []"
                    :selected="$selectedMaritalStatuses"
                />

                {{-- Color / Complexion (Dropdown Checklist) --}}
                <x-form.multi-select-dropdown
                    name="complexions"
                    label="Color / Complexion"
                    :options="$options['complexions'] ?? []"
                    :selected="$selectedComplexions"
                    valueKey="name"
                />

                {{-- Job / Occupation (Dropdown Checklist) --}}
                <x-form.multi-select-dropdown
                    name="occupation_ids"
                    label="Job / Occupation"
                    :options="$options['occupations'] ?? []"
                    :selected="$selectedOccupations"
                />

                {{-- Job Salary Range (Dropdown Checklist) --}}
                <x-form.multi-select-dropdown
                    name="income_range_ids"
                    label="Job Salary Range (Annual Income)"
                    :options="$options['income_ranges'] ?? []"
                    :selected="$selectedIncomeRanges"
                />

                {{-- Mother Tongue (Dropdown Checklist) --}}
                <x-form.multi-select-dropdown
                    name="mother_tongue_ids"
                    label="Mother Tongue"
                    :options="$options['mother_tongues'] ?? []"
                    :selected="$selectedMotherTongues"
                />

                {{-- State (Dropdown Checklist) --}}
                <x-form.multi-select-dropdown
                    name="state_ids"
                    label="State"
                    :options="$options['states'] ?? []"
                    :selected="$selectedStates"
                />
            </div>
        </div>

        {{-- 3. Astrology (Rasi & Natchathiram Dropdown Checklists) --}}
        <div>
            <h3 class="text-sm font-semibold uppercase tracking-wider" style="color:var(--text-muted)">Astrology Preference (Rasi &amp; Natchathiram)</h3>
            <div class="mt-3 grid gap-4 sm:grid-cols-2">
                {{-- Rasi Dropdown Checklist --}}
                <x-form.multi-select-dropdown
                    name="raasis"
                    label="Rasi (Moon Sign)"
                    :options="$options['raasis'] ?? []"
                    :selected="$selectedRaasis"
                />

                {{-- Natchathiram Dropdown Checklist --}}
                <x-form.multi-select-dropdown
                    name="stars"
                    label="Natchathiram (Star)"
                    :options="$options['stars'] ?? []"
                    :selected="$selectedStars"
                />
            </div>
        </div>

        {{-- 4. Caste (Checkbox List) --}}
        <div>
            <div class="flex flex-wrap items-center justify-between gap-2">
                <div>
                    <h3 class="text-sm font-semibold uppercase tracking-wider" style="color:var(--text-muted)">Caste Preference (Checkboxes)</h3>
                    <p class="text-xs" style="color:var(--text-muted)">Select one or more castes you prefer (leave unchecked for any caste).</p>
                </div>
                <div class="flex items-center gap-3 text-xs">
                    <button type="button" id="pref-caste-select-all" class="font-semibold text-primary-600 hover:underline">Select All</button>
                    <button type="button" id="pref-caste-clear-all" class="font-medium text-ink-500 hover:underline">Clear All</button>
                </div>
            </div>

            <div class="mt-2">
                <input
                    type="text"
                    id="pref-caste-search"
                    placeholder="Search caste..."
                    class="w-full rounded-md border px-3 py-1.5 text-sm focus:border-primary-500 focus:outline-none"
                    style="border-color:var(--border-default);background-color:var(--bg-surface)"
                >
            </div>

            <div class="mt-2 rounded-lg border p-2" style="border-color:var(--border-default);background-color:var(--bg-surface)">
                <div class="kg-left-scroll pr-1 pl-2" style="max-height: 220px; overflow-y: auto; direction: rtl;">
                    <div id="pref-caste-grid" class="kg-left-scroll-inner grid grid-cols-1 gap-1.5 sm:grid-cols-2 md:grid-cols-3" style="direction: ltr;">
                        @forelse ($options['castes'] ?? [] as $caste)
                            <label class="pref-caste-item flex cursor-pointer items-center gap-2 rounded px-2 py-1.5 text-sm hover:bg-primary-50/50" data-name="{{ strtolower($caste['name']) }}">
                                <input
                                    type="checkbox"
                                    name="castes[]"
                                    value="{{ $caste['name'] }}"
                                    @checked(in_array((string) $caste['name'], $selectedCastes, true))
                                    class="pref-caste-checkbox rounded text-primary-600 focus:ring-primary-500"
                                >
                                <span class="truncate">{{ $caste['name'] }}</span>
                            </label>
                        @empty
                            <p class="col-span-full text-xs" style="color:var(--text-muted)">No castes configured.</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>

        {{-- Footer navigation --}}
        <div class="mt-6 flex flex-wrap items-center justify-between gap-3 border-t pt-4" style="border-color:var(--border-default)">
            <a href="{{ route('profile.wizard.step', 'aadhaar') }}" class="rounded-md border px-4 py-2 text-sm font-medium" style="border-color:var(--border-default)">
                &larr; Back
            </a>

            <div class="flex flex-wrap items-center gap-2">
                <button type="submit" class="rounded-md bg-primary-600 px-5 py-2 text-sm font-semibold text-white shadow-sm hover:bg-primary-700">
                    Save Preferences &amp; View Matches &rarr;
                </button>
            </div>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var searchInput = document.getElementById('pref-caste-search');
    var items = document.querySelectorAll('.pref-caste-item');
    var checkboxes = document.querySelectorAll('.pref-caste-checkbox');
    var btnSelectAll = document.getElementById('pref-caste-select-all');
    var btnClearAll = document.getElementById('pref-caste-clear-all');

    if (searchInput) {
        searchInput.addEventListener('input', function () {
            var q = this.value.trim().toLowerCase();
            items.forEach(function (item) {
                var name = item.getAttribute('data-name') || '';
                item.style.display = (!q || name.indexOf(q) !== -1) ? '' : 'none';
            });
        });
    }

    if (btnSelectAll) {
        btnSelectAll.addEventListener('click', function () {
            items.forEach(function (item) {
                if (item.style.display !== 'none') {
                    var cb = item.querySelector('.pref-caste-checkbox');
                    if (cb) cb.checked = true;
                }
            });
        });
    }

    if (btnClearAll) {
        btnClearAll.addEventListener('click', function () {
            checkboxes.forEach(function (cb) {
                cb.checked = false;
            });
        });
    }
});
</script>
