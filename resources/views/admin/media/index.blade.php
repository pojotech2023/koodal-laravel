@extends('admin.layouts.admin')

@section('title', 'Media library — '.config('app.name').' Admin')

@section('admin-body')
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="font-serif text-3xl font-bold text-ink-900">Media library</h1>
            <p class="mt-1 text-sm" style="color:var(--text-muted)">
                Public files — banner art, success-story photos, social images. Everything here is served from a URL anyone can open, so nothing member-confidential belongs in it: ID documents and report evidence stay on the private disk behind links that expire.
            </p>
        </div>
    </div>

    @can('cms.manage')
        {{-- A real multipart form, not a JS dropzone. Submitting it reloads
             this page with the new path flashed — which is the whole no-JS
             path, and the reason <x-admin.image-field> can be a pure
             enhancement rather than the only way in. --}}
        <form method="POST" action="{{ route('admin.media.store') }}" enctype="multipart/form-data"
              class="mt-4 flex flex-wrap items-end gap-3 rounded-lg border p-4"
              style="border-color:var(--border-default);background-color:var(--bg-surface)">
            @csrf

            <div class="min-w-0 flex-1">
                <label for="file" class="block text-sm font-medium">Image file</label>
                <input id="file" name="file" type="file" required
                       accept="{{ collect(config('matrimony.media.upload.mimes'))->map(fn ($m) => 'image/'.($m === 'jpg' ? 'jpeg' : $m))->unique()->join(',') }}"
                       class="mt-1 w-full rounded-md border px-3 py-2 text-sm" style="border-color:var(--border-default)">
                <p class="mt-1 text-xs" style="color:var(--text-muted)">
                    {{ strtoupper(implode(', ', config('matrimony.media.upload.mimes'))) }} ·
                    up to {{ round(config('matrimony.media.upload.max_size_kb') / 1024, 1) }} MB.
                    SVG is not accepted — it can carry script, and these files are public.
                </p>
            </div>

            <x-form.select name="collection" label="Collection" placeholder="general" :value="$collection"
                           :options="collect($collections)->map(fn ($c) => ['id' => $c, 'name' => $c])->all()" />

            <button type="submit" class="rounded-md bg-primary-500 px-4 py-2 text-sm font-medium text-white hover:bg-primary-600">
                Upload
            </button>
        </form>
    @endcan

    <nav class="mt-4 flex flex-wrap gap-2" aria-label="Collection">
        <a href="{{ route('admin.media.index') }}"
           @class(['rounded-full border px-3 py-1 text-xs font-medium', 'bg-primary-50 text-primary-700' => $collection === ''])
           style="border-color:var(--border-default)">All</a>

        @foreach ($collections as $name)
            <a href="{{ route('admin.media.index', ['collection' => $name]) }}"
               @class(['rounded-full border px-3 py-1 text-xs font-medium', 'bg-primary-50 text-primary-700' => $collection === $name])
               style="border-color:var(--border-default)">{{ $name }}</a>
        @endforeach
    </nav>

    @if ($uploads->isEmpty())
        <p class="mt-6 rounded-lg border px-4 py-8 text-center text-sm" style="border-color:var(--border-default);color:var(--text-muted)">
            Nothing here yet. Upload an image above, then paste its path into a banner, a success story or a page's social image.
        </p>
    @else
        <div class="mt-4 grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4">
            @foreach ($uploads as $upload)
                <article class="card overflow-hidden">
                    <a href="{{ $upload->url() }}" target="_blank" rel="noopener">
                        <img src="{{ $upload->url() }}" alt="{{ $upload->original_name }}" loading="lazy"
                             class="aspect-video w-full bg-black/5 object-contain">
                    </a>

                    <div class="space-y-2 p-3 text-sm">
                        <p class="truncate font-medium" title="{{ $upload->original_name }}">{{ $upload->original_name }}</p>

                        {{-- The path, selectable, because copying it into an
                             image field by hand is the no-JS workflow this
                             page exists to make possible. --}}
                        <input type="text" readonly value="{{ $upload->path }}" onfocus="this.select()"
                               aria-label="Stored path"
                               class="w-full rounded-md border px-2 py-1 font-mono text-xs" style="border-color:var(--border-default)">

                        <p class="text-xs" style="color:var(--text-muted)">
                            {{ $upload->humanSize() }}
                            @if ($upload->dimensions()) · {{ $upload->dimensions() }} @endif
                            · {{ $upload->created_at->format('d M Y') }}
                            @if ($upload->uploader) · {{ $upload->uploader->name }} @endif
                        </p>

                        @can('cms.manage')
                            {{-- Not behind <x-admin.confirm-dialog>: the
                                 controller refuses to delete a file a live
                                 page still points at, which is the outcome
                                 the typed-identifier confirmation exists to
                                 prevent. An unreferenced image is a genuinely
                                 small mistake to make. --}}
                            <form method="POST" action="{{ route('admin.media.destroy', $upload) }}">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-xs font-medium text-danger hover:underline">Delete</button>
                            </form>
                        @endcan
                    </div>
                </article>
            @endforeach
        </div>

        <div class="mt-6">{{ $uploads->links() }}</div>
    @endif
@endsection
