{{-- Full brand logo — supports logo.jpeg, logo.jpg or logo.png in public/image/
     Light surfaces: bare, blending away background.
     Dark surfaces (tone="light"): white rounded tile with shadow. --}}
@props(['size' => 'h-[4.5rem] sm:h-20', 'tone' => 'dark'])

@php
    $logoPath = file_exists(public_path('image/logo.jpeg')) 
        ? asset('image/logo.jpeg') 
        : (file_exists(public_path('image/logo.jpg')) ? asset('image/logo.jpg') : asset('image/logo.png'));
@endphp

@if ($tone === 'light')
    <span {{ $attributes->merge(['class' => 'inline-block overflow-hidden rounded-2xl bg-white p-2 shadow-lg']) }}>
        <img src="{{ $logoPath }}" alt="{{ config('app.name') }}" class="block {{ $size }} w-auto max-w-none object-contain" onerror="this.style.display='none'">
    </span>
@else
    <span {{ $attributes->merge(['class' => 'inline-flex']) }}>
        <img src="{{ $logoPath }}" alt="{{ config('app.name') }}" class="block {{ $size }} w-auto max-w-none mix-blend-multiply object-contain" onerror="this.style.display='none'">
    </span>
@endif
