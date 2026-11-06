{{-- Step 1 — the `basic` section. Confusingly not the `about_me` textarea
     (that's saved on `family-lifestyle`) — see the class docblock on
     Web\ProfileController for why. --}}
@php
    $createdBy = [
        ['id' => 'self', 'name' => 'Myself'], ['id' => 'parent', 'name' => 'Parent'],
        ['id' => 'sibling', 'name' => 'Sibling'], ['id' => 'relative', 'name' => 'Relative'],
        ['id' => 'friend', 'name' => 'Friend'],
    ];
    $physicalStatuses = [['id' => 'normal', 'name' => 'Normal'], ['id' => 'physically_challenged', 'name' => 'Physically challenged']];
    $childrenLiving = [
        ['id' => 'together', 'name' => 'Living with me'],
        ['id' => 'not_together', 'name' => 'Not living with me'],
        ['id' => 'none', 'name' => 'No children'],
    ];
@endphp

<form method="POST" action="{{ route('profile.wizard.about-you') }}">
    @csrf

    <div class="grid gap-4 sm:grid-cols-2">
        <x-form.select name="profile_created_by" label="This profile is for" :options="$createdBy" :value="$profile?->profile_created_by" />
        <x-form.select name="marital_status_id" label="Marital status" placeholder="Select" :options="$options['marital_statuses']" :value="$profile?->marital_status_id" />
        <x-form.input name="height_cm" label="Height (cm)" type="number" min="120" max="220" hint="120–220 cm" :value="$profile?->height_cm" />
        <x-form.input name="weight_kg" label="Weight (kg)" type="number" min="30" max="200" :value="$profile?->weight_kg" />
        <x-form.select name="body_type" label="Body type" placeholder="Select" :options="$options['body_types']" :value="$profile?->body_type" value-key="name" />
        <x-form.select name="complexion" label="Complexion" placeholder="Select" :options="$options['complexions']" :value="$profile?->complexion" value-key="name" />
        <x-form.select name="physical_status" label="Physical status" :options="$physicalStatuses" :value="$profile?->physical_status ?? 'normal'" />
        <x-form.input name="children_count" label="Children" type="number" min="0" max="20" :value="$profile?->children_count" />
        <x-form.select name="children_living_status" label="Children living status" placeholder="Select" :options="$childrenLiving" :value="$profile?->children_living_status" />
    </div>

    @include('profile.wizard._footer')
</form>
