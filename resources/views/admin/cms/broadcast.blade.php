@extends('admin.layouts.admin')

@section('title', 'Announcement — '.config('app.name').' Admin')

@section('admin-body')
    <h1 class="font-serif text-3xl font-bold text-ink-900">Announcement</h1>
    <p class="mt-1 text-sm" style="color:var(--text-muted)">
        A push notification to every member matching the filters. There is no recall once it is queued.
    </p>

    <div class="mt-4">
        @include('admin.cms._nav', ['type' => 'broadcast'])
    </div>

    {{-- No <form> wrapper here. Every field below carries
         `form="broadcast-form"` and submits with the confirm dialog's own
         form (`form-id` on the component) — nesting the dialog's <form>
         inside a page-level one is invalid HTML and browsers drop the inner
         one silently, which would submit the announcement with no
         confirmation and no fields. --}}
    <div class="mt-6 max-w-2xl space-y-4">
        <x-form.input name="title" label="Title" required maxlength="120" form="broadcast-form" />

        <div>
            <label for="body" class="block text-sm font-medium">Message</label>
            <textarea id="body" name="body" rows="3" required maxlength="180" form="broadcast-form"
                      class="mt-1 w-full rounded-md border px-3 py-2 text-sm" style="border-color:var(--border-default)">{{ old('body') }}</textarea>
            {{-- 180 is not arbitrary: both app stores truncate past roughly
                 that, so copy written to a longer limit reads fine here and
                 ends mid-sentence on a phone. --}}
            <p class="mt-1 text-xs" style="color:var(--text-muted)">
                Under 180 characters — both platforms truncate past that, and it will read fine here and badly on a phone.
            </p>
        </div>

        <x-form.input name="route" label="Deep link" :value="old('route')" maxlength="120" form="broadcast-form"
            hint="An in-app path such as /membership/plans. Not a URL — an announcement must not be able to send the member base to another domain." />

        <fieldset class="card p-5">
            <legend class="px-1 text-sm font-semibold">Audience</legend>
            {{-- An allow-list, not a free query: this can put a notification
                 on every phone in the member base, and everything here
                 narrows. Leaving it all blank sends to every active member,
                 which is already the widest it goes. --}}
            <p class="text-xs" style="color:var(--text-muted)">
                Every filter narrows. Leave them blank to reach every active member — that is as wide as this goes.
            </p>

            <div class="mt-3 grid grid-cols-1 gap-4 sm:grid-cols-2">
                <x-form.select name="audience[gender]" label="Gender" placeholder="Everyone" form="broadcast-form" :options="[
                    ['id' => 'male', 'name' => 'Male'],
                    ['id' => 'female', 'name' => 'Female'],
                ]" />

                <x-form.select name="audience[status]" label="Account status" placeholder="Active members" form="broadcast-form" :options="[
                    ['id' => 'active', 'name' => 'Active'],
                    ['id' => 'pending', 'name' => 'Pending'],
                ]" />

                <x-form.select name="audience[plan_id]" label="On plan" placeholder="Any plan" form="broadcast-form"
                    :options="$plans->map(fn ($p) => ['id' => $p->id, 'name' => $p->name])->all()" />

                <x-form.select name="audience[registration_source]" label="Signed up on" placeholder="Anywhere" form="broadcast-form" :options="[
                    ['id' => 'web', 'name' => 'Web'],
                    ['id' => 'android', 'name' => 'Android'],
                    ['id' => 'ios', 'name' => 'iOS'],
                ]" />
            </div>

            <label class="mt-3 flex items-center gap-2 text-sm font-medium">
                <input type="hidden" name="audience[paid_only]" value="0" form="broadcast-form">
                <input type="checkbox" name="audience[paid_only]" value="1" form="broadcast-form" class="rounded border" style="border-color:var(--border-default)">
                Paying members only
            </label>
        </fieldset>

        {{-- Typed confirmation, because this is the one action in the panel
             whose blast radius is the entire member base and which nothing can
             undo — docs/06-admin-panel-guide.md §7's test, even though the
             guide's own examples are all deletions. --}}
        <x-admin.confirm-dialog
            id="send-announcement" form-id="broadcast-form"
            trigger="Send announcement" variant="danger"
            title="Send this to everyone matching the filters?"
            :action="route('admin.cms.broadcast')"
            identifier="SEND" confirm-label="Queue announcement"
        >
            <p style="color:var(--text-muted)">
                The message and audience above are sent as they are on screen. Once queued it cannot be recalled, edited or unsent.
            </p>
        </x-admin.confirm-dialog>
    </div>
@endsection
