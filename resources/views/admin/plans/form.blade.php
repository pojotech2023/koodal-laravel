@extends('admin.layouts.admin')

@php($isNew = ! $plan->exists)

@section('title', ($isNew ? 'New plan' : 'Edit '.$plan->name).' — '.config('app.name').' Admin')

@section('admin-body')
    <a href="{{ route('admin.plans.index') }}" class="text-sm font-medium text-primary-600 hover:underline">&larr; Membership plans</a>

    <h1 class="mt-3 font-serif text-3xl font-bold text-ink-900">{{ $isNew ? 'New plan' : 'Edit '.$plan->name }}</h1>

    @unless ($isNew)
        <p class="mt-1 text-sm" style="color:var(--text-muted)">
            {{ number_format($plan->subscriptions_count ?? 0) }} active subscription(s) hold this plan. They keep the features they bought — the snapshot taken at purchase is what `FeatureGate` reads, not this form.
        </p>
    @endunless

    <form method="POST" action="{{ $isNew ? route('admin.plans.store') : route('admin.plans.update', $plan) }}" class="mt-6 max-w-4xl space-y-6">
        @csrf
        @unless ($isNew)
            @method('PUT')
        @endunless

        <section class="card p-5">
            <h2 class="card-title">Plan</h2>

            <div class="mt-3 grid grid-cols-1 gap-4 sm:grid-cols-2">
                <x-form.input name="name" label="Name" :value="$plan->name" required maxlength="80" />
                <x-form.input name="slug" label="Slug" :value="$plan->slug" required maxlength="100"
                    hint="Used in URLs and by the seeder. Unique across plans, including deleted ones." />

                <x-form.input name="price" label="Price" type="number" step="0.01" min="0" :value="$plan->price" required />
                <x-form.input name="currency" label="Currency" :value="$plan->currency" maxlength="3" required />

                <x-form.input name="duration_days" label="Duration (days)" type="number" min="1" max="3650" :value="$plan->duration_days"
                    hint="Leave empty for a plan that never expires — that is how the free tier is expressed." />
                <x-form.input name="sort_order" label="Sort order" type="number" min="0" max="9999" :value="$plan->sort_order ?? 0" />

                <div class="sm:col-span-2">
                    <x-form.textarea name="description" label="Description" :value="$plan->description" maxlength="2000" />
                </div>
            </div>

            <div class="mt-4 flex flex-wrap items-center gap-6">
                {{-- Hidden 0 before every checkbox: an unticked box submits
                     nothing at all, and "nothing" on an update means "leave it
                     alone" — so without these, a plan could never be
                     deactivated or un-featured from this form. --}}
                <label class="flex items-center gap-2 text-sm font-medium">
                    <input type="hidden" name="is_active" value="0">
                    <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $plan->is_active ?? true))
                           class="rounded border" style="border-color:var(--border-default)">
                    Active — sold on the plans page
                </label>

                <label class="flex items-center gap-2 text-sm font-medium">
                    <input type="hidden" name="is_featured" value="0">
                    <input type="checkbox" name="is_featured" value="1" @checked(old('is_featured', $plan->is_featured ?? false))
                           class="rounded border" style="border-color:var(--border-default)">
                    Featured
                </label>

                <div class="w-48">
                    <x-form.input name="badge_text" label="Badge text" :value="$plan->badge_text" maxlength="40" />
                </div>
            </div>
        </section>

        {{-- Generated from config('matrimony.membership.features'), never
             hand-listed: that array is the single definition of what a feature
             key is, `FeatureGate` reads the same keys at runtime, and a
             hand-written form would let a typo create a plan that silently
             grants nothing. Adding a feature to the config adds a field here
             with no edit to this file. --}}
        <section class="card p-5">
            <h2 class="card-title">Features</h2>
            <p class="mt-1 text-xs" style="color:var(--text-muted)">
                Generated from the feature schema. Every plan must set every key — an absent key does not mean “off”, it means the member inherits whatever the default plan grants.
            </p>

            <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
                @foreach ($schema as $key => $meta)
                    @php($current = old("features.{$key}", data_get($plan->features, $key)))

                    <div>
                        @if ($meta['type'] === 'quota')
                            <label for="features-{{ $key }}" class="block text-sm font-medium">{{ $meta['label'] }}</label>
                            <input id="features-{{ $key }}" name="features[{{ $key }}]" type="number" min="-1" step="1"
                                   value="{{ $current === null ? '' : $current }}" required
                                   class="mt-1 w-full rounded-md border px-3 py-2 text-sm" style="border-color:var(--border-default)">
                            <p class="mt-1 text-xs" style="color:var(--text-muted)">
                                -1 for unlimited @if (($meta['period'] ?? 'none') !== 'none') · resets per {{ $meta['period'] }}@endif
                            </p>

                        @elseif ($meta['type'] === 'capability')
                            <label class="flex items-center gap-2 pt-6 text-sm font-medium">
                                <input type="hidden" name="features[{{ $key }}]" value="0">
                                <input type="checkbox" name="features[{{ $key }}]" value="1" @checked($current)
                                       class="rounded border" style="border-color:var(--border-default)">
                                {{ $meta['label'] }}
                            </label>

                        @else
                            <x-form.select :name="'features['.$key.']'" :label="$meta['label']" :value="$current"
                                :options="collect($meta['options'] ?? [])->map(fn ($o) => ['id' => $o, 'name' => ucfirst($o)])->all()" />
                        @endif

                        @error("features.{$key}")
                            <p class="mt-1 text-xs text-danger">{{ $message }}</p>
                        @enderror
                    </div>
                @endforeach
            </div>
        </section>

        <div class="flex items-center gap-3">
            <button type="submit" class="rounded-md bg-primary-500 px-4 py-2 text-sm font-medium text-white hover:bg-primary-600">
                {{ $isNew ? 'Create plan' : 'Save changes' }}
            </button>
            <a href="{{ route('admin.plans.index') }}" class="rounded-md border px-4 py-2 text-sm font-medium" style="border-color:var(--border-default)">
                Cancel
            </a>
        </div>
    </form>
@endsection
