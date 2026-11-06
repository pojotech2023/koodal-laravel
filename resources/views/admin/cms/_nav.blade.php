{{-- The four content types, plus the announcement composer that shares this
     module's routes. One nav for every CMS screen so a content editor moves
     between them without going back to the sidebar. --}}
<nav class="flex flex-wrap gap-1" aria-label="Content type">
    @foreach (['pages' => 'Pages', 'faqs' => 'FAQs', 'banners' => 'Banners', 'success-stories' => 'Success stories'] as $slug => $label)
        <a href="{{ route('admin.cms.'.$slug.'.index') }}"
           @class([
               'rounded-md border px-3 py-1.5 text-sm font-medium',
               'bg-primary-50 text-primary-700 border-primary-300' => ($type ?? null) === $slug,
           ])
           @style(['border-color:var(--border-default)' => ($type ?? null) !== $slug])
           @if (($type ?? null) === $slug) aria-current="page" @endif>
            {{ $label }}
        </a>
    @endforeach

    @can('notifications.broadcast')
        <a href="{{ route('admin.cms.broadcast.form') }}"
           @class([
               'rounded-md border px-3 py-1.5 text-sm font-medium',
               'bg-primary-50 text-primary-700 border-primary-300' => ($type ?? null) === 'broadcast',
           ])
           @style(['border-color:var(--border-default)' => ($type ?? null) !== 'broadcast'])>
            Announcement
        </a>
    @endcan
</nav>
