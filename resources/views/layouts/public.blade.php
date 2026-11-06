@extends('layouts.app')

@section('flash_inline', '1')

@section('content')
    <x-nav />
    <x-flash />

    <main class="flex-1">
        @yield('body')
    </main>

    <x-footer />
@endsection
