@extends('layouts.public')

@section('title', 'Frequently Asked Questions — '.config('app.name'))
@section('description', 'Answers about profiles, verification, memberships, privacy and payments on '.config('app.name').'.')

@section('body')
    <x-page-header eyebrow="Help centre" title="Frequently asked questions" sub="Quick answers about profiles, verification, memberships, privacy and payments." />

    <div class="mx-auto max-w-3xl px-4 py-12">

        @if ($faqs->isEmpty())
            <p class="mt-6 text-sm" style="color:var(--text-muted)">No questions have been published yet.</p>
        @else
            @foreach ($faqs as $category => $rows)
                <section class="mt-10">
                    <h2 class="font-serif text-xl font-bold text-primary-700">{{ ucfirst(str_replace(['_', '-'], ' ', $category)) }}</h2>

                    <div class="card mt-4 divide-y overflow-hidden" style="border-color:var(--border-default)">
                        @foreach ($rows as $faq)
                            {{-- <details>, not Alpine. An accordion is the one
                                 widget HTML already ships, it is keyboard- and
                                 screen-reader-accessible without any work, and
                                 the browser's find-in-page searches inside a
                                 closed <details> — which a JS-hidden panel
                                 defeats, on the page people arrive at
                                 specifically to search. --}}
                            <details class="group px-5 py-4 transition-colors open:bg-primary-50/40">
                                <summary class="cursor-pointer list-none text-sm font-semibold marker:content-none">
                                    <span class="mr-2 text-primary-600 group-open:hidden">+</span>
                                    <span class="mr-2 hidden text-primary-600 group-open:inline">&minus;</span>
                                    {{ $faq->question }}
                                </summary>
                                <div class="mt-2 pl-6 text-sm leading-relaxed" style="color:var(--text-muted)">
                                    {{ $faq->answer }}
                                </div>
                            </details>
                        @endforeach
                    </div>
                </section>
            @endforeach
        @endif

        <div class="relative mt-12 overflow-hidden rounded-3xl bg-ink-gradient p-8 text-center text-white shadow-lg">
            <div class="pointer-events-none absolute -right-10 -top-10 h-40 w-40 rounded-full bg-primary-500/40 blur-3xl"></div>
            <p class="relative font-serif text-xl font-bold">Still stuck?</p>
            <p class="relative mt-1 text-sm text-ink-200/80">Our team replies within one working day.</p>
            <a href="{{ route('page.contact') }}" class="relative mt-5 inline-block rounded-xl bg-white px-6 py-2.5 text-sm font-bold text-primary-700 shadow-lg transition-transform hover:scale-105">
                Contact our team
            </a>
        </div>
    </div>
@endsection
