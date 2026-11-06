@props(['rows', 'columns'])

{{-- The one table component every admin list screen uses —
     docs/06-admin-panel-guide.md §4. Pagination, sorting and filtering are
     all server-side: `$rows` must always be a LengthAwarePaginator (never an
     unpaginated collection), sort/filter state lives in the query string via
     the `filters` slot's plain `<form method="GET">`, and `{{ $rows->links()
     }}` is the only pagination this ever renders — which is what makes a
     filtered, sorted table view a shareable, bookmarkable URL.

     The default slot is the `<tbody>` content: each caller writes its own
     `@foreach ($rows as $row) <tr>...</tr> @endforeach` so a module's row
     markup lives with that module's view, not hidden inside this component.

     No standalone bulk-bar partial yet — nothing in the admin surface has a
     bulk action to drive one as of this session (Users' five actions are all
     per-row; PROGRESS.md §6 has the reasoning). The `bulkActions` slot below
     is real and wired for the day a module needs it. --}}
<div>
    @isset($filters)
        <div class="mb-4">{{ $filters }}</div>
    @endisset

    @isset($bulkActions)
        <div class="mb-3 flex items-center gap-2 rounded-xl border border-primary-200 bg-primary-50 px-4 py-2.5 text-sm font-medium text-primary-800">
            {{ $bulkActions }}
        </div>
    @endisset

    <div class="card overflow-x-auto">
        <table class="min-w-full divide-y text-sm [&_tbody_tr]:transition-colors [&_tbody_tr:hover]:bg-primary-50/40 [&_tbody_td]:px-4 [&_tbody_td]:py-3" style="border-color:var(--border-default)">
            <thead style="background-color:var(--bg-subtle)">
                <tr>
                    @foreach ($columns as $column)
                        <th scope="col" class="whitespace-nowrap px-4 py-3.5 text-left text-[11px] font-bold uppercase tracking-wider" style="color:var(--text-muted)">
                            {{ $column }}
                        </th>
                    @endforeach
                </tr>
            </thead>
            <tbody class="divide-y" style="border-color:var(--border-default)">
                {{ $slot }}
            </tbody>
        </table>
    </div>

    @if ($rows instanceof \Illuminate\Contracts\Pagination\LengthAwarePaginator)
        <div class="mt-4">{{ $rows->links() }}</div>
    @endif
</div>
