{{-- One location row's fields, shared by the add form and every inline edit
     row so the two cannot offer different sets.

     `$nameFields` is false on the edit rows, where name and slug live in their
     own table cells; the add form renders everything in one line. `$form` is
     the id of the row's <form> element — the edit fields sit in sibling cells
     and associate with it by id, because a <form> cannot span table cells. --}}
@php
    // A plain partial, not a component — `@include` has no `@props`.
    $row ??= null;
    $form ??= null;
    $nameFields ??= true;
    $countries ??= collect();
    $states ??= collect();
@endphp

@if ($nameFields)
    <input type="text" name="name" value="{{ $row?->name }}" required maxlength="120" placeholder="Name" aria-label="Name"
           @if ($form) form="{{ $form }}" @endif
           class="w-48 rounded-md border px-2 py-1.5 text-sm" style="border-color:var(--border-default)">

    {{-- Unique within the parent, not globally: there is a Hyderabad in
         Telangana and one in Sindh, and a global unique would let whichever
         was imported first block the other forever. --}}
    <input type="text" name="slug" value="{{ $row?->slug }}" required maxlength="140" placeholder="slug" aria-label="Slug"
           @if ($form) form="{{ $form }}" @endif
           class="w-40 rounded-md border px-2 py-1.5 font-mono text-sm" style="border-color:var(--border-default)">
@endif

@switch($type)
    @case('countries')
        <input type="text" name="iso2" value="{{ $row?->iso2 }}" required maxlength="2" placeholder="ISO2" aria-label="ISO2 code"
               @if ($form) form="{{ $form }}" @endif
               class="w-20 rounded-md border px-2 py-1.5 text-sm uppercase" style="border-color:var(--border-default)">

        <input type="text" name="iso3" value="{{ $row?->iso3 }}" required maxlength="3" placeholder="ISO3" aria-label="ISO3 code"
               @if ($form) form="{{ $form }}" @endif
               class="w-20 rounded-md border px-2 py-1.5 text-sm uppercase" style="border-color:var(--border-default)">

        {{-- A string with its `+`: it is dialled, not summed, and an integer
             loses the leading zero on the handful of codes that have one. --}}
        <input type="text" name="phone_code" value="{{ $row?->phone_code }}" required maxlength="8" placeholder="+91" aria-label="Phone code"
               @if ($form) form="{{ $form }}" @endif
               class="w-24 rounded-md border px-2 py-1.5 text-sm" style="border-color:var(--border-default)">

        <input type="number" name="sort_order" value="{{ $row?->sort_order ?? 0 }}" min="0" max="65535" aria-label="Sort order"
               @if ($form) form="{{ $form }}" @endif
               class="w-20 rounded-md border px-2 py-1.5 text-sm" style="border-color:var(--border-default)">
        @break

    @case('states')
        <select name="country_id" required aria-label="Country" @if ($form) form="{{ $form }}" @endif
                class="rounded-md border px-2 py-1.5 text-sm" style="border-color:var(--border-default)">
            <option value="">Country…</option>
            @foreach ($countries as $country)
                <option value="{{ $country->id }}" @selected($row?->country_id === $country->id)>{{ $country->name }}</option>
            @endforeach
        </select>

        <input type="text" name="code" value="{{ $row?->code }}" maxlength="10" placeholder="Code" aria-label="State code"
               @if ($form) form="{{ $form }}" @endif
               class="w-24 rounded-md border px-2 py-1.5 text-sm" style="border-color:var(--border-default)">
        @break

    @case('cities')
        <select name="state_id" required aria-label="State" @if ($form) form="{{ $form }}" @endif
                class="rounded-md border px-2 py-1.5 text-sm" style="border-color:var(--border-default)">
            <option value="">State…</option>
            @foreach ($states as $state)
                <option value="{{ $state->id }}" @selected($row?->state_id === $state->id)>
                    {{ $state->name }}@if ($state->country) ({{ $state->country->name }}) @endif
                </option>
            @endforeach
        </select>

        <input type="number" name="latitude" value="{{ $row?->latitude }}" step="0.000001" min="-90" max="90" placeholder="Lat" aria-label="Latitude"
               @if ($form) form="{{ $form }}" @endif
               class="w-28 rounded-md border px-2 py-1.5 text-sm" style="border-color:var(--border-default)">

        <input type="number" name="longitude" value="{{ $row?->longitude }}" step="0.000001" min="-180" max="180" placeholder="Lng" aria-label="Longitude"
               @if ($form) form="{{ $form }}" @endif
               class="w-28 rounded-md border px-2 py-1.5 text-sm" style="border-color:var(--border-default)">
        @break
@endswitch
