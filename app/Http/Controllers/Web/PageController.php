<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\Cms\ContactEnquiryRequest;
use App\Models\CmsPage;
use App\Models\ContactEnquiry;
use App\Models\Faq;
use App\Models\SuccessStory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

/**
 * The marketing content pages — about, privacy, terms, refund, FAQ, success
 * stories and contact. The Blade twin of Api\V1\User\CmsController, reading
 * the same models through the same published-only scopes.
 *
 * These are the pages docs/05-website-guide.md §3 lists under `layouts.public`
 * and the ones docs §6 says the sitemap is built from. Until now the CMS had
 * an admin panel that authored content and an API that served it, and no
 * website page that rendered it — which is why <x-footer> could not link to a
 * privacy policy that had been in the database since the first seeder ran.
 *
 * **Published-only, and unpublished is a 404 rather than a 403** — the same
 * single rule the API controller rests on, for the same reason: confirming
 * that `/refund-policy` exists but is not public tells a competitor exactly
 * what is being drafted.
 *
 * Cached on the same ten-minute TTL and, deliberately, under the same cache
 * keys as the API's (`cms.page.{slug}`, `cms.faqs`). Two front doors reading
 * one row through two keys would mean an admin edit clearing one and not the
 * other — CmsController's flush already invalidates these names.
 */
class PageController extends Controller
{
    /** Same TTL as Api\V1\User\CmsController::TTL — these are the same rows. */
    protected const TTL = 600;

    /**
     * Any published CMS page, by slug. Registered as the LAST route in
     * routes/web.php, so it only ever catches a path nothing else claimed.
     */
    public function show(string $slug): View
    {
        $page = Cache::remember(
            "cms.page.{$slug}",
            static::TTL,
            fn () => CmsPage::published()->where('slug', $slug)->first(),
        );

        abort_unless($page, 404);

        return view('pages.show', ['page' => $page]);
    }

    /**
     * The whole FAQ, grouped by category — not paginated and not filtered
     * server-side, same reasoning as the API's: a few dozen short rows that
     * render as accordions, and a visitor with all of them can use the
     * browser's own find-in-page.
     */
    public function faqs(): View
    {
        $faqs = Cache::remember(
            'cms.faqs.web',
            static::TTL,
            fn () => Faq::published()->orderBy('category')->orderBy('sort_order')->get()->groupBy('category'),
        );

        return view('pages.faq', ['faqs' => $faqs]);
    }

    /**
     * Paginated and uncached, unlike its neighbours — the list grows without
     * bound and is browsed page by page, so caching would fill the store with
     * one entry per page for a screen nobody reloads. (The API says the same.)
     */
    public function successStories(): View
    {
        return view('pages.stories', [
            'stories' => SuccessStory::published()->paginate(12),
        ]);
    }

    /**
     * The contact form, with the `contact-us` CMS page rendered above it when
     * one is published — so the address, hours and phone number stay editable
     * in the admin panel rather than being hardcoded into this view.
     */
    public function contact(): View
    {
        return view('pages.contact', [
            'page' => Cache::remember(
                'cms.page.contact-us',
                static::TTL,
                fn () => CmsPage::published()->where('slug', 'contact-us')->first(),
            ),
        ]);
    }

    /**
     * Validated by the same FormRequest the API uses, so the 20-character
     * message floor and the mobile normalisation are one implementation, not
     * two. Answered with a redirect and a flash rather than the created row —
     * the enquiry id means nothing to the sender, and echoing it back confirms
     * to a spam script that its submission was stored.
     */
    public function storeContact(ContactEnquiryRequest $request): RedirectResponse
    {
        ContactEnquiry::create($request->validated() + ['status' => 'open']);

        return redirect()
            ->route('page.contact')
            ->with('status', 'Thank you. Our team will get back to you within one working day.');
    }
}
