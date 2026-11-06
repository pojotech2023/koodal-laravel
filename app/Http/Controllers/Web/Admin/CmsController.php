<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\BroadcastNotificationRequest;
use App\Http\Requests\Admin\CmsContentRequest;
use App\Jobs\BroadcastAnnouncement;
use App\Models\MembershipPlan;
use App\Services\AuditLogger;
use App\Support\CmsResources;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * The `Web\Admin` sibling of {@see \App\Http\Controllers\Api\V1\Admin\
 * CmsManagementController} — pages, FAQs, banners and success stories behind
 * one controller, plus the announcement broadcast.
 *
 * **The route URIs are `admin/cms/<type>/…` on purpose.** `CmsResources::
 * typeFor()` reads the content type out of the *matched route's URI pattern*
 * (`#cms/([a-z-]+)#`), which is what lets one controller and one FormRequest
 * serve four types without either doing its own string handling — and which
 * is also why the type cannot be influenced by anything a caller sends. Keep
 * the `cms/` segment and the type segment exactly as they are, or every
 * request here 404s in `typeFor()` before reaching a line of this class.
 *
 * Unpublished rows are listed, unlike the public endpoints: this is the
 * authoring screen, and a draft the author cannot see is a draft they will
 * write twice.
 */
class CmsController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('cms.view');

        $type = CmsResources::typeFor($request);
        [$column, $direction] = CmsResources::orderFor($type);

        return view('admin.cms.index', [
            'type' => $type,
            'label' => CmsResources::labelFor($type),
            'items' => CmsResources::modelFor($type)::query()
                ->orderBy($column, $direction)
                ->paginate(50),
        ]);
    }

    public function create(Request $request): View
    {
        $this->authorize('cms.manage');

        $type = CmsResources::typeFor($request);
        $model = CmsResources::modelFor($type);

        return view('admin.cms.form', [
            'type' => $type,
            'label' => CmsResources::labelFor($type),
            'item' => new $model,
        ]);
    }

    public function store(CmsContentRequest $request, AuditLogger $audit): RedirectResponse
    {
        $this->authorize('cms.manage');

        $type = CmsResources::typeFor($request);

        $item = CmsResources::modelFor($type)::create($this->attributes($request, $type, null));

        $audit->log('cms.created', $item, newValues: $item->getAttributes(), actor: $request->user());

        CmsResources::forgetCaches($type, $item);

        return redirect()->route("admin.cms.{$type}.index")
            ->with('status', CmsResources::labelFor($type).' created.');
    }

    public function edit(Request $request, int $id): View
    {
        $this->authorize('cms.manage');

        $type = CmsResources::typeFor($request);

        return view('admin.cms.form', [
            'type' => $type,
            'label' => CmsResources::labelFor($type),
            'item' => CmsResources::modelFor($type)::findOrFail($id),
        ]);
    }

    public function update(CmsContentRequest $request, int $id, AuditLogger $audit): RedirectResponse
    {
        $this->authorize('cms.manage');

        $type = CmsResources::typeFor($request);
        $item = CmsResources::modelFor($type)::findOrFail($id);

        // Cloned before the fill: forgetCaches needs the slug the page was
        // published under, not the one it is being renamed to, or the old URL
        // keeps serving the old content until the TTL runs out.
        $original = clone $item;

        $item->fill($this->attributes($request, $type, $item));
        $item->save();

        $audit->logChanges('cms.updated', $item, CmsResources::labelFor($type).' edited', $request->user());

        CmsResources::forgetCaches($type, $item, $original);

        return redirect()->route("admin.cms.{$type}.index")
            ->with('status', CmsResources::labelFor($type).' updated.');
    }

    public function destroy(Request $request, int $id, AuditLogger $audit): RedirectResponse
    {
        $this->authorize('cms.manage');

        $type = CmsResources::typeFor($request);
        $item = CmsResources::modelFor($type)::findOrFail($id);

        // The whole row goes in the trail rather than just its id. CMS rows
        // carry text somebody wrote, none of these tables is soft-deleted, and
        // "who deleted the terms page" is a question asked with the
        // expectation of getting the content back.
        $audit->log(
            'cms.deleted',
            $item,
            oldValues: $item->getAttributes(),
            description: CmsResources::labelFor($type).' deleted',
            actor: $request->user(),
        );

        $item->delete();

        CmsResources::forgetCaches($type, $item);

        return redirect()->route("admin.cms.{$type}.index")
            ->with('status', CmsResources::labelFor($type).' deleted.');
    }

    // --- Announcements ------------------------------------------------------

    public function broadcastForm(): View
    {
        $this->authorize('notifications.broadcast');

        return view('admin.cms.broadcast', [
            'plans' => MembershipPlan::query()->orderBy('sort_order')->get(['id', 'name']),
        ]);
    }

    /**
     * Audited before the job starts, and the audit row records the audience
     * filters. The decision to send is the auditable event — by the time the
     * fan-out finishes, the interesting question is not how many rows were
     * written but who chose to write them and to whom.
     */
    public function broadcast(BroadcastNotificationRequest $request, AuditLogger $audit): RedirectResponse
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

        return back()->with('status', 'Announcement queued. It goes out to everyone matching the filters — there is no recall.');
    }

    // --- Internals ---------------------------------------------------------

    /**
     * `published_at` is stamped the first time a page goes live and never
     * moved afterwards. It is the date shown on a terms page, and quietly
     * updating it every time a typo is fixed makes the document look
     * rewritten when it was not — which on a legal page is the difference
     * between a correction and a change of terms.
     *
     * Simpler than the API sibling's version only because the model is
     * already resolved here; the rule is identical.
     *
     * @return array<string, mixed>
     */
    protected function attributes(CmsContentRequest $request, string $type, ?Model $existing): array
    {
        $attributes = $request->validated();

        $goingLive = $type === 'pages'
            && ($attributes['is_published'] ?? false)
            && ! array_key_exists('published_at', $attributes)
            && ! $existing?->published_at;

        if ($goingLive) {
            $attributes['published_at'] = now();
        }

        return $attributes;
    }
}
