{{-- Step 4 — `family`, `lifestyle` and `about` (the about_me/expectations
     textareas — the "About you" copy lives on this step, not the
     `about-you` one; see the class docblock on Web\ProfileController).
     Hobbies/interests are one comma-separated text field each —
     UpdateLifestyleRequest::prepareForValidation() splits it into the array
     the API contract expects. --}}
@php
    $familyType = [['id' => 'joint', 'name' => 'Joint family'], ['id' => 'nuclear', 'name' => 'Nuclear family']];
    $familyStatus = [
        ['id' => 'middle_class', 'name' => 'Middle class'], ['id' => 'upper_middle', 'name' => 'Upper middle class'],
        ['id' => 'rich', 'name' => 'Rich'], ['id' => 'affluent', 'name' => 'Affluent'],
    ];
    $familyValues = [['id' => 'traditional', 'name' => 'Traditional'], ['id' => 'moderate', 'name' => 'Moderate'], ['id' => 'liberal', 'name' => 'Liberal']];
@endphp

<form method="POST" action="{{ route('profile.wizard.family-lifestyle') }}">
    @csrf

    <h3 class="text-sm font-semibold" style="color:var(--text-muted)">Family</h3>
    <div class="mt-3 grid gap-4 sm:grid-cols-2">
        <x-form.select name="family_type" label="Family type" placeholder="Select" :options="$familyType" :value="$profile?->family_type" />
        <x-form.select name="family_status" label="Family status" placeholder="Select" :options="$familyStatus" :value="$profile?->family_status" />
        <x-form.select name="family_values" label="Family values" placeholder="Select" :options="$familyValues" :value="$profile?->family_values" />
        <x-form.input name="family_location" label="Family location" :value="$profile?->family_location" />
        <x-form.input name="father_occupation" label="Father's occupation" :value="$profile?->father_occupation" />
        <x-form.input name="mother_occupation" label="Mother's occupation" :value="$profile?->mother_occupation" />
        <x-form.input name="brothers_count" label="Brothers" type="number" min="0" max="20" :value="$profile?->brothers_count" />
        <x-form.input name="brothers_married" label="…of whom married" type="number" min="0" max="20" :value="$profile?->brothers_married" />
        <x-form.input name="sisters_count" label="Sisters" type="number" min="0" max="20" :value="$profile?->sisters_count" />
        <x-form.input name="sisters_married" label="…of whom married" type="number" min="0" max="20" :value="$profile?->sisters_married" />
    </div>
    <div class="mt-4">
        <x-form.textarea name="family_description" label="About your family" hint="Optional — a couple of lines a prospective match reads first" :value="$profile?->family_description" />
    </div>

    <h3 class="mt-6 text-sm font-semibold" style="color:var(--text-muted)">Lifestyle</h3>
    <div class="mt-3 grid gap-4 sm:grid-cols-2">
        <x-form.select name="diet" label="Diet" placeholder="Select" :options="$options['diets']" :value="$profile?->diet" value-key="name" />
        <x-form.select name="smoking" label="Smoking" placeholder="Select" :options="$options['smoking']" :value="$profile?->smoking" value-key="name" />
        <x-form.select name="drinking" label="Drinking" placeholder="Select" :options="$options['drinking']" :value="$profile?->drinking" value-key="name" />
    </div>
    <div class="mt-4 grid gap-4 sm:grid-cols-2">
        <x-form.input name="hobbies" label="Hobbies" hint="Comma-separated, up to 20" :value="$profile?->hobbies ? implode(', ', $profile->hobbies) : null" />
        <x-form.input name="interests" label="Interests" hint="Comma-separated, up to 20" :value="$profile?->interests ? implode(', ', $profile->interests) : null" />
    </div>

    <h3 class="mt-6 text-sm font-semibold" style="color:var(--text-muted)">About you</h3>
    <div class="mt-3 flex flex-col gap-4">
        <x-form.textarea name="about_me" label="Describe yourself" hint="What a prospective match should know about you" :value="$profile?->about_me" />
        <x-form.textarea name="expectations" label="What you're looking for" :value="$profile?->expectations" />
    </div>

    @include('profile.wizard._footer')
</form>
