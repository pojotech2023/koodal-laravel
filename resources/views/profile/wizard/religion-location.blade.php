{{-- Step 2 — `religious` and `location`. Community/sub-community and
     state/city are cascades: `Web\ProfileController::optionsFor()` filters
     each child list by the parent's *currently saved or just-submitted*
     value, so picking a new religion/country and hitting Continue reloads
     this step with the right children — no JS required (PROGRESS.md §6). --}}
@php
    $manglik = [['id' => 'yes', 'name' => 'Yes'], ['id' => 'no', 'name' => 'No'], ['id' => 'dont_know', 'name' => "Don't know"]];
    $religionSelected = old('religion_id', $profile?->religion_id);
    $communitySelected = old('community_id', $profile?->community_id);
    $countrySelected = old('country_id', $profile?->country_id);
    $stateSelected = old('state_id', $profile?->state_id);

    // config('matrimony.astrology') — the fixed Raasi/Nakshatra lists, shared
    // with the "Profile matched" filter on the Matches page.
    $raasis = collect(config('matrimony.astrology.raasis'))->map(fn ($name) => ['id' => $name, 'name' => $name])->all();
    $nakshatras = collect(config('matrimony.astrology.nakshatras'))->map(fn ($name) => ['id' => $name, 'name' => $name])->all();
@endphp

<form method="POST" action="{{ route('profile.wizard.religion-location') }}">
    @csrf

    <h3 class="text-sm font-semibold" style="color:var(--text-muted)">Religion & background</h3>
    <div class="mt-3 grid gap-4 sm:grid-cols-2">
        <x-form.select name="religion_id" label="Religion" placeholder="Select" :options="$options['religions']" :value="$profile?->religion_id" />
        <x-form.select name="community_id" label="Community" :placeholder="$religionSelected ? 'Select' : 'Select a religion first'" :options="$options['communities']" :value="$profile?->community_id" :disabled="! $religionSelected" />
        <x-form.select name="sub_community_id" label="Sub-community" :placeholder="$communitySelected ? 'Select' : 'Select a community first'" :options="$options['sub_communities']" :value="$profile?->sub_community_id" :disabled="! $communitySelected" />
        <x-form.select name="mother_tongue_id" label="Mother tongue" placeholder="Select" :options="$options['mother_tongues']" :value="$profile?->mother_tongue_id" />
        <x-form.select name="caste" label="Caste" placeholder="Select" :options="$options['castes']" :value="$profile?->caste" valueKey="name" />
        <x-form.select name="gothra" label="Gothra" placeholder="Select" :options="$options['gothras']" :value="$profile?->gothra" valueKey="name" />
        <x-form.select name="manglik" label="Manglik" placeholder="Select" :options="$manglik" :value="$profile?->manglik" />
        <x-form.select name="star" label="Star / Nakshatra" placeholder="Select" :options="$nakshatras" :value="$profile?->star" valueKey="id" />
        <x-form.select name="raasi" label="Raasi / Moon sign" placeholder="Select" :options="$raasis" :value="$profile?->raasi" valueKey="id" />
        <x-form.input name="birth_time" label="Birth time" type="time" hint="If known — used for horoscope matching" :value="$profile?->birth_time" />
        <x-form.input name="birth_place" label="Birth place" :value="$profile?->birth_place" />
    </div>

    <h3 class="mt-6 text-sm font-semibold" style="color:var(--text-muted)">Where you live</h3>
    <div class="mt-3 grid gap-4 sm:grid-cols-2">
        <x-form.select name="country_id" label="Country" placeholder="Select" :options="$options['countries']" :value="$profile?->country_id" />
        <x-form.select name="state_id" label="State" :placeholder="$countrySelected ? 'Select' : 'Select a country first'" :options="$options['states']" :value="$profile?->state_id" :disabled="! $countrySelected" />
        <x-form.select name="city_id" label="City" :placeholder="$stateSelected ? 'Select' : 'Select a state first'" :options="$options['cities']" :value="$profile?->city_id" :disabled="! $stateSelected" />
        <x-form.input name="residency_status" label="Residency status" hint="e.g. Citizen, Permanent Resident, Work visa" :value="$profile?->residency_status" />
        <x-form.input name="pincode" label="Pincode" :value="$profile?->pincode" />
    </div>

    @include('profile.wizard._footer')
</form>

