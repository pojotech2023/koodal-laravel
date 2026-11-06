@extends('layouts.public')

@section('title', ($page->meta_title ?: $page->title).' — '.config('app.name'))
@section('description', $page->meta_description ?? '')

@push('meta')
    @if ($url = $page->ogImageUrl())
        <meta property="og:image" content="{{ $url }}">
    @endif
    <meta property="og:title" content="{{ $page->meta_title ?: $page->title }}">
    <meta property="og:type" content="article">
@endpush

@section('body')
    <x-page-header :title="$page->title" :sub="$page->published_at ? 'Last updated '.$page->updated_at->format('j F Y') : null" />

    <article class="mx-auto max-w-3xl px-4 py-12">

        {{-- Unescaped, and that is a decision. `content` is a longText field
             authored through the admin panel's plain textarea by a holder of
             the `cms.manage` permission, and a privacy policy needs headings,
             lists and links — escaping it would print the markup instead of
             rendering it and make the page unusable.

             The trust boundary is therefore "anyone with cms.manage can put
             script on the marketing site". That is the ordinary CMS bargain
             and the permission is already the narrow one, but it IS a stored
             XSS surface and is recorded as such in PROGRESS.md §6: when a
             Composer toolchain exists, an HTML purifier on the way IN (in
             CmsContentRequest, so the stored row is already clean) is the fix.
             Sanitising here instead would leave the bad markup in the database
             and only hide it. --}}
        <div class="cms-content card p-6 text-[0.95rem] leading-relaxed sm:p-10" style="color:var(--text-primary)">
            {!! $page->content !!}
        </div>
    </article>
@endsection
