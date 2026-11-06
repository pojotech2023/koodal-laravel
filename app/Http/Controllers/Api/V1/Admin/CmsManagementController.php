<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\BroadcastNotificationRequest;
use App\Http\Requests\Admin\CmsContentRequest;
use App\Jobs\BroadcastAnnouncement;
use App\Services\AuditLogger;
use App\Support\ApiResponse;
use App\Support\CmsResources;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Pages, FAQs, banners and success stories — plus the announcement broadcast,
 * which lives here because the routes put it here.
 *
 * One controller over four content types: `routes/api_v1_admin.php` points
 * four `apiResource` declarations at this class, so the type comes from the
 * matched route rather than from the method signature. `CmsResources` owns
 * that mapping and explains the trade-off; everything below is the same five
 * verbs against whichever model it names.
 *
 * The route parameters are scalars, not bound models. `apiResource('cms/faqs')`
 * names its parameter `{faq}`, and there is no `Faq`-shaped signature that also
 * accepts a `CmsPage` — so the id arrives as an int and the model is resolved
 * against the type the route actually matched. A guessed id from another type's
 * URL therefore 404s on the wrong table rather than editing a row the caller
 * did not name.
 */
class CmsManagementController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->authorize('cms.view');

        $type = CmsResources::typeFor($request);
        $model = CmsResources::modelFor($type);
        [$column, $direction] = CmsResources::orderFor($type);

        // Unpublished rows included, unlike the public endpoints: this is the
        // authoring list, and a draft the author cannot see is a draft they
        // will write twice.
        $items = $model::query()->orderBy($column, $direction)->get();

        return ApiResponse::success(
            CmsResources::resourceFor($type)::collection($items)->resolve(),
            CmsResources::labelFor($type).' list retrieved successfully',
        );
    }

    public function store(CmsContentRequest $request, AuditLogger $audit): JsonResponse
    {
        $this->authorize('cms.manage');

        $type = CmsResources::typeFor($request);
        $model = CmsResources::modelFor($type);

        $item = $model::create($this->attributes($request, $type));

        $audit->log('cms.created', $item, newValues: $item->getAttributes(), actor: $request->user());

        CmsResources::forgetCaches($type, $item);

        return ApiResponse::created(
            new (CmsResources::resourceFor($type))($item),
            CmsResources::labelFor($type).' created',
        );
    }

    public function show(Request $request, int|string $id): JsonResponse
    {
        $this->authorize('cms.view');

        $type = CmsResources::typeFor($request);
        $item = CmsResources::modelFor($type)::findOrFail($id);

        return ApiResponse::success(
            new (CmsResources::resourceFor($type))($item),
            CmsResources::labelFor($type).' retrieved successfully',
        );
    }

    public function update(CmsContentRequest $request, int|string $id, AuditLogger $audit): JsonResponse
    {
        $this->authorize('cms.manage');

        $type = CmsResources::typeFor($request);
        $item = CmsResources::modelFor($type)::findOrFail($id);

        // Cloned before the fill — see the same line in Web\Admin\CmsController.
        $original = clone $item;

        $item->fill($this->attributes($request, $type));
        $item->save();

        $audit->logChanges('cms.updated', $item, CmsResources::labelFor($type).' edited', $request->user());

        CmsResources::forgetCaches($type, $item, $original);

        return ApiResponse::success(
            new (CmsResources::resourceFor($type))($item->fresh()),
            CmsResources::labelFor($type).' updated',
        );
    }

    public function destroy(Request $request, int|string $id, AuditLogger $audit): JsonResponse
    {
        $this->authorize('cms.manage');

        $type = CmsResources::typeFor($request);
        $item = CmsResources::modelFor($type)::findOrFail($id);

        // The whole row goes in the trail rather than just its id. CMS rows
        // carry the text somebody wrote, none of these tables is soft-deleted,
        // and "who deleted the terms page" is a question that gets asked with
        // the expectation of getting the content back.
        $audit->log(
            'cms.deleted',
            $item,
            oldValues: $item->getAttributes(),
            description: CmsResources::labelFor($type).' deleted',
            actor: $request->user(),
        );

        $item->delete();

        CmsResources::forgetCaches($type, $item);

        return ApiResponse::success(null, CmsResources::labelFor($type).' deleted');
    }

    /**
     * Announcements to the member base.
     *
     * Audited here, before the job starts, and the audit row records the
     * audience filters. The decision to send is the auditable event — by the
     * time the fan-out finishes, the interesting question is not how many rows
     * were written but who chose to write them and to whom.
     */
    public function broadcast(BroadcastNotificationRequest $request, AuditLogger $audit): JsonResponse
    {
        $this->authorize('notifications.broadcast');

        $audience = $request->input('audience', []);

        $audit->log(
            'notification.broadcast',
            null,
            newValues: ['title' => $request->input('title'), 'audience' => $audience],
            description: 'Announcement queued',
            actor: $request->user(),
        );

        BroadcastAnnouncement::dispatch(
            $request->string('title')->toString(),
            $request->string('body')->toString(),
            $audience,
            $request->input('route'),
        );

        return ApiResponse::success(
            ['queued' => true],
            'Announcement queued for delivery.',
            202,
        );
    }

    // --- Internals ---------------------------------------------------------

    /**
     * `published_at` is stamped the first time a page goes live and never
     * moved afterwards. It is the date shown on a terms page, and quietly
     * updating it every time a typo is fixed makes the document look
     * rewritten when it was not — which on a legal page is the difference
     * between a correction and a change of terms.
     *
     * @return array<string, mixed>
     */
    protected function attributes(CmsContentRequest $request, string $type): array
    {
        $attributes = $request->validated();

        if ($type === 'pages' && ($attributes['is_published'] ?? false) && ! array_key_exists('published_at', $attributes)) {
            $existing = $request->route() && ! $request->isMethod('POST')
                ? CmsResources::modelFor($type)::find(array_values($request->route()->parameters())[0] ?? null)
                : null;

            if (! $existing?->published_at) {
                $attributes['published_at'] = now();
            }
        }

        return $attributes;
    }
}
