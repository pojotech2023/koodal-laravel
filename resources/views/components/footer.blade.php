{{-- The rule that shaped this file: a footer renders on EVERY page, so one
     link to a route that does not exist throws at render time and takes the
     whole site down, not just its own page. Add a link in the same commit as
     the route, never before.

     The CMS links point at slugs, not at named routes of their own: they all
     resolve through `page.show`, and the slugs below are the ones CmsSeeder
     creates. A slug renamed in the admin panel without this file being updated
     404s — it does not throw — which is the right failure for content that
     staff can edit and the reason these are not four more named routes. --}}
<footer class="relative mt-auto overflow-hidden bg-ink-gradient text-ink-200">
    <div class="pointer-events-none absolute inset-0 bg-grid-ink opacity-60"></div>
    <div class="pointer-events-none absolute -top-32 left-1/4 h-72 w-72 rounded-full bg-primary-500/25 blur-3xl"></div>
    <div class="pointer-events-none absolute -bottom-32 right-1/4 h-72 w-72 rounded-full bg-accent-500/15 blur-3xl"></div>

    <div class="relative mx-auto max-w-7xl px-4 py-14 sm:px-6">
        <div class="grid gap-10 lg:grid-cols-12">
            <div class="lg:col-span-5">
                <x-logo tone="light" size="h-32" />
                <p class="mt-5 max-w-sm text-sm leading-relaxed text-ink-200/80">
                    India's trusted matrimonial platform — verified profiles, real conversations, real marriages.
                </p>
                <div class="mt-6 flex flex-wrap gap-2 text-xs font-semibold">
                    <span class="rounded-full border border-white/10 bg-white/5 px-3 py-1.5 text-white/90">✓ Verified profiles</span>
                    <span class="rounded-full border border-white/10 bg-white/5 px-3 py-1.5 text-white/90">🔒 Privacy first</span>
                    <span class="rounded-full border border-white/10 bg-white/5 px-3 py-1.5 text-white/90">✦ Jadhagam match</span>
                </div>
            </div>

            <nav class="grid grid-cols-2 gap-8 sm:grid-cols-3 lg:col-span-7" aria-label="Footer">
                <div>
                    <h3 class="text-xs font-bold uppercase tracking-[0.16em] text-accent-300">Discover</h3>
                    <ul class="mt-4 space-y-3 text-sm">
                        <li><a href="{{ route('browse') }}" class="text-ink-200/80 transition-colors hover:text-white">Browse profiles</a></li>
                        <li><a href="{{ route('membership.plans') }}" class="text-ink-200/80 transition-colors hover:text-white">Membership plans</a></li>
                        <li><a href="{{ route('page.stories') }}" class="text-ink-200/80 transition-colors hover:text-white">Success stories</a></li>
                    </ul>
                </div>
                <div>
                    <h3 class="text-xs font-bold uppercase tracking-[0.16em] text-accent-300">Company</h3>
                    <ul class="mt-4 space-y-3 text-sm">
                        <li><a href="{{ route('page.show', 'about-us') }}" class="text-ink-200/80 transition-colors hover:text-white">About us</a></li>
                        <li><a href="{{ route('page.faq') }}" class="text-ink-200/80 transition-colors hover:text-white">Help &amp; FAQ</a></li>
                        <li><a href="{{ route('page.contact') }}" class="text-ink-200/80 transition-colors hover:text-white">Contact us</a></li>
                    </ul>
                </div>
                <div>
                    <h3 class="text-xs font-bold uppercase tracking-[0.16em] text-accent-300">Legal</h3>
                    <ul class="mt-4 space-y-3 text-sm">
                        <li><a href="{{ route('page.show', 'privacy-policy') }}" class="text-ink-200/80 transition-colors hover:text-white">Privacy policy</a></li>
                        <li><a href="{{ route('page.show', 'terms-conditions') }}" class="text-ink-200/80 transition-colors hover:text-white">Terms &amp; conditions</a></li>
                        <li><a href="{{ route('page.show', 'refund-policy') }}" class="text-ink-200/80 transition-colors hover:text-white">Refund policy</a></li>
                    </ul>
                </div>
            </nav>
        </div>

        <div class="mt-12 flex flex-col items-center justify-between gap-3 border-t border-white/10 pt-6 text-xs text-ink-200/60 sm:flex-row">
            <p>&copy; {{ now()->year }} {{ config('app.name') }}. All rights reserved.</p>
            <p class="flex items-center gap-1.5">Made with <span class="text-primary-400">♥</span> for families across India</p>
        </div>
    </div>
</footer>
