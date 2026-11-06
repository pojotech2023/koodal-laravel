@extends('layouts.member')

@section('title', 'Complete your profile — '.config('app.name'))

@php
    $stepLabels = [
        'about-you' => 'About you',
        'religion-location' => 'Religion & location',
        'education-career' => 'Education & career',
        'family-lifestyle' => 'Family & lifestyle',
        'photos' => 'Photos',
        'jadhagam' => 'Jadhagam',
        'aadhaar' => 'Aadhaar verify',
        'preferences' => 'Preference',
    ];
    $position = array_search($step, $steps, true);
@endphp

@section('body')
    <div class="mx-auto max-w-3xl px-4 py-8">
        <h1 class="font-serif text-3xl font-bold text-ink-900">Complete your profile</h1>
        <p class="mt-1 text-sm" style="color:var(--text-muted)">
            Most of the member area needs a filled-in profile to be useful. It takes about five minutes.
        </p>

        <div class="mt-6">
            <div class="flex items-center justify-between text-sm">
                <span class="font-medium">Profile strength</span>
                <span id="profile-strength-pct" class="font-semibold text-primary-600">{{ $completion['completion_percentage'] }}%</span>
            </div>
            <div class="mt-2 h-2 w-full overflow-hidden rounded-full" style="background-color:var(--border-default)">
                <div id="profile-strength-bar" class="h-full rounded-full bg-primary-500 transition-all duration-500" style="width: {{ $completion['completion_percentage'] }}%"></div>
            </div>
        </div>

        <ol class="mt-6 flex gap-2 overflow-x-auto pb-1">
            @foreach ($steps as $key)
                <li>
                    <a
                        href="{{ route('profile.wizard.step', $key) }}"
                        @class([
                            'block whitespace-nowrap rounded-full border px-3 py-1.5 text-xs font-medium',
                            'border-primary-500 bg-primary-50 text-primary-700' => $key === $step,
                        ])
                        @style($key === $step ? [] : ['border-color' => 'var(--border-default)'])
                    >
                        {{ $loop->iteration }}. {{ $stepLabels[$key] }}
                    </a>
                </li>
            @endforeach
        </ol>

        {{-- Each partial owns its <form> (or, for `photos`, its own upload
             form) and renders the Back/Continue footer row itself — Back is
             a plain link, so it lives inside the form without submitting
             it. --}}
        <div class="card mt-6 p-6">
            @include('profile.wizard.'.$step)
        </div>
    </div>
@endsection
