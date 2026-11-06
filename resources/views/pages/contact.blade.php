@extends('layouts.public')

@section('title', 'Contact Us — '.config('app.name'))
@section('description', 'Get in touch with the '.config('app.name').' team. We reply within one working day.')

@section('body')
    <x-page-header eyebrow="We're here to help" title="Contact us" sub="Questions about your profile, a payment or a match? Send us a note and we'll reply within one working day." />

    <div class="mx-auto max-w-3xl px-4 py-12">

        {{-- The address, hours and phone number live in the `contact-us` CMS
             page so support can change them without a deploy. Unescaped for
             the same reason (and with the same caveat) as pages/show — see the
             comment there. Absent or unpublished, the form below still works. --}}
        @if ($page?->content)
            <div class="cms-content card mb-2 p-6 text-sm leading-relaxed" style="color:var(--text-muted)">
                {!! $page->content !!}
            </div>
        @endif

        <form method="POST" action="{{ route('page.contact.store') }}" class="card mt-6 grid gap-5 p-6 shadow-lg sm:grid-cols-2 sm:p-8">
            @csrf

            <x-form.input name="name" label="Your name" required />
            <x-form.input name="email" label="Email" type="email" required />
            <x-form.input name="mobile" label="Mobile (optional)" type="tel" />
            <x-form.input name="subject" label="Subject (optional)" />

            <div class="sm:col-span-2">
                <x-form.textarea
                    name="message"
                    label="How can we help?"
                    rows="6"
                    required
                    minlength="20"
                    maxlength="5000"
                    hint="A sentence or two about what you need — the more you tell us, the faster we can help."
                />
            </div>

            <div class="sm:col-span-2 flex justify-end">
                <button type="submit" class="rounded-xl bg-primary-500 px-6 py-3 text-sm font-semibold text-white hover:bg-primary-600">
                    Send message
                </button>
            </div>
        </form>
    </div>
@endsection
