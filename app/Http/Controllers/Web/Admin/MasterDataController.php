<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\MasterDataRequest;
use App\Models\Community;
use App\Models\Religion;
use App\Services\AuditLogger;
use App\Services\MasterData\MasterDataCache;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * The `Web\Admin` sibling of {@see \App\Http\Controllers\Api\V1\Admin\
 * MasterDataManagementController} — CRUD for the nine lookup tables every
 * client reads and none of them hardcodes.
 *
 * **Every write flushes the master-data cache explicitly**, exactly as the API
 * sibling does. `CacheBustingObserver` covers cities, states, settings and
 * plans but *not* these nine, because those four are written from several
 * places and these nine had exactly one write path. As of this controller they
 * have two — which is precisely the hazard that comment warned about, so the
 * flush is repeated here rather than assumed. Rename a community without it
 * and the old name sits in every dropdown for twenty-four hours.
 *
 * The delete guards are copied for the same reason: the FKs are
 * `nullOnDelete`, so nothing in the database stops a delete that blanks a
 * field on thousands of live profiles. Deactivation is what was actually
 * wanted, and it is an update.
 */
class MasterDataController extends Controller
{
    /**
     * Which profile column points at each lookup table — the check that turns
     * a silent data loss into a refusal. Mirrors the API sibling's constant;
     * two copies of this list is a real cost, but importing a constant off a
     * controller is worse, and the day a tenth lookup table is added both
     * files are edited together anyway.
     */
    protected const REFERENCED_BY = [
        'religions' => 'religion_id',
        'communities' => 'community_id',
        'sub-communities' => 'sub_community_id',
        'mother-tongues' => 'mother_tongue_id',
        'educations' => 'education_id',
        'occupations' => 'occupation_id',
        'income-ranges' => 'income_range_id',
        'marital-statuses' => 'marital_status_id',
    ];

    public function __construct(protected MasterDataCache $cache) {}

    public function index(Request $request, string $type): View
    {
        $this->authorize('master_data.view');

        $model = $this->model($type);

        // Inactive rows included — this is the management list, and a
        // deactivated option has to be visible to whoever might reinstate it.
        // The public `/master-data` endpoint is the filtered one.
        $rows = $model::query()
            ->when($request->filled('q'), fn ($q) => $q->where('name', 'like', '%'.trim($request->string('q')->toString()).'%'))
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate(50)
            ->withQueryString();

        return view('admin.master-data.index', [
            'type' => $type,
            'rows' => $rows,
            'types' => array_keys(MasterDataCache::TYPES),

            // Parent options for the two nested types. Loaded only when the
            // form on screen actually has the select — nine types share one
            // view and eight of them would pay for a query they never render.
            'religions' => $type === 'communities'
                ? Religion::query()->orderBy('name')->get(['id', 'name'])
                : collect(),
            'communities' => $type === 'sub-communities'
                ? Community::query()->orderBy('name')->get(['id', 'name'])
                : collect(),
        ]);
    }

    public function store(MasterDataRequest $request, string $type, AuditLogger $audit): RedirectResponse
    {
        $this->authorize('master_data.manage');

        $row = $this->model($type)::create($request->validated());

        $audit->log('master_data.created', $row, newValues: $row->getAttributes(), actor: $request->user());

        $this->cache->flush();

        return back()->with('status', "“{$row->name}” added. Every client picks it up on its next master-data fetch.");
    }

    public function update(MasterDataRequest $request, string $type, int $id, AuditLogger $audit): RedirectResponse
    {
        $this->authorize('master_data.manage');

        $row = $this->model($type)::findOrFail($id);

        $row->fill($request->validated());
        $row->save();

        $audit->logChanges('master_data.updated', $row, 'Lookup entry edited', $request->user());

        $this->cache->flush();

        return back()->with('status', "“{$row->name}” updated.");
    }

    public function destroy(Request $request, string $type, int $id, AuditLogger $audit): RedirectResponse
    {
        $this->authorize('master_data.manage');

        $row = $this->model($type)::findOrFail($id);

        if ($column = static::REFERENCED_BY[$type] ?? null) {
            $inUse = DB::table('user_profiles')->where($column, $id)->limit(1)->exists();

            // A redirect, not the API's 409: the panel's answer to "you cannot
            // delete this" is the deactivate toggle two columns away, and an
            // error page loses the row the admin was looking at.
            if ($inUse) {
                return back()->withErrors([
                    'delete' => 'Members have selected this option. Deactivate it instead — deleting it would blank the field on their profiles.',
                ]);
            }
        }

        if ($childError = $this->childrenError($type, $id)) {
            return back()->withErrors(['delete' => $childError]);
        }

        $audit->log(
            'master_data.deleted',
            $row,
            oldValues: $row->getAttributes(),
            description: 'Lookup entry deleted',
            actor: $request->user(),
        );

        $row->delete();

        $this->cache->flush();

        return back()->with('status', 'Entry deleted.');
    }

    // --- Internals ---------------------------------------------------------

    /** @return class-string<Model> */
    protected function model(string $type): string
    {
        $model = MasterDataCache::TYPES[$type] ?? null;

        abort_unless($model, 404, 'Not found.');

        return $model;
    }

    /**
     * Communities and sub-communities cascade on their parent, so deleting a
     * religion with communities under it takes them with it — silently, and on
     * rows nobody is looking at.
     */
    protected function childrenError(string $type, int $id): ?string
    {
        [$table, $column] = match ($type) {
            'religions' => ['communities', 'religion_id'],
            'communities' => ['sub_communities', 'community_id'],
            default => [null, null],
        };

        if ($table === null) {
            return null;
        }

        return DB::table($table)->where($column, $id)->limit(1)->exists()
            ? 'This entry has sub-entries. Remove or reassign them first — deleting it would cascade.'
            : null;
    }
}
