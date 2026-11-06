@extends('layouts.public')

@section('title', 'Success Stories — '.config('app.name'))
@section('description', 'Couples who met on '.config('app.name').' — real weddings, in their own words.')

@section('body')
    <x-page-header eyebrow="Real celebrations" title="Success stories" sub="Couples who met here, published with their permission." />

    <div class="mx-auto max-w-6xl px-4 py-12">

        @if ($stories->isEmpty())
            <p class="mt-8 text-sm" style="color:var(--text-muted)">No stories have been published yet.</p>
        @else
            <div class="stagger mt-2 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($stories as $story)
                    <article class="card hover-lift group flex flex-col overflow-hidden">
                        @if ($url = $story->photoUrl())
                            <img src="{{ $url }}" alt="{{ $story->groom_name }} and {{ $story->bride_name }}" class="h-56 w-full object-cover transition-transform duration-700 group-hover:scale-105" loading="lazy">
                        @endif

                        <div class="flex flex-1 flex-col gap-2 p-5">
                            <h2 class="font-serif text-xl font-bold text-ink-900">
                                {{ $story->bride_name }} &amp; {{ $story->groom_name }}
                            </h2>
                            <p class="text-xs" style="color:var(--text-muted)">
                                {{ collect([$story->location, $story->marriage_date?->format('F Y')])->filter()->implode(' · ') }}
                            </p>
                            @if ($story->story)
                                <p class="text-sm leading-relaxed" style="color:var(--text-muted)">{{ $story->story }}</p>
                            @endif
                        </div>
                    </article>
                @endforeach
            </div>

            <div class="mt-8">
                {{ $stories->links() }}
            </div>
        @endif
    </div>
@endsection
