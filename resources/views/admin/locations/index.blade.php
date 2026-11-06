@extends('admin.layouts.admin')

@section('title', 'Locations — '.config('app.name').' Admin')

@section('admin-body')
    <h1 class="font-serif text-3xl font-bold text-ink-900">Locations</h1>
    <p class="mt-1 text-sm" style="color:var(--text-muted)">
        Country → state → city. These foreign keys cascade, so deleting a parent would take everything under it — the delete button refuses rather than asks.
    </p>

    <nav class="mt-4 flex flex-wrap gap-1" aria-label="Location table">
        @foreach ($types as $option)
            <a href="{{ route('admin.locations.index', $option) }}"
               @class([
                   'rounded-md border px-3 py-1.5 text-sm font-medium',
                   'bg-primary-50 text-primary-700 border-primary-300' => $type === $option,
               ])
               @style(['border-color:var(--border-default)' => $type !== $option])
               @if ($type === $option) aria-current="page" @endif>
                {{ ucfirst($option) }}
            </a>
        @endforeach
    </nav>

    @if (session('import_rejected'))
        <div class="mt-4 rounded-md border border-warning bg-warning/5 p-3 text-sm" role="alert">
            <p class="font-medium text-warning">Some rows were not imported:</p>
            <ul class="mt-1 list-inside list-disc text-xs">
                @foreach (session('import_rejected') as $reason)
                    <li>{{ $reason }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @can('locations.manage')
        <form method="POST" action="{{ route('admin.locations.store', $type) }}"
              class="mt-4 flex flex-wrap items-center gap-2 rounded-lg border p-3"
              style="border-color:var(--border-default);background-color:var(--bg-surface)">
            @csrf
            @include('admin.locations._fields', ['type' => $type, 'row' => null, 'countries' => $countries, 'states' => $states])
            <button type="submit" class="rounded-md bg-primary-500 px-4 py-1.5 text-sm font-medium text-white hover:bg-primary-600">
                Add {{ \Illuminate\Support\Str::singular($type) }}
            </button>
        </form>

        @if ($type === 'cities')
            {{-- Cities are the only table large enough that anyone would
                 import one; countries and states are seeded once and edited by
                 hand, which is why the upload only appears here. --}}
            <form method="POST" action="{{ route('admin.locations.import') }}" enctype="multipart/form-data"
                  class="mt-3 flex flex-wrap items-end gap-3 rounded-lg border p-3"
                  style="border-color:var(--border-default);background-color:var(--bg-surface)">
                @csrf
                <div>
                    <label for="file" class="block text-sm font-medium">Import cities from CSV</label>
                    <input id="file" name="file" type="file" accept=".csv,text/csv" required
                           class="mt-1 text-sm">
                    <p class="mt-1 text-xs" style="color:var(--text-muted)">
                        Header row required: <span class="font-mono">state_id,name[,slug,latitude,longitude]</span>. Up to 5,000 rows, upserted on (state, slug) — re-running the same file corrects rows instead of duplicating them.
                    </p>
                </div>
                <button type="submit" class="rounded-md border border-primary-500 px-4 py-2 text-sm font-medium text-primary-600 hover:bg-primary-50">
                    Import
                </button>
            </form>
        @endif
    @endcan

    <div class="mt-4">
        <x-admin.data-table :rows="$rows" :columns="['Name', 'Slug', 'Parent / detail', 'Active', '']">
            <x-slot:filters>
                <form method="GET" action="{{ route('admin.locations.index', $type) }}" class="flex flex-wrap items-end gap-3">
                    <div>
                        <label for="q" class="block text-sm font-medium">Search</label>
                        <input id="q" name="q" type="text" value="{{ request('q') }}" maxlength="60"
                               class="mt-1 w-56 rounded-md border px-3 py-2 text-sm" style="border-color:var(--border-default)">
                    </div>

                    @if ($type === 'states')
                        <x-form.select name="country_id" label="Country" placeholder="Any country" :value="request('country_id')"
                            :options="$countries->map(fn ($c) => ['id' => $c->id, 'name' => $c->name])->all()" />
                    @elseif ($type === 'cities')
                        <x-form.select name="state_id" label="State" placeholder="Any state" :value="request('state_id')"
                            :options="$states->map(fn ($s) => ['id' => $s->id, 'name' => $s->name])->all()" />
                    @endif

                    <div class="flex items-end gap-2">
                        <button type="submit" class="rounded-md bg-primary-500 px-4 py-2 text-sm font-medium text-white hover:bg-primary-600">Filter</button>
                        <a href="{{ route('admin.locations.index', $type) }}" class="rounded-md border px-4 py-2 text-sm font-medium" style="border-color:var(--border-default)">Clear</a>
                    </div>
                </form>
            </x-slot:filters>

            @forelse ($rows as $row)
                <tr>
                    <td class="px-4 py-2">
                        {{-- One empty form per row; every field associates with
                             it by id, because a <form> cannot span table cells.
                             Same shape as the master-data table. --}}
                        <form method="POST" action="{{ route('admin.locations.update', [$type, $row->id]) }}" id="edit-loc-{{ $row->id }}">
                            @csrf
                            @method('PUT')
                        </form>

                        <input type="text" name="name" form="edit-loc-{{ $row->id }}" value="{{ $row->name }}" required maxlength="120" aria-label="Name"
                               class="w-48 rounded-md border px-2 py-1.5 text-sm" style="border-color:var(--border-default)">
                    </td>
                    <td class="px-4 py-2">
                        <input type="text" name="slug" form="edit-loc-{{ $row->id }}" value="{{ $row->slug }}" required maxlength="140" aria-label="Slug"
                               class="w-40 rounded-md border px-2 py-1.5 font-mono text-sm" style="border-color:var(--border-default)">
                    </td>
                    <td class="px-4 py-2">
                        <div class="flex flex-wrap items-center gap-2">
                            @include('admin.locations._fields', [
                                'type' => $type,
                                'row' => $row,
                                'countries' => $countries,
                                'states' => $states,
                                'form' => 'edit-loc-'.$row->id,
                                'nameFields' => false,
                            ])
                        </div>
                    </td>
                    <td class="px-4 py-2">
                        <label class="flex items-center gap-2 text-sm">
                            <input type="hidden" name="is_active" value="0" form="edit-loc-{{ $row->id }}">
                            <input type="checkbox" name="is_active" value="1" form="edit-loc-{{ $row->id }}" @checked($row->is_active)
                                   class="rounded border" style="border-color:var(--border-default)">
                            Active
                        </label>
                    </td>
                    <td class="whitespace-nowrap px-4 py-2 text-right">
                        @can('locations.manage')
                            <div class="flex items-center justify-end gap-2">
                                <button type="submit" form="edit-loc-{{ $row->id }}"
                                        class="rounded-md border border-primary-500 px-3 py-1.5 text-sm font-medium text-primary-600 hover:bg-primary-50">
                                    Save
                                </button>

                                <x-admin.confirm-dialog
                                    id="delete-loc-{{ $row->id }}" trigger="Delete" variant="danger"
                                    title="Delete “{{ $row->name }}”?"
                                    :action="route('admin.locations.destroy', [$type, $row->id])" method="DELETE"
                                    :identifier="$row->slug" confirm-label="Delete location"
                                >
                                    <p style="color:var(--text-muted)">
                                        Refused if anything lives beneath it or any member lives here — these keys cascade, so a delete would take the children with it.
                                    </p>
                                </x-admin.confirm-dialog>
                            </div>
                        @endcan
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="px-4 py-8 text-center text-sm" style="color:var(--text-muted)">
                        Nothing here. `LocationSeeder` normally creates 36 states and 16 NRI countries.
                    </td>
                </tr>
            @endforelse
        </x-admin.data-table>
    </div>
@endsection
