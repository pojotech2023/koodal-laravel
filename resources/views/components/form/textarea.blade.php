@props(['name', 'label', 'value' => null, 'hint' => null])

<div>
    <label for="{{ $name }}" class="block text-sm font-medium">{{ $label }}</label>
    <textarea
        id="{{ $name }}" name="{{ $name }}"
        {{-- merge(), not a hardcoded rows="4" before $attributes: HTML takes
             the FIRST of a duplicated attribute, so a caller passing rows="6"
             was silently getting four. --}}
        {{ $attributes->merge(['rows' => 4]) }}
        class="mt-1 w-full rounded-md border px-3 py-2 text-sm"
        style="border-color:var(--border-default)"
    >{{ old($name, $value) }}</textarea>
    @if ($hint)
        <p class="mt-1 text-xs" style="color:var(--text-muted)">{{ $hint }}</p>
    @endif
</div>
