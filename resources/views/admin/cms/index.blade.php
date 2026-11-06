@extends('admin.layouts.admin')

@section('title', 'Content — '.config('app.name').' Admin')

@section('admin-body')
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="font-serif text-3xl font-bold text-ink-900">Content</h1>
            <p class="mt-1 text-sm" style="color:var(--text-muted)">
                Drafts are listed here alongside live rows — the public pages filter them out, this screen deliberately does not.
            </p>
        </div>

        @can('cms.manage')
            <a href="{{ route('admin.cms.'.$type.'.create') }}" class="rounded-md bg-primary-500 px-4 py-2 text-sm font-medium text-white hover:bg-primary-600">
                New {{ strtolower($label) }}
            </a>
        @endcan
    </div>

    <div class="mt-4">
        @include('admin.cms._nav')
    </div>

    <div class="mt-4">
        @switch($type)
            @case('pages')
                <x-admin.data-table :rows="$items" :columns="['Title', 'Slug', 'Published', 'Live since', 'Updated', '']">
                    @forelse ($items as $item)
                        <tr>
                            <td class="px-4 py-2.5 font-medium">{{ $item->title }}</td>
                            <td class="whitespace-nowrap px-4 py-2.5 font-mono text-xs">/{{ $item->slug }}</td>
                            <td class="whitespace-nowrap px-4 py-2.5">@include('admin.cms._status', ['on' => $item->is_published])</td>
                            <td class="whitespace-nowrap px-4 py-2.5">{{ $item->published_at?->format('d M Y') ?? '—' }}</td>
                            <td class="whitespace-nowrap px-4 py-2.5">{{ $item->updated_at?->format('d M Y') }}</td>
                            <td class="whitespace-nowrap px-4 py-2.5 text-right">@include('admin.cms._actions', ['item' => $item, 'type' => $type, 'label' => $label, 'identifier' => $item->slug])</td>
                        </tr>
                    @empty
                        @include('admin.cms._empty', ['colspan' => 6, 'label' => $label])
                    @endforelse
                </x-admin.data-table>
                @break

            @case('faqs')
                <x-admin.data-table :rows="$items" :columns="['Question', 'Category', 'Order', 'Published', '']">
                    @forelse ($items as $item)
                        <tr>
                            <td class="px-4 py-2.5 font-medium">{{ $item->question }}</td>
                            <td class="whitespace-nowrap px-4 py-2.5">{{ $item->category }}</td>
                            <td class="whitespace-nowrap px-4 py-2.5">{{ $item->sort_order }}</td>
                            <td class="whitespace-nowrap px-4 py-2.5">@include('admin.cms._status', ['on' => $item->is_published])</td>
                            <td class="whitespace-nowrap px-4 py-2.5 text-right">@include('admin.cms._actions', ['item' => $item, 'type' => $type, 'label' => $label, 'identifier' => $item->id])</td>
                        </tr>
                    @empty
                        @include('admin.cms._empty', ['colspan' => 5, 'label' => $label])
                    @endforelse
                </x-admin.data-table>
                @break

            @case('banners')
                <x-admin.data-table :rows="$items" :columns="['Banner', 'Position', 'Window', 'Order', 'Active', '']">
                    @forelse ($items as $item)
                        <tr>
                            <td class="px-4 py-2.5">
                                <span class="font-medium">{{ $item->title ?: 'Untitled' }}</span>
                                <span class="block font-mono text-xs" style="color:var(--text-muted)">{{ $item->image_path }}</span>
                            </td>
                            <td class="whitespace-nowrap px-4 py-2.5">{{ str_replace('_', ' ', $item->position) }}</td>
                            <td class="whitespace-nowrap px-4 py-2.5">
                                {{-- Both ends matter: a window that has closed
                                     is the usual reason a banner "isn't
                                     showing" while its Active toggle is
                                     on. --}}
                                {{ $item->starts_at?->format('d M Y') ?? 'always' }} → {{ $item->ends_at?->format('d M Y') ?? 'no end' }}
                            </td>
                            <td class="whitespace-nowrap px-4 py-2.5">{{ $item->sort_order }}</td>
                            <td class="whitespace-nowrap px-4 py-2.5">@include('admin.cms._status', ['on' => $item->is_active])</td>
                            <td class="whitespace-nowrap px-4 py-2.5 text-right">@include('admin.cms._actions', ['item' => $item, 'type' => $type, 'label' => $label, 'identifier' => $item->id])</td>
                        </tr>
                    @empty
                        @include('admin.cms._empty', ['colspan' => 6, 'label' => $label])
                    @endforelse
                </x-admin.data-table>
                @break

            @case('success-stories')
                <x-admin.data-table :rows="$items" :columns="['Couple', 'Married', 'Location', 'Order', 'Published', '']">
                    @forelse ($items as $item)
                        <tr>
                            <td class="px-4 py-2.5">
                                <span class="font-medium">{{ $item->groom_name }} &amp; {{ $item->bride_name }}</span>
                                @if ($item->is_featured)
                                    <span class="ml-1 rounded-full bg-warning/10 px-2 py-0.5 text-xs font-medium text-warning">Featured</span>
                                @endif
                            </td>
                            <td class="whitespace-nowrap px-4 py-2.5">{{ $item->marriage_date?->format('d M Y') ?? '—' }}</td>
                            <td class="whitespace-nowrap px-4 py-2.5">{{ $item->location ?? '—' }}</td>
                            <td class="whitespace-nowrap px-4 py-2.5">{{ $item->sort_order }}</td>
                            <td class="whitespace-nowrap px-4 py-2.5">@include('admin.cms._status', ['on' => $item->is_published])</td>
                            <td class="whitespace-nowrap px-4 py-2.5 text-right">@include('admin.cms._actions', ['item' => $item, 'type' => $type, 'label' => $label, 'identifier' => $item->id])</td>
                        </tr>
                    @empty
                        @include('admin.cms._empty', ['colspan' => 6, 'label' => $label])
                    @endforelse
                </x-admin.data-table>
                @break
        @endswitch
    </div>
@endsection
