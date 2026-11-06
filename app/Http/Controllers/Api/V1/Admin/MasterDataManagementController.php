<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\MasterDataRequest;
use App\Services\AuditLogger;
use App\Services\MasterData\MasterDataCache;
use App\Support\ApiResponse;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * CRUD for the nine lookup tables every client reads and none of them
 * hardcodes.
 *
 * **Every write flushes the master-data cache explicitly.**
 * `CacheBustingObserver` covers cities, states, settings and plans, but not
 * these nine — and that is not an omission to quietly fix here: those four are
 * written from several places, these nine have exactly one write path, which
 * is this controller. The consequence is worth stating plainly, because it is
 * the bug a future session would otherwise introduce: **any new write path to
 * a lookup table must flush too, or an admin renames a community and watches
 * the old name sit in the dropdown for twenty-four hours.**
 */
class MasterDataManagementController extends Controller
{
    /**
     * Which profile column points at each lookup table. Used to refuse a
     * delete that would silently blank a field on live profiles — the FKs are
     * `nullOnDelete`, so the database will not stop it and thousands of
     * members would simply lose their religion or their mother tongue.
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

    public function index(Request $request, string $type): JsonResponse
    {
        $this->authorize('master_data.view');

        // Inactive rows included — this is the management list, and a
        // deactivated option has to be visible to whoever might reinstate it.
        // The public `/master-data` endpoint is the filtered one.
        $rows = $this->model($type)::query()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return ApiResponse::success($rows, 'Master data retrieved successfully');
    }

    public function store(MasterDataRequest $request, string $type, AuditLogger $audit): JsonResponse
    {
        $this->authorize('master_data.manage');

        $row = $this->model($type)::create($request->validated());

        $audit->log('master_data.created', $row, newValues: $row->getAttributes(), actor: $request->user());

        $this->cache->flush();

        return ApiResponse::created($row, 'Entry created');
    }

    public function update(MasterDataRequest $request, string $type, int $id, AuditLogger $audit): JsonResponse
    {
        $this->authorize('master_data.manage');

        $row = $this->model($type)::findOrFail($id);

        $row->fill($request->validated());
        $row->save();

        $audit->logChanges('master_data.updated', $row, 'Lookup entry edited', $request->user());

        $this->cache->flush();

        return ApiResponse::success($row->fresh(), 'Entry updated');
    }

    /**
     * Refused outright once anyone has selected the option.
     *
     * Deactivation is what was actually wanted — it removes the entry from
     * every dropdown while leaving the members who chose it with a profile
     * that still says what they said. Deleting the row nulls their column
     * instead, silently, on rows nobody is looking at, and there is no way
     * back from it.
     */
    public function destroy(Request $request, string $type, int $id, AuditLogger $audit): JsonResponse
    {
        $this->authorize('master_data.manage');

        $row = $this->model($type)::findOrFail($id);

        if ($column = static::REFERENCED_BY[$type] ?? null) {
            $inUse = DB::table('user_profiles')->where($column, $id)->limit(1)->exists();

            abort_if(
                $inUse,
                409,
                'Members have selected this option. Deactivate it instead — deleting it would blank the field on their profiles.',
            );
        }

        // Communities and sub-communities cascade on their parent, so deleting
        // a religion with communities under it takes them with it. The child
        // rows are checked through the same guard above via their own profile
        // column, but the parent has to be checked for children as well.
        $this->assertNoChildren($type, $id);

        $audit->log(
            'master_data.deleted',
            $row,
            oldValues: $row->getAttributes(),
            description: 'Lookup entry deleted',
            actor: $request->user(),
        );

        $row->delete();

        $this->cache->flush();

        return ApiResponse::success(null, 'Entry deleted');
    }

    // --- Internals ---------------------------------------------------------

    /** @return class-string<Model> */
    protected function model(string $type): string
    {
        $model = MasterDataCache::TYPES[$type] ?? null;

        abort_unless($model, 404, 'Not found.');

        return $model;
    }

    protected function assertNoChildren(string $type, int $id): void
    {
        [$table, $column] = match ($type) {
            'religions' => ['communities', 'religion_id'],
            'communities' => ['sub_communities', 'community_id'],
            default => [null, null],
        };

        if ($table === null) {
            return;
        }

        abort_if(
            DB::table($table)->where($column, $id)->limit(1)->exists(),
            409,
            'This entry has sub-entries. Remove or reassign them first — deleting it would cascade.',
        );
    }
}
