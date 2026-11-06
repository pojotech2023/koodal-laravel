@extends('layouts.public')

@section('title', 'Browse Profiles — '.config('app.name'))
@section('description', 'Browse verified '.config('app.name').' profiles by age, community, mother tongue and location. Join free to see full profiles and send an interest.')

{{-- Indexable — this is one of the pages docs/05-website-guide.md §3 lists
     under layouts.public. The canonical is the bare path so that the hundreds
     of filter permutations a crawler will generate all fold into one URL
     rather than competing with each other. --}}
@section('canonical', route('browse'))

@section('body')
    <div class="mx-auto max-w-6xl px-4 py-10">
        <h1 class="font-serif text-3xl font-semibold">Browse profiles</h1>
        <p class="mt-2 max-w-2xl text-sm" style="color:var(--text-muted)">
            A preview of who is here. Members choose whether their profile is
            public — so this shows a fraction of the community. Join free to
            search everyone, see full profiles and start a conversation.
        </p>

        {{-- Plain GET form, no JS. Same rule as the member search
             (docs/05-website-guide.md §1) and it matters more here: this is
             the page a first-time visitor and a crawler both land on. --}}
        <form method="GET" action="{{ route('browse') }}" class="card mt-6 grid gap-4 p-5 sm:grid-cols-3">
            <div>
                <label class="block text-sm font-medium" for="looking_for">I'm looking for</label>
                <select id="looking_for" name="looking_for" required class="mt-1 w-full rounded-md border px-3 py-2 text-sm" style="border-color:var(--border-default)">
                    <option value="">Choose…</option>
                    <option value="female" @selected($lookingFor === 'female')>A bride</option>
                    <option value="male" @selected($lookingFor === 'male')>A groom</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium" for="age_min">Age from</label>
                <input type="number" id="age_min" name="age_min" min="18" max="{{ config('matrimony.profile.max_age') }}" value="{{ $criteria['age_min'] ?? '' }}" class="mt-1 w-full rounded-md border px-3 py-2 text-sm" style="border-color:var(--border-default)">
            </div>
            <div>
                <label class="block text-sm font-medium" for="age_max">Age to</label>
                <input type="number" id="age_max" name="age_max" min="18" max="{{ config('matrimony.profile.max_age') }}" value="{{ $criteria['age_max'] ?? '' }}" class="mt-1 w-full rounded-md border px-3 py-2 text-sm" style="border-color:var(--border-default)">
            </div>

            <div>
                <label class="block text-sm font-medium" for="religion_id">Religion</label>
                <select id="religion_id" name="religion_id" class="mt-1 w-full rounded-md border px-3 py-2 text-sm" style="border-color:var(--border-default)">
                    <option value="">Any</option>
                    @foreach ($religions as $religion)
                        <option value="{{ $religion['id'] }}" @selected(($criteria['religion_id'] ?? null) == $religion['id'])>{{ $religion['name'] }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium" for="mother_tongue_id">Mother tongue</label>
                <select id="mother_tongue_id" name="mother_tongue_id" class="mt-1 w-full rounded-md border px-3 py-2 text-sm" style="border-color:var(--border-default)">
                    <option value="">Any</option>
                    @foreach ($motherTongues as $tongue)
                        <option value="{{ $tongue['id'] }}" @selected(($criteria['mother_tongue_id'] ?? null) == $tongue['id'])>{{ $tongue['name'] }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium" for="marital_status_id">Marital status</label>
                <select id="marital_status_id" name="marital_status_id" class="mt-1 w-full rounded-md border px-3 py-2 text-sm" style="border-color:var(--border-default)">
                    <option value="">Any</option>
                    @foreach ($maritalStatuses as $status)
                        <option value="{{ $status['id'] }}" @selected(($criteria['marital_status_id'] ?? null) == $status['id'])>{{ $status['name'] }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Location has no control here on purpose: a country/state/city
                 cascade is three dependent selects and the JS to drive them,
                 and this form is the one page that has to work without any.
                 The filters themselves are accepted (PreviewSearchRequest), so
                 a marketing link like /browse?looking_for=female&city_id=12
                 works today — only the picker is missing. --}}
            @foreach (['country_id', 'state_id', 'city_id'] as $passthrough)
                @isset($criteria[$passthrough])
                    <input type="hidden" name="{{ $passthrough }}" value="{{ $criteria[$passthrough] }}">
                @endisset
            @endforeach

            <label class="flex items-center gap-2 self-end text-sm">
                <input type="checkbox" name="has_photo" value="1" @checked($criteria['has_photo'] ?? false)>
                With photo only
            </label>

            <div class="flex justify-end gap-2 sm:col-span-3">
                <a href="{{ route('browse') }}" class="rounded-md border px-4 py-2 text-sm font-medium" style="border-color:var(--border-default)">Clear</a>
                <button type="submit" class="rounded-md bg-primary-500 px-4 py-2 text-sm font-medium text-white hover:bg-primary-600">Show profiles</button>
            </div>
        </form>

        @if ($preview === null)
            {{-- Nothing chosen yet. No default gender is assumed — see
                 Web\PreviewSearchController. --}}
            <p class="mt-8 text-sm" style="color:var(--text-muted)">
                Choose who you are looking for to see a preview.
            </p>
        @elseif (! $preview->hasMatches)
            <div class="mt-8 rounded-lg border p-6 text-center" style="border-color:var(--border-default)">
                <p class="text-sm" style="color:var(--text-muted)">No members match these filters yet. Try widening them.</p>
                <a href="{{ route('register') }}" class="mt-4 inline-block rounded-md bg-primary-500 px-5 py-2.5 text-sm font-medium text-white hover:bg-primary-600">
                    Create your free profile
                </a>
            </div>
        @else
            {{-- The count is a bucket, never an exact figure, and may be
                 withheld entirely when the matching set is small — see
                 RunPreviewSearch::countMatches for why. Both branches read as
                 a sentence, so neither looks like a missing value. --}}
            <p class="mt-8 text-sm font-medium">
                @if ($preview->approxLabel())
                    {{ $preview->approxLabel() }} members match your search.
                @else
                    Members matching your search are waiting to hear from you.
                @endif
            </p>

            @if ($preview->profiles->isEmpty())
                <p class="mt-2 text-sm" style="color:var(--text-muted)">
                    Please log in before viewing matches so that you can view member profiles.
                </p>
            @else
                <p class="mt-1 text-sm" style="color:var(--text-muted)">
                    Showing the {{ $preview->profiles->count() }} who chose to make their profile public.
                </p>

                <div class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    @foreach ($preview->profiles as $profile)
                        <x-profile.preview-card :profile="$profile" />
                    @endforeach
                </div>
            @endif

            <div class="card mt-10 p-6 text-center">
                <p class="font-serif text-xl font-semibold">See every profile that matches</p>
                <p class="mx-auto mt-2 max-w-md text-sm" style="color:var(--text-muted)">
                    Members search the full community, view complete profiles and send an interest. Creating a profile is free.
                </p>
                <div class="mt-5 flex flex-col items-center justify-center gap-3 sm:flex-row">
                    <a href="{{ route('register') }}" class="w-full rounded-md bg-primary-500 px-6 py-3 text-sm font-medium text-white hover:bg-primary-600 sm:w-auto">
                        Create your free profile
                    </a>
                    <a href="{{ route('login') }}" class="w-full rounded-md border border-primary-500 px-6 py-3 text-sm font-medium text-primary-600 hover:bg-primary-50 sm:w-auto">
                        Log in
                    </a>
                </div>
            </div>
        @endif
    </div>
@endsection
