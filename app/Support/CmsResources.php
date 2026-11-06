<?php

namespace App\Support;

use App\Http\Resources\BannerResource;
use App\Http\Resources\CmsPageResource;
use App\Http\Resources\FaqResource;
use App\Http\Resources\SuccessStoryResource;
use App\Models\Banner;
use App\Models\CmsPage;
use App\Models\Faq;
use App\Models\SuccessStory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Cache;

/**
 * The four CMS content types behind one controller.
 *
 * `routes/api_v1_admin.php` points four `apiResource` declarations —
 * `cms/pages`, `cms/faqs`, `cms/banners`, `cms/success-stories` — at a single
 * `CmsManagementController`. That is the routing we were given, and it means
 * the controller cannot know which content type it is serving from its method
 * signature: only the URI says. Rather than have the controller and its
 * FormRequest each work that out with their own string handling, the mapping
 * lives here and both read it.
 *
 * The alternative — four near-identical controllers — was rejected because
 * their bodies would be the same twenty lines four times, and the differences
 * that do exist (the model, the ordering, the validation rules) are exactly
 * what this table holds.
 */
class CmsResources
{
    /** URI segment → [model, resource]. */
    public const MAP = [
        'pages' => [CmsPage::class, CmsPageResource::class],
        'faqs' => [Faq::class, FaqResource::class],
        'banners' => [Banner::class, BannerResource::class],
        'success-stories' => [SuccessStory::class, SuccessStoryResource::class],
    ];

    /**
     * Reads the type out of the matched route's URI, not out of the request
     * path. The route pattern is fixed at boot (`.../cms/faqs/{faq}`), so this
     * cannot be influenced by anything a caller sends — a path segment taken
     * from user input and used to pick a model class is a way to reach a model
     * the route never intended.
     */
    /**
     * Drop every read cache a change to this content type could have staled.
     *
     * Both front doors cache these rows for ten minutes — Api\V1\User\
     * CmsController and Web\PageController, deliberately under the same key
     * names — and until this existed neither was ever invalidated: publishing
     * the privacy policy left the old version live for up to ten minutes with
     * nothing in the panel to explain it. Every writer of CMS content calls
     * this.
     *
     * Keys are forgotten individually rather than through cache tags because
     * tags require a taggable store (redis, memcached) and this application
     * must keep working on the file driver a small deployment starts on.
     *
     * $original is the row as it was BEFORE the edit. A slug change writes
     * under a new key and leaves the old one serving the old content on the
     * old URL, which is the one case forgetting only the current slug misses.
     */
    public static function forgetCaches(string $type, ?Model $item = null, ?Model $original = null): void
    {
        match ($type) {
            'pages' => array_map(
                fn (?string $slug) => $slug ? Cache::forget("cms.page.{$slug}") : null,
                array_unique(array_filter([$item?->slug, $original?->slug])),
            ),

            'faqs' => array_map(Cache::forget(...), ['cms.faqs', 'cms.faqs.web']),

            // Position is a free-text column, so the live set is read from the
            // table rather than from a list here that would drift the first
            // time a campaign introduces a new one.
            'banners' => array_map(
                fn (?string $position) => Cache::forget('cms.banners.'.($position ?? 'all')),
                array_merge([null], Banner::query()->distinct()->pluck('position')->all()),
            ),

            // Success stories are paginated and were never cached — see
            // CmsController::successStories.
            default => null,
        };
    }

    public static function typeFor(Request $request): string
    {
        $uri = $request->route()?->uri() ?? '';

        preg_match('#cms/([a-z-]+)#', $uri, $matches);

        $type = $matches[1] ?? '';

        abort_unless(isset(static::MAP[$type]), 404, 'Not found.');

        return $type;
    }

    /** @return class-string<Model> */
    public static function modelFor(string $type): string
    {
        return static::MAP[$type][0];
    }

    /** @return class-string<JsonResource> */
    public static function resourceFor(string $type): string
    {
        return static::MAP[$type][1];
    }

    /** The order each type is authored in, which is the order it is edited in. */
    public static function orderFor(string $type): array
    {
        return match ($type) {
            'pages' => ['title', 'asc'],
            'faqs', 'banners', 'success-stories' => ['sort_order', 'asc'],
        };
    }

    /** Singular label for response copy — "FAQ created", not "faqs created". */
    public static function labelFor(string $type): string
    {
        return match ($type) {
            'pages' => 'Page',
            'faqs' => 'FAQ',
            'banners' => 'Banner',
            'success-stories' => 'Success story',
        };
    }
}
