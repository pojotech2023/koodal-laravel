{{-- Closed-by-default checkbox dropdown for multi-value filters (Religion,
     Marital status, Mother tongue, …). Renders plain `<input type="checkbox"
     name="{{ $name }}[]">` inputs, so it still works inside the search page's
     plain GET form with no JS on submit — Alpine only toggles the panel and
     keeps the closed-state summary text in sync with what's checked. --}}
@props(['name', 'label', 'options' => [], 'selected' => [], 'valueKey' => 'id'])

@php
    $selected = array_map('strval', $selected);
    $optionsJson = collect($options)->map(fn ($option) => [
        'value' => (string) $option[$valueKey],
        'name' => $option['name'],
    ])->all();
@endphp

<div
    x-data="{
        open: false,
        checked: {{ Illuminate\Support\Js::from($selected) }},
        options: {{ Illuminate\Support\Js::from($optionsJson) }},
        get label() {
            if (this.checked.length === 0) return null;
            return this.options.filter(o => this.checked.includes(o.value)).map(o => o.name).join(', ');
        },
    }"
    class="relative"
>
    <label class="block text-sm font-medium">{{ $label }}</label>

    <button
        type="button"
        @click="open = !open"
        @click.outside="open = false"
        :aria-expanded="open"
        class="mt-1 flex w-full items-center justify-between rounded-md border bg-white px-3 py-2 text-left text-sm"
        style="border-color:var(--border-default)"
    >
        <span x-text="label" x-show="label" class="truncate"></span>
        <span x-show="!label" style="color:var(--text-muted)">Any</span>
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-4 w-4 shrink-0" style="color:var(--text-muted)">
            <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd" />
        </svg>
    </button>

    <div
        x-cloak
        x-show="open"
        x-transition
        class="absolute z-30 mt-1 w-full overflow-hidden rounded-md border bg-white shadow-lg"
        style="border-color:var(--border-default)"
    >
        @if (count($options) > 1)
            <div class="flex items-center justify-between border-b bg-white px-3 py-2 text-xs" style="border-color:var(--border-default)">
                <button
                    type="button"
                    @click="checked = options.map(o => o.value)"
                    class="font-semibold text-primary-600 hover:underline"
                >
                    Select All
                </button>
                <span class="text-ink-400" x-text="checked.length + ' selected'"></span>
                <button
                    type="button"
                    @click="checked = []"
                    class="font-medium text-ink-500 hover:underline"
                >
                    Clear
                </button>
            </div>
        @endif
        <div class="kg-left-scroll p-1.5" style="max-height: 220px; overflow-y: auto; direction: rtl;">
            <div class="kg-left-scroll-inner space-y-0.5" style="direction: ltr;">
                @foreach ($options as $option)
                    <label class="flex cursor-pointer items-center gap-2 rounded px-2 py-1.5 text-sm hover:bg-pink-50/60">
                        <input
                            type="checkbox"
                            name="{{ $name }}[]"
                            value="{{ $option[$valueKey] }}"
                            x-model="checked"
                            @checked(in_array((string) $option[$valueKey], $selected, true))
                            class="rounded text-primary-600 focus:ring-primary-500"
                        >
                        <span>{{ $option['name'] }}</span>
                    </label>
                @endforeach
            </div>
        </div>
    </div>
</div>
