{{-- An image path field, with a way to put a file at that path.

     It is still, underneath, the plain text input it replaces — same `name`,
     same submitted value, same server-side rule (`nullable|string|max:255`),
     and it works with the script blocked exactly as before: type or paste a
     path from the media library. Everything else here is enhancement.

     Deliberately *not* a file input inside the surrounding form. A banner
     form carries a dozen other fields, and making the image arrive as part of
     that submit would mean either re-uploading the file on every validation
     failure or teaching CmsContentRequest four different file rules across
     four content types. Instead the file goes to `admin.media.store` on its
     own, comes back as a path, and this field is filled in place — which also
     means the upload lands in the library and is reusable, rather than being
     a one-off attachment to one banner.

     `data-image-field` is what resources/js/admin.js binds; the markup below
     it renders inert (and hidden) until that script attaches. --}}

@props([
    'name',
    'label',
    'value' => null,
    'hint' => null,
    'collection' => 'general',
])

<div data-image-field
     data-upload-url="{{ route('admin.media.store') }}"
     data-collection="{{ $collection }}">

    <label for="{{ $name }}" class="block text-sm font-medium">{{ $label }}</label>

    <input id="{{ $name }}" name="{{ $name }}" type="text"
           value="{{ old($name, $value) }}" data-path-input
           {{ $attributes }}
           class="mt-1 w-full rounded-md border px-3 py-2 font-mono text-sm"
           style="border-color:var(--border-default)">

    <div class="mt-1 flex flex-wrap items-center gap-3">
        <p class="text-xs" style="color:var(--text-muted)">
            {{ $hint ?? 'A path on the public disk.' }}
            <a href="{{ route('admin.media.index') }}" target="_blank" rel="noopener" class="text-primary-600 hover:underline">Media library</a>
        </p>

        {{-- Unhidden by admin.js. Hidden by default so a blocked script never
             shows a picker that would do nothing, which is the same rule the
             photo queue's shortcut hint follows. --}}
        <label hidden data-upload-trigger
               class="cursor-pointer text-xs font-medium text-primary-600 hover:underline">
            Upload a file
            <input type="file" class="sr-only" data-upload-input
                   accept="{{ collect(config('matrimony.media.upload.mimes'))->map(fn ($m) => 'image/'.($m === 'jpg' ? 'jpeg' : $m))->unique()->join(',') }}">
        </label>

        <span hidden data-upload-status class="text-xs" style="color:var(--text-muted)"></span>
    </div>

    {{-- Rendered server-side when the field already has a value, so an
         existing banner shows its image with no JS at all; admin.js swaps the
         src after an upload.

         `src` is *omitted*, not set to "", when there is no path yet: an empty
         src resolves against the document URL, so the placeholder would fetch
         this admin page and hand it to the image decoder on every form load.
         `hidden` does not prevent that. --}}
    @php($currentPath = old($name, $value))

    <img data-image-preview alt=""
         @if ($currentPath)
             src="{{ \Illuminate\Support\Facades\Storage::disk(config('filesystems.default'))->url($currentPath) }}"
         @else
             hidden
         @endif
         class="mt-2 max-h-32 rounded-md border" style="border-color:var(--border-default)">
</div>
