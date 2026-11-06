@extends('admin.layouts.admin')

@section('title', 'Role permissions — '.config('app.name').' Admin')

@section('admin-body')
    <a href="{{ route('admin.staff.index') }}" class="text-sm font-medium text-primary-600 hover:underline">&larr; Staff &amp; roles</a>

    <h1 class="mt-3 font-serif text-3xl font-bold text-ink-900">Role permissions</h1>
    <p class="mt-1 text-sm" style="color:var(--text-muted)">
        A change here is live on each staff member's next request — nobody is signed out. Permissions are rows, not code, which is why reorganising a role is an admin task rather than a deploy.
    </p>

    <div class="mt-6 space-y-8">
        @foreach ($roles as $role)
            @if (!in_array($role->name, ['moderator', 'admin'], true))
            <section class="card p-5">
                <div class="flex flex-wrap items-baseline justify-between gap-2">
                    <h2 class="card-title">
                        {{ $role->label }}
                        <span class="font-mono text-xs font-normal" style="color:var(--text-muted)">{{ $role->name }}</span>
                    </h2>
                    <p class="text-xs" style="color:var(--text-muted)">{{ $role->users_count }} staff member(s)</p>
                </div>

                @if ($role->name === 'super_admin')
                    {{-- Refused server-side too. `Gate::before` grants this role
                         every dotted ability regardless of the pivot table, so
                         a matrix of checkboxes here would change nothing while
                         appearing to change everything. --}}
                    <p class="mt-3 rounded-md border border-warning bg-warning/5 px-3 py-2 text-sm text-warning">
                        Super Admin bypasses permission checks entirely — it holds every ability whatever this table says, so it has no editable matrix.
                    </p>
                @else
                    <form method="POST" action="{{ route('admin.staff.roles.update', $role) }}" class="mt-4">
                        @csrf
                        @method('PUT')

                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <x-form.input :name="'label'" label="Label" :value="$role->label" maxlength="80" />
                        </div>

                        {{-- Grouped by `permissions.group`, which is the UI:
                             a permission added to the seeder appears in the
                             right section with no edit here. --}}
                        <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                            @foreach ($permissionGroups as $group => $permissions)
                                <fieldset class="rounded-md border p-3" style="border-color:var(--border-default)">
                                    <legend class="px-1 text-xs font-medium uppercase tracking-wide" style="color:var(--text-muted)">
                                        {{ str_replace('_', ' ', $group) }}
                                    </legend>

                                    <div class="space-y-1">
                                        @foreach ($permissions as $permission)
                                            <label class="flex items-start gap-2 text-sm">
                                                <input type="checkbox" name="permissions[]" value="{{ $permission->id }}"
                                                       @checked($role->permissions->contains('id', $permission->id))
                                                       class="mt-0.5 rounded border" style="border-color:var(--border-default)">
                                                <span>
                                                    {{ $permission->label }}
                                                    <span class="block font-mono text-xs" style="color:var(--text-muted)">{{ $permission->name }}</span>
                                                </span>
                                            </label>
                                        @endforeach
                                    </div>
                                </fieldset>
                            @endforeach
                        </div>

                        {{-- The whole set is submitted every time, not a diff:
                             the form sends what was on screen, and a diff would
                             let two admins disagree about that — the loser
                             silently re-granting what the other just
                             revoked. Unticking everything is a real, allowed
                             state, so `permissions` is sent even when empty. --}}
                        <input type="hidden" name="permissions[]" value="">

                        @can('staff.manage')
                            <button type="submit" class="mt-4 rounded-md bg-primary-500 px-4 py-2 text-sm font-medium text-white hover:bg-primary-600">
                                Save {{ $role->label }} permissions
                            </button>
                        @endcan
                    </form>
                @endif
            </section>
            @endif
        @endforeach
    </div>
@endsection
