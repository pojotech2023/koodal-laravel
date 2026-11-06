@extends('admin.layouts.admin')

@php($isNew = ! $staff->exists)

@section('title', ($isNew ? 'Add staff' : 'Edit '.$staff->name).' — '.config('app.name').' Admin')

@section('admin-body')
    <a href="{{ route('admin.staff.index') }}" class="text-sm font-medium text-primary-600 hover:underline">&larr; Staff &amp; roles</a>

    <h1 class="mt-3 font-serif text-3xl font-bold text-ink-900">{{ $isNew ? 'Add staff' : 'Edit '.$staff->name }}</h1>

    <form method="POST" action="{{ $isNew ? route('admin.staff.store') : route('admin.staff.update', $staff) }}" class="mt-6 max-w-2xl space-y-4">
        @csrf
        @unless ($isNew)
            @method('PUT')
        @endunless

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <x-form.input name="name" label="Name" :value="$staff->name" required maxlength="120" />
            <x-form.input name="email" label="Email" type="email" :value="$staff->email" required maxlength="190" />
            <x-form.input name="mobile" label="Mobile" :value="$staff->mobile" required maxlength="16"
                hint="Normalised to E.164 before validation, same as a member's." />

            <x-form.select name="role_id" label="Role" :value="$staff->role_id" required
                :options="$roles->map(fn ($r) => ['id' => $r->id, 'name' => $r->label])->all()" />
        </div>

        <div>
            <label for="password" class="block text-sm font-medium">
                Password
                @unless ($isNew)
                    <span class="font-normal" style="color:var(--text-muted)">(leave blank to keep the current one)</span>
                @endunless
            </label>
            <input id="password" name="password" type="password" autocomplete="new-password" @required($isNew)
                   class="mt-1 w-full rounded-md border px-3 py-2 text-sm" style="border-color:var(--border-default)">
            {{-- Stricter than a member's, deliberately: a member account
                 compromise costs that member, a staff one costs the member
                 base. The rule also rejects passwords found in known breaches,
                 which is a network call — a slow submit here is that check
                 working, not a hang. --}}
            <p class="mt-1 text-xs" style="color:var(--text-muted)">
                At least 12 characters with upper and lower case, a number and a symbol — and not one that appears in a known breach.
            </p>
        </div>

        @unless ($isNew)
            <x-form.select name="status" label="Status" :value="$staff->status" :options="[
                ['id' => 'active', 'name' => 'Active'],
                ['id' => 'suspended', 'name' => 'Suspended'],
            ]" />
            <p class="text-xs" style="color:var(--text-muted)">
                Suspending revokes their API tokens immediately; their browser session ends on their next click.
            </p>
        @endunless

        <div class="flex items-center gap-3 pt-2">
            <button type="submit" class="rounded-md bg-primary-500 px-4 py-2 text-sm font-medium text-white hover:bg-primary-600">
                {{ $isNew ? 'Create staff account' : 'Save changes' }}
            </button>
            <a href="{{ route('admin.staff.index') }}" class="rounded-md border px-4 py-2 text-sm font-medium" style="border-color:var(--border-default)">Cancel</a>
        </div>
    </form>
@endsection
