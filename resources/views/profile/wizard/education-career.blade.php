{{-- Step 3 — `education` and `professional`. --}}
@php
    $jobTypes = [
        ['id' => 'govt', 'name' => 'Government'], ['id' => 'private', 'name' => 'Private'],
        ['id' => 'business', 'name' => 'Business'], ['id' => 'self_employed', 'name' => 'Self-employed'],
        ['id' => 'defence', 'name' => 'Defence'], ['id' => 'not_working', 'name' => 'Not working'],
    ];
@endphp

<form method="POST" action="{{ route('profile.wizard.education-career') }}">
    @csrf

    <h3 class="text-sm font-semibold" style="color:var(--text-muted)">Education</h3>
    <div class="mt-3 grid gap-4 sm:grid-cols-2">
        <x-form.select name="education_id" label="Highest education" placeholder="Select" :options="$options['educations']" :value="$profile?->education_id" />
        <x-form.input name="education_detail" label="Education detail" hint="e.g. B.Tech Computer Science" :value="$profile?->education_detail" />
        <x-form.input name="college" label="College" :value="$profile?->college" />
        <x-form.input name="university" label="University" :value="$profile?->university" />
        <x-form.input name="specialization" label="Specialization" :value="$profile?->specialization" />
    </div>

    <h3 class="mt-6 text-sm font-semibold" style="color:var(--text-muted)">Career</h3>
    <div class="mt-3 grid gap-4 sm:grid-cols-2">
        <x-form.select name="occupation_id" label="Occupation" placeholder="Select" :options="$options['occupations']" :value="$profile?->occupation_id" />
        <x-form.input name="company" label="Company" :value="$profile?->company" />
        <x-form.select name="job_type" label="Job type" placeholder="Select" :options="$jobTypes" :value="$profile?->job_type" />
        <x-form.select name="income_range_id" label="Annual income" placeholder="Prefer not to say" :options="$options['income_ranges']" :value="$profile?->income_range_id" />
    </div>

    @include('profile.wizard._footer')
</form>
