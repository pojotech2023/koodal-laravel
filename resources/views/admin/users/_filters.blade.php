{{-- Plain GET — a filtered, sorted table view is a shareable, bookmarkable
     URL, per docs/06-admin-panel-guide.md §4. No Alpine auto-submit yet
     (mentioned there as a progressive enhancement); the explicit Filter
     button is the whole interaction for this first pass. --}}
<div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-6">
    <div class="col-span-2 sm:col-span-2">
        <label for="q" class="block text-sm font-medium">Search</label>
        <input id="q" name="q" type="text" value="{{ request('q') }}" maxlength="120"
               placeholder="Name, MAT code, email or mobile"
               class="mt-1 w-full rounded-md border px-3 py-2 text-sm" style="border-color:var(--border-default)">
    </div>

    <x-form.select name="status" label="Status" placeholder="Any status" :value="request('status')" :options="[
        ['id' => 'pending', 'name' => 'Pending'],
        ['id' => 'active', 'name' => 'Active'],
        ['id' => 'suspended', 'name' => 'Suspended'],
        ['id' => 'deactivated', 'name' => 'Deactivated'],
        ['id' => 'deleted', 'name' => 'Deleted'],
    ]" />

    <x-form.select name="gender" label="Gender" placeholder="Any gender" :value="request('gender')" :options="[
        ['id' => 'male', 'name' => 'Male'],
        ['id' => 'female', 'name' => 'Female'],
    ]" />

    <x-form.select name="approval_status" label="Approval" placeholder="Any" :value="request('approval_status')" :options="[
        ['id' => 'pending', 'name' => 'Pending'],
        ['id' => 'approved', 'name' => 'Approved'],
        ['id' => 'rejected', 'name' => 'Rejected'],
    ]" />

    <x-form.select name="verified" label="Verified" placeholder="Any" :value="request('verified')" :options="[
        ['id' => '1', 'name' => 'Verified'],
        ['id' => '0', 'name' => 'Not verified'],
    ]" />

    <div class="col-span-2 flex items-end gap-2 sm:col-span-1">
        <button type="submit" class="rounded-md bg-primary-500 px-4 py-2 text-sm font-medium text-white hover:bg-primary-600">
            Filter
        </button>
        <a href="{{ route('admin.users.index') }}" class="rounded-md border px-4 py-2 text-sm font-medium" style="border-color:var(--border-default)">
            Clear
        </a>
    </div>
</div>
