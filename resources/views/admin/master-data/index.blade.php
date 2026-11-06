@extends('admin.layouts.admin')

@section('title', 'Master data — '.config('app.name').' Admin')

@section('admin-body')
    <h1 class="font-serif text-3xl font-bold text-ink-900">Master data</h1>
    <p class="mt-1 text-sm" style="color:var(--text-muted)">
        The lookup tables every client reads and none of them hardcodes. Each write flushes the master-data cache, so a change is live on the next fetch rather than in twenty-four hours.
    </p>

    <nav class="mt-4 flex flex-wrap gap-1" aria-label="Lookup table">
        @foreach ($types as $option)
            <a href="{{ route('admin.master-data.index', $option) }}"
               @class([
                   'rounded-md border px-3 py-1.5 text-sm font-medium',
                   'bg-primary-50 text-primary-700 border-primary-300' => $type === $option,
               ])
               @style(['border-color:var(--border-default)' => $type !== $option])
               @if ($type === $option) aria-current="page" @endif>
                {{ str_replace('-', ' ', ucfirst($option)) }}
            </a>
        @endforeach
    </nav>

    @can('master_data.manage')
        <form method="POST" action="{{ route('admin.master-data.store', $type) }}"
              class="mt-4 flex flex-wrap items-center gap-2 rounded-lg border p-3"
              style="border-color:var(--border-default);background-color:var(--bg-surface)">
            @csrf

            <input type="text" name="name" required maxlength="120" placeholder="Name" aria-label="Name"
                   class="w-48 rounded-md border px-2 py-1.5 text-sm" style="border-color:var(--border-default)">

            {{-- Slug is what every client and every saved search stores. It
                 stays editable afterwards on purpose — a typo that can never
                 be corrected is a permanent wart in the API surface — but
                 changing one invalidates any client holding the old value,
                 which is what the warning on the edit rows says. --}}
            <input type="text" name="slug" required maxlength="140" placeholder="slug" aria-label="Slug"
                   class="w-40 rounded-md border px-2 py-1.5 font-mono text-sm" style="border-color:var(--border-default)">

            @include('admin.master-data._fields', ['type' => $type, 'row' => null, 'religions' => $religions, 'communities' => $communities])

            <input type="number" name="sort_order" value="0" min="0" max="65535" aria-label="Sort order"
                   class="w-20 rounded-md border px-2 py-1.5 text-sm" style="border-color:var(--border-default)">

            <button type="submit" class="rounded-md bg-primary-500 px-4 py-1.5 text-sm font-medium text-white hover:bg-primary-600">
                Add entry
            </button>
        </form>
    @endcan

    <div class="mt-4">
        <x-admin.data-table :rows="$rows" :columns="['Name', 'Slug', 'Details', 'Order', 'Active', '']">
            <x-slot:filters>
                <form method="GET" action="{{ route('admin.master-data.index', $type) }}" class="flex items-end gap-2">
                    <div>
                        <label for="q" class="block text-sm font-medium">Search</label>
                        <input id="q" name="q" type="text" value="{{ request('q') }}" maxlength="120"
                               class="mt-1 w-56 rounded-md border px-3 py-2 text-sm" style="border-color:var(--border-default)">
                    </div>
                    <button type="submit" class="rounded-md bg-primary-500 px-4 py-2 text-sm font-medium text-white hover:bg-primary-600">Filter</button>
                </form>
            </x-slot:filters>

            @forelse ($rows as $row)
                {{-- Each row is its own form, posting only itself. A single
                     form over the whole table would submit fifty rows to fix
                     one, and every one of those writes flushes the cache and
                     writes an audit row. --}}
                <tr>
                    <td class="px-4 py-2">
                        {{-- The form element is empty and every field in the
                             row associates with it by id (`form="edit-N"`).
                             A <form> cannot span table cells, and the
                             alternative — one cell holding every field —
                             gives up the table. --}}
                        <form method="POST" action="{{ route('admin.master-data.update', [$type, $row->id]) }}" id="edit-{{ $row->id }}">
                            @csrf
                            @method('PUT')
                        </form>

                        <input type="text" name="name" form="edit-{{ $row->id }}" value="{{ $row->name }}" required maxlength="120" aria-label="Name"
                               class="w-48 rounded-md border px-2 py-1.5 text-sm" style="border-color:var(--border-default)">
                    </td>
                    <td class="px-4 py-2">
                        <input type="text" name="slug" form="edit-{{ $row->id }}" value="{{ $row->slug }}" required maxlength="140" aria-label="Slug"
                               class="w-40 rounded-md border px-2 py-1.5 font-mono text-sm" style="border-color:var(--border-default)">
                    </td>
                    <td class="px-4 py-2">
                        <div class="flex flex-wrap items-center gap-2">
                            @include('admin.master-data._fields', [
                                'type' => $type,
                                'row' => $row,
                                'religions' => $religions,
                                'communities' => $communities,
                                'form' => 'edit-'.$row->id,
                            ])
                        </div>
                    </td>
                    <td class="px-4 py-2">
                        <input type="number" name="sort_order" form="edit-{{ $row->id }}" value="{{ $row->sort_order }}" min="0" max="65535" aria-label="Sort order"
                               class="w-20 rounded-md border px-2 py-1.5 text-sm" style="border-color:var(--border-default)">
                    </td>
                    <td class="px-4 py-2">
                        <label class="flex items-center gap-2 text-sm">
                            <input type="hidden" name="is_active" value="0" form="edit-{{ $row->id }}">
                            <input type="checkbox" name="is_active" value="1" form="edit-{{ $row->id }}" @checked($row->is_active)
                                   class="rounded border" style="border-color:var(--border-default)">
                            Active
                        </label>
                    </td>
                    <td class="whitespace-nowrap px-4 py-2 text-right">
                        @can('master_data.manage')
                            <div class="flex items-center justify-end gap-2">
                                <button type="submit" form="edit-{{ $row->id }}"
                                        class="rounded-md border border-primary-500 px-3 py-1.5 text-sm font-medium text-primary-600 hover:bg-primary-50">
                                    Save
                                </button>

                                {{-- Refused server-side once any member has
                                     selected the option, with the deactivate
                                     toggle one cell away as the answer. --}}
                                <x-admin.confirm-dialog
                                    id="delete-md-{{ $row->id }}" trigger="Delete" variant="danger"
                                    title="Delete “{{ $row->name }}”?"
                                    :action="route('admin.master-data.destroy', [$type, $row->id])" method="DELETE"
                                    :identifier="$row->slug" confirm-label="Delete entry"
                                >
                                    <p style="color:var(--text-muted)">
                                        Refused if any member has chosen it — deleting would blank the field on their profile, silently and with no way back. Deactivate instead.
                                    </p>
                                </x-admin.confirm-dialog>
                            </div>
                        @endcan
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="px-4 py-8 text-center text-sm" style="color:var(--text-muted)">
                        Nothing in this table yet. `MasterDataSeeder` normally fills all nine.
                    </td>
                </tr>
            @endforelse
        </x-admin.data-table>
    </div>
@endsection
