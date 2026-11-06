@props(['name', 'label', 'type' => 'text', 'value' => null, 'hint' => null])

<div>
    <label for="{{ $name }}" class="block text-sm font-medium">{{ $label }}</label>
    <input
        id="{{ $name }}" name="{{ $name }}" type="{{ $type }}"
        value="{{ old($name, $value) }}"
        {{ $attributes }}
        class="mt-1 w-full rounded-md border px-3 py-2 text-sm"
        style="border-color:var(--border-default)"
    >
    @if ($hint)
        <p class="mt-1 text-xs" style="color:var(--text-muted)">{{ $hint }}</p>
    @endif
</div>
