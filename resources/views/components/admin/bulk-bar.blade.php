@props(['form', 'label' => 'selected', 'reasons' => []])

{{-- The bulk bar `<x-admin.data-table>`'s `bulkActions` slot was built for —
     unused until the photo queue, which is the first module with a genuine
     batch endpoint behind it (`ModeratePhoto::bulk()`), per PROGRESS.md §4's
     note to build this against a real caller rather than speculatively.

     **Nothing here is nested inside anything.** The caller renders one empty
     `<form id="…" method="POST">` and every checkbox in its rows carries
     `form="…"` — HTML5 form association, not DOM containment. That is not a
     stylistic choice: a moderation row needs its own per-item approve/reject
     forms, and a batch form wrapped round the table would make those nested
     `<form>`s, which browsers silently drop. Association keeps both, with no
     JS holding it together.

     The checkboxes themselves belong to the caller (only it knows what a row
     is); this renders the select-all, the live count, the reason picker and
     the two submits.

     No-JS path first, same rule as the queue itself: Alpine only counts and
     drives select-all. With Alpine dead the buttons still submit whatever is
     ticked — they just read "Approve" instead of "Approve 12" and never
     disable. --}}
<div
    x-data="{
        count: 0,
        boxes() {
            const form = document.getElementById('{{ $form }}')
            // .elements, not querySelectorAll: the checkboxes are associated
            // with the form by its id, not contained in it, so they are in
            // this collection and in no descendant query.
            return form ? [...form.elements].filter(el => el.name === 'ids[]') : []
        },
        recount() { this.count = this.boxes().filter(box => box.checked).length },
        toggleAll(checked) {
            this.boxes().forEach(box => { box.checked = checked })
            this.recount()
        },
    }"
    x-init="recount()"
    x-on:change.window="recount()"
    class="flex flex-wrap items-center gap-3 rounded-md border px-3 py-2"
    style="border-color:var(--border-default);background-color:var(--bg-surface)"
>
    <label class="flex items-center gap-2 text-sm font-medium">
        <input type="checkbox" x-on:change="toggleAll($event.target.checked)" class="rounded border" style="border-color:var(--border-default)">
        Select all on this page
    </label>

    <span class="text-sm" style="color:var(--text-muted)" x-text="count ? count + ' {{ $label }}' : 'None {{ $label }}'">None {{ $label }}</span>

    <div class="ml-auto flex flex-wrap items-end gap-2">
        {{-- One picker serves both buttons: the batch request requires a
             reason only when the action is `reject` and ignores it otherwise,
             and it validates against the same configured list a single
             rejection uses — a batch is not a way around the picker. --}}
        @if ($reasons)
            <x-form.select name="reason" label="Rejection reason" placeholder="Choose a reason" :form="$form"
                :options="collect($reasons)->map(fn ($r) => ['id' => $r, 'name' => $r])->all()" />
        @endif

        <button type="submit" form="{{ $form }}" name="action" value="approve"
                x-bind:disabled="count === 0"
                class="rounded-md bg-success px-3 py-2 text-sm font-medium text-white hover:opacity-90 disabled:cursor-not-allowed disabled:opacity-40">
            Approve <span x-text="count || ''"></span>
        </button>

        <button type="submit" form="{{ $form }}" name="action" value="reject"
                x-bind:disabled="count === 0"
                class="rounded-md border border-danger px-3 py-2 text-sm font-medium text-danger hover:bg-danger/10 disabled:cursor-not-allowed disabled:opacity-40">
            Reject <span x-text="count || ''"></span>
        </button>
    </div>

    {{ $slot }}
</div>
