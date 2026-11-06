@extends('layouts.member')

@section('title', 'My profile — '.config('app.name'))

@section('body')
    @php $profile = $user->profile; @endphp

    <div class="mx-auto max-w-4xl px-4 py-8">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <h1 class="font-serif text-3xl font-bold text-ink-900">My profile</h1>
                <p class="mt-1 text-sm" style="color:var(--text-muted)">{{ $user->profile_code }}</p>
            </div>
            <a href="{{ route('profile.wizard.start') }}" class="rounded-md bg-primary-500 px-4 py-2 text-sm font-medium text-white hover:bg-primary-600">
                Edit profile
            </a>
        </div>

        <div class="mt-4 flex flex-wrap gap-2 text-sm">
            <span class="rounded-full px-3 py-1 shadow-sm" style="background-color:var(--bg-surface);border:1px solid var(--border-default)">
                {{ $completion['completion_percentage'] }}% complete
            </span>
            <span class="rounded-full px-3 py-1 shadow-sm" style="background-color:var(--bg-surface);border:1px solid var(--border-default)">
                {{ ucfirst($profile?->approval_status ?? 'pending') }}
            </span>
            @if ($profile?->is_verified || $user->is_aadhaar_verified)
                <span class="inline-flex items-center gap-1 rounded-full bg-emerald-50 border border-emerald-200 px-3 py-1 text-xs font-semibold text-emerald-700 shadow-sm">
                    <svg class="h-3.5 w-3.5 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
                    Verified
                </span>
            @endif
        </div>

        @if ($profile?->approval_status === 'rejected' && $profile->rejection_reason)
            <div class="mt-4 rounded-md border border-danger bg-danger/10 px-4 py-3 text-sm text-danger">
                Your last edit was not approved: {{ $profile->rejection_reason }}
            </div>
        @endif

        {{-- Photos --}}
        <section class="card mt-6 p-6">
            <h2 class="card-title">Photos</h2>
            @if ($user->photos->isEmpty())
                <p class="mt-2 text-sm" style="color:var(--text-muted)">
                    No photos yet. <a href="{{ route('profile.wizard.step', 'photos') }}" class="font-medium text-primary-600">Add one</a> — profiles with a photo get far more interest.
                </p>
            @else
                <div class="mt-3 grid grid-cols-2 gap-3 sm:grid-cols-4">
                    @foreach ($user->photos as $photo)
                        <div class="relative aspect-[4/5] overflow-hidden rounded-lg border" style="border-color:var(--border-default)">
                            <img src="{{ $photo->urlFor('thumbnail') }}" alt="" class="h-full w-full object-cover">
                            @if ($photo->is_primary)
                                <span class="absolute left-2 top-2 rounded-full bg-primary-500 px-2 py-0.5 text-xs text-white">Primary</span>
                            @endif
                        </div>
                    @endforeach
                </div>
            @endif
        </section>

        {{-- Jadhagam (Horoscope) --}}
        <section class="card mt-6 p-6">
            <div class="flex items-center justify-between">
                <h2 class="card-title">Jadhagam (Horoscope)</h2>
                <a href="{{ route('profile.wizard.step', 'jadhagam') }}" class="text-xs font-medium text-primary-600 hover:underline">
                    Manage Jadhagam
                </a>
            </div>

            @if ($user->jadhagams->isEmpty())
                <p class="mt-2 text-sm" style="color:var(--text-muted)">
                    No Jadhagam uploaded yet. <a href="{{ route('profile.wizard.step', 'jadhagam') }}" class="font-medium text-primary-600">Upload one</a> to enhance match compatibility.
                </p>
            @else
                <div class="mt-3 grid grid-cols-2 gap-3 sm:grid-cols-4">
                    @foreach ($user->jadhagams as $jadhagam)
                        <div class="card overflow-hidden p-2">
                            <div class="relative aspect-[3/4] bg-neutral-100 rounded overflow-hidden">
                                <a href="{{ $jadhagam->url() }}" target="_blank">
                                    <img src="{{ $jadhagam->url() }}" alt="Jadhagam" class="h-full w-full object-contain">
                                </a>
                                <span @class([
                                    'absolute top-1 right-1 rounded-full px-2 py-0.5 text-xs font-semibold',
                                    'bg-warning text-white' => $jadhagam->approval_status === 'pending',
                                    'bg-success text-white' => $jadhagam->approval_status === 'approved',
                                    'bg-danger text-white' => $jadhagam->approval_status === 'rejected',
                                ])>{{ ucfirst($jadhagam->approval_status) }}</span>
                            </div>

                            @if ($jadhagam->approval_status === 'rejected')
                                <p class="mt-1 text-xs text-danger">Rejected: {{ $jadhagam->rejection_reason }}</p>
                            @elseif ($jadhagam->approval_status === 'approved')
                                <p class="mt-1 text-xs text-success font-medium">&#10003; Live on profile</p>
                            @else
                                <p class="mt-1 text-xs text-warning">&#9203; Pending admin review</p>
                            @endif
                        </div>
                    @endforeach
                </div>
            @endif
        </section>

        {{-- Basic --}}
        <section class="card mt-6 p-6">
            <h2 class="card-title">Basic details</h2>
            <dl class="mt-3 grid gap-x-6 gap-y-3 sm:grid-cols-2">
                <x-profile.detail-row label="Age" :value="$user->age().' years'" />
                <x-profile.detail-row label="Height" :value="$profile?->heightLabel()" />
                <x-profile.detail-row label="Marital status" :value="$profile?->maritalStatus?->name" />
                <x-profile.detail-row label="Physical status" :value="$profile?->physical_status" />
                <x-profile.detail-row label="This profile was created by" :value="$profile?->profile_created_by ? ucfirst($profile->profile_created_by) : null" />
            </dl>
        </section>

        {{-- Religious --}}
        <section class="card mt-6 p-6">
            <h2 class="card-title">Religion & background</h2>
            <dl class="mt-3 grid gap-x-6 gap-y-3 sm:grid-cols-2">
                <x-profile.detail-row label="Religion" :value="$profile?->religion?->name" />
                <x-profile.detail-row label="Community" :value="$profile?->community?->name" />
                <x-profile.detail-row label="Mother tongue" :value="$profile?->motherTongue?->name" />
                <x-profile.detail-row label="Caste" :value="$profile?->caste" />
                <x-profile.detail-row label="Star / Nakshatra" :value="$profile?->star" />
                <x-profile.detail-row label="Raasi / Moon sign" :value="$profile?->raasi" />
            </dl>
        </section>

        {{-- Education & career --}}
        <section class="card mt-6 p-6">
            <h2 class="card-title">Education & career</h2>
            <dl class="mt-3 grid gap-x-6 gap-y-3 sm:grid-cols-2">
                <x-profile.detail-row label="Education" :value="$profile?->education?->name" />
                <x-profile.detail-row label="Occupation" :value="$profile?->occupation?->name" />
                <x-profile.detail-row label="Company" :value="$profile?->company" />
                <x-profile.detail-row label="Annual income" :value="$profile?->incomeRange?->name" />
            </dl>
        </section>

        {{-- Location --}}
        <section class="card mt-6 p-6">
            <h2 class="card-title">Location</h2>
            <dl class="mt-3 grid gap-x-6 gap-y-3 sm:grid-cols-2">
                <x-profile.detail-row label="Country" :value="$profile?->country?->name" />
                <x-profile.detail-row label="State" :value="$profile?->state?->name" />
                <x-profile.detail-row label="City" :value="$profile?->city?->name" />
                <x-profile.detail-row label="Residency status" :value="$profile?->residency_status" />
            </dl>
        </section>

        {{-- Family --}}
        <section class="card mt-6 p-6">
            <h2 class="card-title">Family</h2>
            <dl class="mt-3 grid gap-x-6 gap-y-3 sm:grid-cols-2">
                <x-profile.detail-row label="Family type" :value="$profile?->family_type ? ucfirst($profile->family_type) : null" />
                <x-profile.detail-row label="Family status" :value="$profile?->family_status ? str($profile->family_status)->replace('_', ' ')->ucfirst() : null" />
                <x-profile.detail-row label="Family values" :value="$profile?->family_values ? ucfirst($profile->family_values) : null" />
                <x-profile.detail-row label="Father's occupation" :value="$profile?->father_occupation" />
                <x-profile.detail-row label="Mother's occupation" :value="$profile?->mother_occupation" />
            </dl>
            @if ($profile?->family_description)
                <p class="mt-3 text-sm">{{ $profile->family_description }}</p>
            @endif
        </section>

        {{-- Lifestyle --}}
        <section class="card mt-6 p-6">
            <h2 class="card-title">Lifestyle</h2>
            <dl class="mt-3 grid gap-x-6 gap-y-3 sm:grid-cols-2">
                <x-profile.detail-row label="Diet" :value="$profile?->diet" />
                <x-profile.detail-row label="Smoking" :value="$profile?->smoking" />
                <x-profile.detail-row label="Drinking" :value="$profile?->drinking" />
            </dl>
            @if (! empty($profile?->hobbies))
                <p class="mt-3 text-sm"><span style="color:var(--text-muted)">Hobbies:</span> {{ implode(', ', $profile->hobbies) }}</p>
            @endif
            @if (! empty($profile?->interests))
                <p class="mt-1 text-sm"><span style="color:var(--text-muted)">Interests:</span> {{ implode(', ', $profile->interests) }}</p>
            @endif
        </section>

        {{-- About --}}
        @if ($profile?->about_me || $profile?->expectations)
            <section class="card mt-6 p-6">
                <h2 class="card-title">About</h2>
                @if ($profile->about_me)
                    <p class="mt-3 text-sm">{{ $profile->about_me }}</p>
                @endif
                @if ($profile->expectations)
                    <p class="mt-3 text-sm"><span style="color:var(--text-muted)">Looking for:</span> {{ $profile->expectations }}</p>
                @endif
            </section>
        @endif

        {{-- Partner Preference (Step 8) --}}
        @php $prefs = $user->preferences; @endphp
        <section class="card mt-6 p-6">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <h2 class="card-title">Partner Preference</h2>
                <div class="flex items-center gap-3">
                    <a href="{{ route('matches.reference') }}" class="text-xs font-semibold text-primary-600 hover:underline">
                        View Reference Matches &rarr;
                    </a>
                    <a href="{{ route('profile.wizard.step', 'preferences') }}" class="rounded-md bg-primary-500 px-3 py-1.5 text-xs font-medium text-white hover:bg-primary-600">
                        Edit Preference
                    </a>
                </div>
            </div>

            @if (! $prefs || $prefs->isEmpty())
                <p class="mt-2 text-sm" style="color:var(--text-muted)">
                    No partner preferences set yet. <a href="{{ route('profile.wizard.step', 'preferences') }}" class="font-medium text-primary-600">Set your preferences</a> to view matching profiles in the Reference tab.
                </p>
            @else
                <dl class="mt-3 grid gap-x-6 gap-y-3 sm:grid-cols-2">
                    <x-profile.detail-row label="Age Range" :value="($prefs->age_min || $prefs->age_max) ? ($prefs->age_min ?? 18).' – '.($prefs->age_max ?? 70).' yrs' : 'Any'" />
                    <x-profile.detail-row label="Height Range" :value="($prefs->height_min_cm || $prefs->height_max_cm) ? ($prefs->height_min_cm ?? 122).' – '.($prefs->height_max_cm ?? 213).' cm' : 'Any'" />
                    <x-profile.detail-row label="Weight Range" :value="($prefs->weight_min_kg || $prefs->weight_max_kg) ? ($prefs->weight_min_kg ?? 35).' – '.($prefs->weight_max_kg ?? 140).' kg' : 'Any'" />
                    <x-profile.detail-row label="Color / Complexion" :value="!empty($prefs->complexions) ? implode(', ', $prefs->complexions) : 'Any'" />
                    <x-profile.detail-row label="Rasi" :value="!empty($prefs->raasis) ? implode(', ', $prefs->raasis) : 'Any'" />
                    <x-profile.detail-row label="Natchathiram" :value="!empty($prefs->stars) ? implode(', ', $prefs->stars) : 'Any'" />
                    <x-profile.detail-row label="Caste" :value="!empty($prefs->castes) ? implode(', ', $prefs->castes) : 'Any'" />
                </dl>
            @endif
        </section>
    </div>
@endsection
