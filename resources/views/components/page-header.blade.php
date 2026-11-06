@props(['title', 'eyebrow' => null, 'sub' => null, 'align' => 'center'])

{{-- Hero band for public content pages (plans, stories, FAQ, contact, CMS).
     Same soft blush + dotted texture as the home hero so the site reads as
     one system, but half the height — these pages exist for their content. --}}
<section class="relative overflow-hidden border-b" style="border-color:var(--border-default)">
    <div class="pointer-events-none absolute inset-0 bg-brand-soft"></div>
    <div class="pointer-events-none absolute inset-0 bg-dots opacity-60 [mask-image:radial-gradient(ellipse_at_top,black,transparent_75%)]"></div>
    <div class="pointer-events-none absolute -left-20 -top-24 h-72 w-72 rounded-full bg-primary-300/35 blur-3xl animate-blob"></div>
    <div class="pointer-events-none absolute -right-16 -bottom-24 h-72 w-72 rounded-full bg-accent-300/35 blur-3xl animate-blob" style="animation-delay:-6s"></div>

    <div class="relative mx-auto max-w-4xl px-4 py-14 sm:py-16 {{ $align === 'center' ? 'text-center' : '' }} animate-fade-up">
        @if ($eyebrow)
            <span class="eyebrow">{{ $eyebrow }}</span>
        @endif
        <h1 class="mt-4 font-serif text-4xl font-bold leading-tight text-ink-900 sm:text-5xl">{{ $title }}</h1>
        @if ($sub)
            <p class="{{ $align === 'center' ? 'mx-auto' : '' }} mt-4 max-w-2xl text-base leading-relaxed text-ink-500">{{ $sub }}</p>
        @endif
    </div>
</section>
