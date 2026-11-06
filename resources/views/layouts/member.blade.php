@extends('layouts.app')

{{-- Unconditional, per docs/05-website-guide.md §3 — every member-area page
     is behind auth:web and must never be indexed, regardless of what any
     individual view does or forgets to do. --}}
@section('robots', 'noindex, nofollow')

@section('flash_inline', '1')

@section('content')
    <x-member-nav />
    <x-flash />

    <main class="flex-1">
        @yield('body')
    </main>

    <x-footer />
@endsection
