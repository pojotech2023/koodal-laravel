{{-- The type-specific half of a lookup row's form, shared by the "add" form
     and every inline edit row so the two can never offer different fields —
     which is how a create form ends up writing a row the edit form cannot
     then fix. `$row` is null on create.

     `$form` is the id of the row's <form> element for the inline edit rows,
     whose fields live in sibling table cells and associate with it by id; it
     is null in the add form, where the fields sit inside the form already.

     Field names match `MasterDataRequest`'s rules exactly; anything not listed
     there is not rendered here. --}}
@php
    // A plain partial, not a component — `@include` has no `@props`, so the
    // optional variables are defaulted here instead.
    $row ??= null;
    $form ??= null;
    $religions ??= collect();
    $communities ??= collect();
@endphp

@switch($type)
    @case('communities')
        <select name="religion_id" required aria-label="Religion" @if ($form) form="{{ $form }}" @endif
                class="rounded-md border px-2 py-1.5 text-sm" style="border-color:var(--border-default)">
            <option value="">Religion…</option>
            @foreach ($religions as $religion)
                <option value="{{ $religion->id }}" @selected($row?->religion_id === $religion->id)>{{ $religion->name }}</option>
            @endforeach
        </select>
        @break

    @case('sub-communities')
        <select name="community_id" required aria-label="Community" @if ($form) form="{{ $form }}" @endif
                class="rounded-md border px-2 py-1.5 text-sm" style="border-color:var(--border-default)">
            <option value="">Community…</option>
            @foreach ($communities as $community)
                <option value="{{ $community->id }}" @selected($row?->community_id === $community->id)>{{ $community->name }}</option>
            @endforeach
        </select>
        @break

    @case('educations')
    @case('occupations')
        <input type="text" name="category" value="{{ $row?->category }}" maxlength="40" placeholder="Category"
               aria-label="Category" @if ($form) form="{{ $form }}" @endif
               class="w-32 rounded-md border px-2 py-1.5 text-sm" style="border-color:var(--border-default)">
        @break

    @case('income-ranges')
        <input type="number" name="min_amount" value="{{ $row?->min_amount }}" min="0" required placeholder="Min"
               aria-label="Minimum amount" @if ($form) form="{{ $form }}" @endif
               class="w-28 rounded-md border px-2 py-1.5 text-sm" style="border-color:var(--border-default)">

        {{-- Empty max is "and above", which is how the top band is expressed —
             not a missing value. --}}
        <input type="number" name="max_amount" value="{{ $row?->max_amount }}" min="0" placeholder="Max (blank = and above)"
               aria-label="Maximum amount" @if ($form) form="{{ $form }}" @endif
               class="w-40 rounded-md border px-2 py-1.5 text-sm" style="border-color:var(--border-default)">
        @break

    @case('lifestyle-options')
        {{-- Five sub-lists share this table; `type` is what makes a row mean
             anything, and the slug is unique per type rather than globally. --}}
        <select name="type" required aria-label="Option type" @if ($form) form="{{ $form }}" @endif
                class="rounded-md border px-2 py-1.5 text-sm" style="border-color:var(--border-default)">
            <option value="">Type…</option>
            @foreach (['diet', 'smoking', 'drinking', 'body_type', 'complexion'] as $option)
                <option value="{{ $option }}" @selected($row?->type === $option)>{{ str_replace('_', ' ', ucfirst($option)) }}</option>
            @endforeach
        </select>
        @break
@endswitch
