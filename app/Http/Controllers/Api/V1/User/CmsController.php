<?php

namespace App\Http\Controllers\Api\V1\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\Cms\ContactEnquiryRequest;
use App\Http\Resources\BannerResource;
use App\Http\Resources\CmsPageResource;
use App\Http\Resources\FaqResource;
use App\Http\Resources\SuccessStoryResource;
use App\Models\Banner;
use App\Models\CmsPage;
use App\Models\ContactEnquiry;
use App\Models\Faq;
use App\Models\Setting;
use App\Models\SuccessStory;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

/**
 * The public content surface: pages, FAQs, banners, success stories, the
 * contact form and the public settings.
 *
 * Everything read here is **published-only**, through the models' own scopes,
 * and that is the single rule the whole controller rests on. The admin
 * surface deliberately returns drafts so authors can see their work; if a
 * scope were forgotten on one of these five endpoints, an unfinished terms
 * page or an unapproved success story would be live on the marketing site
 * with nothing in the panel indicating it.
 *
 * Cached briefly rather than not at all. These are the endpoints an anonymous
 * visitor hits, so they are the ones a traffic spike lands on first, and none
 * of the content changes more than a few times a month.
 */
class CmsController extends Controller
{
    protected const TTL = 600;

    /**
     * `scopePublished` on `CmsPage` is applied via `findOrFail`-by-slug rather
     * than by id: the model sets `getRouteKeyName()` to the slug, and a page is
     * quoted by URL everywhere — in emails, in the app footer, by support.
     *
     * An unpublished page is a **404**, not a 403. Confirming that
     * `/cms/pages/refund-policy` exists but is not public tells a competitor
     * exactly what is being drafted.
     */
    public function page(string $slug): JsonResponse
    {
        $page = Cache::remember(
            "cms.page.{$slug}",
            static::TTL,
            fn () => CmsPage::published()->where('slug', $slug)->first(),
        );

        abort_unless($page, 404, 'Not found.');

        return ApiResponse::success(new CmsPageResource($page), 'Page retrieved successfully');
    }

    /**
     * The whole FAQ, grouped by category, in one response.
     *
     * Not paginated and not filtered by category server-side: the entire set is
     * a few dozen short rows, the help screen renders it as accordions, and a
     * client that has all of it can search it without a round trip per
     * keystroke.
     */
    public function faqs(Request $request): JsonResponse
    {
        $faqs = Cache::remember('cms.faqs', static::TTL, fn () => Faq::published()
            ->orderBy('category')
            ->orderBy('sort_order')
            ->get()
            ->groupBy('category')
            ->map(fn ($rows) => FaqResource::collection($rows)->resolve()));

        return ApiResponse::success($faqs, 'FAQs retrieved successfully');
    }

    /**
     * `scopeLive` is stricter than `is_active`: a banner outside its scheduled
     * window is active and not live. Caching for ten minutes means a campaign
     * can start up to ten minutes late, which is the right trade against
     * querying this on every home-page load.
     */
    public function banners(Request $request): JsonResponse
    {
        $request->validate(['position' => ['nullable', 'string', 'max:30']]);

        $position = $request->string('position')->toString() ?: null;

        $banners = Cache::remember(
            'cms.banners.'.($position ?? 'all'),
            static::TTL,
            fn () => Banner::live()->atPosition($position)->get(),
        );

        return ApiResponse::success(
            BannerResource::collection($banners)->resolve(),
            'Banners retrieved successfully',
        );
    }

    public function successStories(Request $request): JsonResponse
    {
        $request->validate([
            'featured' => ['nullable', 'boolean'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);

        // Paginated and uncached, unlike its neighbours: the list grows without
        // bound and is browsed page by page, so caching it would fill the store
        // with one entry per page per filter combination for a screen nobody
        // reloads.
        $stories = SuccessStory::published()
            ->when($request->boolean('featured'), fn ($q) => $q->where('is_featured', true))
            ->paginate($request->integer('per_page', 12));

        return ApiResponse::paginated($stories, SuccessStoryResource::class, 'Success stories retrieved successfully');
    }

    /**
     * The contact form.
     *
     * Answered with 201 and a reassurance, never with the created row. The
     * enquiry id means nothing to the sender and the row carries a `status`
     * and a `handled_by` that are internal; echoing it back would also confirm
     * to a spam script that its submission was stored.
     */
    public function contact(ContactEnquiryRequest $request): JsonResponse
    {
        ContactEnquiry::create($request->validated() + ['status' => 'open']);

        return ApiResponse::created(
            null,
            'Thank you. Our team will get back to you within one working day.',
        );
    }

    /**
     * Only the keys explicitly flagged `is_public`.
     *
     * The flag defaults to false on the column precisely so that a new setting
     * is never accidentally published — an operational knob is often a
     * commission rate, a gateway threshold or a feature switch that tells an
     * attacker what to probe. This endpoint reads that flag and nothing else.
     */
    public function publicSettings(): JsonResponse
    {
        $settings = Cache::remember('cms.settings.public', static::TTL, fn () => Setting::publicValues());

        return ApiResponse::success($settings, 'Settings retrieved successfully');
    }
}
