@extends('layouts.member')

@section('title', 'Search — '.config('app.name'))

@section('body')
    <div class="mx-auto max-w-6xl px-4 py-8">
        <h1 class="font-serif text-3xl font-bold text-ink-900">Search</h1>

        {{-- Plain GET form — reloads with results, no Alpine required for the
             golden path (docs/05-website-guide.md §1). --}}
        <form method="GET" action="{{ route('search') }}" class="card mt-6 grid gap-4 p-6 sm:grid-cols-4">
            <div class="sm:col-span-2">
                <label class="block text-sm font-medium" for="q">Name or profile code</label>
                <input type="text" id="q" name="q" value="{{ $query['q'] ?? '' }}" class="mt-1 w-full rounded-md border px-3 py-2 text-sm" style="border-color:var(--border-default)">
            </div>

            <div>
                <label class="block text-sm font-medium" for="age_min">Age from</label>
                <input type="number" id="age_min" name="age_min" min="18" value="{{ $query['age_min'] ?? '' }}" class="mt-1 w-full rounded-md border px-3 py-2 text-sm" style="border-color:var(--border-default)">
            </div>
            <div>
                <label class="block text-sm font-medium" for="age_max">Age to</label>
                <input type="number" id="age_max" name="age_max" min="18" value="{{ $query['age_max'] ?? '' }}" class="mt-1 w-full rounded-md border px-3 py-2 text-sm" style="border-color:var(--border-default)">
            </div>

            <x-form.multi-select-dropdown
                name="religion_ids"
                label="Religion"
                :options="$religions"
                :selected="$query['religion_ids'] ?? []"
            />

            <x-form.multi-select-dropdown
                name="marital_status_ids"
                label="Marital status"
                :options="$maritalStatuses"
                :selected="$query['marital_status_ids'] ?? []"
            />

            <x-form.multi-select-dropdown
                name="mother_tongue_ids"
                label="Mother tongue"
                :options="$motherTongues"
                :selected="$query['mother_tongue_ids'] ?? []"
            />

            <div>
                <label class="block text-sm font-medium" for="sort">Sort by</label>
                <select id="sort" name="sort" class="mt-1 w-full rounded-md border px-3 py-2 text-sm" style="border-color:var(--border-default)">
                    @foreach ($sorts as $sort)
                        <option value="{{ $sort }}" @selected(($query['sort'] ?? 'recommended') === $sort)>{{ ucfirst(str_replace('_', ' ', $sort)) }}</option>
                    @endforeach
                </select>
            </div>

            <label class="flex items-center gap-2 self-end text-sm">
                <input type="checkbox" name="has_photo" value="1" @checked($query['has_photo'] ?? false)>
                With photo only
            </label>

            <div class="flex justify-end gap-2 sm:col-span-4">
                <a href="{{ route('search') }}" class="rounded-md border px-4 py-2 text-sm font-medium" style="border-color:var(--border-default)">Clear</a>
                <button type="submit" class="rounded-md bg-primary-500 px-4 py-2 text-sm font-medium text-white hover:bg-primary-600">Search</button>
            </div>
        </form>

        <p class="mt-6 text-sm" style="color:var(--text-muted)">{{ $results->total() }} profiles found</p>

        @if ($results->isEmpty())
            <p class="mt-4 text-sm" style="color:var(--text-muted)">No profiles match these filters yet. Try widening your search.</p>
        @else
            <div class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ($results as $profile)
                    <x-profile.card :profile="$profile" :viewer="$viewer" />
                @endforeach
            </div>

            <div class="mt-6">
                {{ $results->appends($query)->links() }}
            </div>
        @endif
    </div>
@endsection
