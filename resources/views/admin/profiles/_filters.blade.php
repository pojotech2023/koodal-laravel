{{-- Plain GET, same as Members' filters — a filtered queue is a shareable
     URL, per docs/06-admin-panel-guide.md §4. The status control is rendered
     as tabs rather than a <select>: "pending" is the queue and the other two
     are audits of past decisions, and a moderator switches between them far
     more often than they touch any other filter. --}}
<div class="flex flex-wrap items-end gap-3">
    <nav class="flex rounded-md border" style="border-color:var(--border-default)" aria-label="Queue status">
        @foreach (['pending' => 'Pending', 'approved' => 'Approved', 'rejected' => 'Rejected'] as $value => $label)
            <a href="{{ route('admin.profiles.index', ['status' => $value] + request()->only('gender', 'q')) }}"
               @class([
                   'px-4 py-2 text-sm font-medium first:rounded-l-md last:rounded-r-md',
                   'bg-primary-50 text-primary-700' => $status === $value,
               ])
               @if ($status === $value) aria-current="page" @endif>
                {{ $label }}
                @if ($value === 'pending' && $pendingCount > 0)
                    <span class="ml-1 rounded-full bg-warning/15 px-1.5 py-0.5 text-xs text-warning">{{ $pendingCount }}</span>
                @endif
            </a>
        @endforeach
    </nav>

    <input type="hidden" name="status" value="{{ $status }}">

    <div>
        <label for="q" class="block text-sm font-medium">Search</label>
        <input id="q" name="q" type="text" value="{{ request('q') }}" maxlength="120"
               placeholder="Name or MAT code"
               class="mt-1 w-56 rounded-md border px-3 py-2 text-sm" style="border-color:var(--border-default)">
    </div>

    <x-form.select name="gender" label="Gender" placeholder="Any gender" :value="request('gender')" :options="[
        ['id' => 'male', 'name' => 'Male'],
        ['id' => 'female', 'name' => 'Female'],
    ]" />

    <div class="flex items-end gap-2">
        <button type="submit" class="rounded-md bg-primary-500 px-4 py-2 text-sm font-medium text-white hover:bg-primary-600">
            Filter
        </button>
        <a href="{{ route('admin.profiles.index') }}" class="rounded-md border px-4 py-2 text-sm font-medium" style="border-color:var(--border-default)">
            Clear
        </a>
    </div>
</div>