<script>
(function() {
    function initCascades() {
        const religionSelect = document.getElementById('religion_id');
        const communitySelect = document.getElementById('community_id');
        const subCommunitySelect = document.getElementById('sub_community_id');
        const countrySelect = document.getElementById('country_id');
        const stateSelect = document.getElementById('state_id');
        const citySelect = document.getElementById('city_id');

        const initialCommunityId = @json(old('community_id', $profile?->community_id));
        const initialSubCommunityId = @json(old('sub_community_id', $profile?->sub_community_id));
        const initialStateId = @json(old('state_id', $profile?->state_id));
        const initialCityId = @json(old('city_id', $profile?->city_id));

        async function fetchJson(url) {
            try {
                const res = await fetch(url, {
                    headers: { 'Accept': 'application/json' }
                });
                if (!res.ok) return [];
                const json = await res.json();
                if (Array.isArray(json.data)) return json.data;
                if (json.data && Array.isArray(json.data.items)) return json.data.items;
                return [];
            } catch (err) {
                console.error('Fetch error:', err);
                return [];
            }
        }

        function populateSelect(selectElem, items, placeholder, selectedValue) {
            selectElem.innerHTML = '';
            const defaultOpt = document.createElement('option');
            defaultOpt.value = '';
            defaultOpt.textContent = placeholder || 'Select';
            selectElem.appendChild(defaultOpt);

            items.forEach(function(item) {
                const opt = document.createElement('option');
                opt.value = item.id;
                opt.textContent = item.name;
                if (selectedValue && String(item.id) === String(selectedValue)) {
                    opt.selected = true;
                }
                selectElem.appendChild(opt);
            });

            if (items.length > 0) {
                selectElem.disabled = false;
            } else {
                selectElem.disabled = false;
                defaultOpt.textContent = 'None available';
            }
        }

        function setSelectLoading(selectElem, text) {
            selectElem.innerHTML = '';
            const opt = document.createElement('option');
            opt.value = '';
            opt.textContent = text || 'Loading...';
            selectElem.appendChild(opt);
            selectElem.disabled = true;
        }

        function setSelectDisabled(selectElem, text) {
            selectElem.innerHTML = '';
            const opt = document.createElement('option');
            opt.value = '';
            opt.textContent = text;
            selectElem.appendChild(opt);
            selectElem.disabled = true;
        }

        async function onReligionChange(religionId, targetCommunityId) {
            if (!religionId) {
                setSelectDisabled(communitySelect, 'Select a religion first');
                setSelectDisabled(subCommunitySelect, 'Select a community first');
                return;
            }

            setSelectLoading(communitySelect, 'Loading communities...');
            setSelectDisabled(subCommunitySelect, 'Select a community first');

            const items = await fetchJson('/api/v1/master-data/communities?religion_id=' + encodeURIComponent(religionId));
            populateSelect(communitySelect, items, 'Select', targetCommunityId);

            if (communitySelect.value) {
                onCommunityChange(communitySelect.value, initialSubCommunityId);
            }
        }

        async function onCommunityChange(communityId, targetSubCommunityId) {
            if (!communityId) {
                setSelectDisabled(subCommunitySelect, 'Select a community first');
                return;
            }

            setSelectLoading(subCommunitySelect, 'Loading sub-communities...');

            const items = await fetchJson('/api/v1/master-data/sub-communities?community_id=' + encodeURIComponent(communityId));
            if (items.length > 0) {
                populateSelect(subCommunitySelect, items, 'Select', targetSubCommunityId);
            } else {
                setSelectDisabled(subCommunitySelect, 'Not applicable');
                subCommunitySelect.disabled = false;
            }
        }

        async function onCountryChange(countryId, targetStateId) {
            if (!countryId) {
                setSelectDisabled(stateSelect, 'Select a country first');
                setSelectDisabled(citySelect, 'Select a state first');
                return;
            }

            setSelectLoading(stateSelect, 'Loading states...');
            setSelectDisabled(citySelect, 'Select a state first');

            const items = await fetchJson('/api/v1/locations/states?country_id=' + encodeURIComponent(countryId));
            populateSelect(stateSelect, items, 'Select', targetStateId);

            if (stateSelect.value) {
                onStateChange(stateSelect.value, initialCityId);
            }
        }

        async function onStateChange(stateId, targetCityId) {
            if (!stateId) {
                setSelectDisabled(citySelect, 'Select a state first');
                return;
            }

            setSelectLoading(citySelect, 'Loading cities...');

            const items = await fetchJson('/api/v1/locations/cities?state_id=' + encodeURIComponent(stateId));
            populateSelect(citySelect, items, 'Select', targetCityId);
        }

        if (religionSelect) {
            religionSelect.addEventListener('change', function() {
                onReligionChange(this.value, null);
            });

            if (religionSelect.value) {
                const optCount = communitySelect ? communitySelect.querySelectorAll('option[value]:not([value=""])').length : 0;
                if (optCount === 0) {
                    onReligionChange(religionSelect.value, initialCommunityId);
                }
            }
        }

        if (communitySelect) {
            communitySelect.addEventListener('change', function() {
                onCommunityChange(this.value, null);
            });

            if (communitySelect.value) {
                const optCount = subCommunitySelect ? subCommunitySelect.querySelectorAll('option[value]:not([value=""])').length : 0;
                if (optCount === 0) {
                    onCommunityChange(communitySelect.value, initialSubCommunityId);
                }
            }
        }

        if (countrySelect) {
            countrySelect.addEventListener('change', function() {
                onCountryChange(this.value, null);
            });

            if (countrySelect.value) {
                const optCount = stateSelect ? stateSelect.querySelectorAll('option[value]:not([value=""])').length : 0;
                if (optCount === 0) {
                    onCountryChange(countrySelect.value, initialStateId);
                }
            }
        }

        if (stateSelect) {
            stateSelect.addEventListener('change', function() {
                onStateChange(this.value, null);
            });

            if (stateSelect.value) {
                const optCount = citySelect ? citySelect.querySelectorAll('option[value]:not([value=""])').length : 0;
                if (optCount === 0) {
                    onStateChange(stateSelect.value, initialCityId);
                }
            }
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initCascades);
    } else {
        initCascades();
    }
})();
</script>
