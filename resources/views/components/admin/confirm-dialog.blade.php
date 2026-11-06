@props(['id', 'trigger', 'title', 'action', 'method' => 'POST', 'identifier', 'confirmLabel' => 'Confirm', 'variant' => 'danger', 'formId' => null])

{{-- Every irreversible admin action goes through this one component —
     docs/06-admin-panel-guide.md §7: a native `<dialog>`, Alpine-driven,
     whose submit button stays disabled until the staff member has typed the
     record's own identifier (its profile code here; a payment reference or
     plan name elsewhere) back exactly. Alpine only decides when the button
     is clickable — the request behind it is a plain
     `<form method="POST">` with a `@method` spoof, so this works with no JS
     at all if Alpine fails to load; it would just never disable the button,
     same "no-JS path first" rule the guide states for chat and the photo
     queue.

     A slot, not more props, carries whatever the action needs beyond the
     identifier — e.g. a `reason` textarea for suspend/block/delete.

     `form-id` names this component's own `<form>`, for the case the slot
     cannot cover: fields that must live on the page rather than inside the
     dialog — a long compose form whose submit needs confirming, not a
     one-line reason. Those fields carry `form="<that id>"` and submit with
     this dialog, which is the only way to have both without nesting one
     `<form>` inside another. The announcement composer is the caller that
     needed it; every other one leaves it null. --}}
<div x-data="{ typed: '' }">
    <button type="button" x-on:click="typed = ''; $nextTick(() => $refs['{{ $id }}'].showModal())"
            class="rounded-md border px-3 py-1.5 text-sm font-medium {{ $variant === 'danger' ? 'border-danger text-danger hover:bg-danger/10' : 'border-primary-500 text-primary-600 hover:bg-primary-50' }}">
        {{ $trigger }}
    </button>

    <dialog x-ref="{{ $id }}" class="w-full max-w-md rounded-lg border p-0" style="border-color:var(--border-default)">
        <form method="POST" action="{{ $action }}" class="p-5" @if ($formId) id="{{ $formId }}" @endif>
            @csrf
            @if (strtoupper($method) !== 'POST')
                @method($method)
            @endif

            <h2 class="text-base font-semibold">{{ $title }}</h2>

            <div class="mt-4 space-y-3 text-sm">
                {{ $slot }}
            </div>

            <div class="mt-4">
                <label class="block text-sm font-medium">
                    Type <span class="font-mono">{{ $identifier }}</span> to confirm
                </label>
                <input type="text" x-model="typed" autocomplete="off"
                       class="mt-1 w-full rounded-md border px-3 py-2 text-sm" style="border-color:var(--border-default)">
            </div>

            <div class="mt-5 flex justify-end gap-2">
                <button type="button" x-on:click="$refs['{{ $id }}'].close()"
                        class="rounded-md border px-3 py-1.5 text-sm font-medium" style="border-color:var(--border-default)">
                    Cancel
                </button>
                <button type="submit" x-bind:disabled="typed !== @js((string) $identifier)"
                        class="rounded-md bg-danger px-3 py-1.5 text-sm font-medium text-white disabled:cursor-not-allowed disabled:opacity-40">
                    {{ $confirmLabel }}
                </button>
            </div>
        </form>
    </dialog>
</div>
