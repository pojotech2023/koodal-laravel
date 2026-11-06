<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ImportLocationsRequest;
use App\Http\Requests\Admin\LocationRequest;
use App\Models\City;
use App\Models\Country;
use App\Models\State;
use App\Services\AuditLogger;
use App\Services\MasterData\MasterDataCache;
use App\Support\ApiResponse;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * The cascading country → state → city tables.
 *
 * `CacheBustingObserver` already handles City and State precisely — a renamed
 * city forgets only its own state's key. **Country is not observed**, so
 * country writes flush here explicitly. That asymmetry is easy to miss and
 * would show up as a new country missing from every signup form for a day.
 */
class LocationManagementController extends Controller
{
    /** URL segment → model. Keeps the route honest without a `where` clause. */
    protected const MODELS = [
        'countries' => Country::class,
        'states' => State::class,
        'cities' => City::class,
    ];

    public function __construct(protected MasterDataCache $cache) {}

    public function index(Request $request, string $type): JsonResponse
    {
        $this->authorize('locations.view');

        $request->validate([
            'country_id' => ['nullable', 'integer'],
            'state_id' => ['nullable', 'integer'],
            'q' => ['nullable', 'string', 'max:60'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:200'],
        ]);

        $query = $this->model($type)::query()
            ->when($type === 'states' && $request->integer('country_id'), fn ($q, $id) => $q->where('country_id', $id))
            ->when($type === 'cities' && $request->integer('state_id'), fn ($q, $id) => $q->where('state_id', $id))
            ->when($request->filled('q'), fn ($q) => $q->where('name', 'like', $request->string('q')->toString().'%'))
            ->orderBy('name');

        // Cities are paginated and the other two are not: there are 36 states
        // and ~200 countries, and paginating a list the client renders as one
        // dropdown only forces it to loop until the pages run out.
        if ($type === 'cities') {
            return ApiResponse::success(
                $query->paginate($request->integer('per_page', 100))->items(),
                'Cities retrieved successfully',
            );
        }

        return ApiResponse::success($query->get(), 'Locations retrieved successfully');
    }

    public function store(LocationRequest $request, string $type, AuditLogger $audit): JsonResponse
    {
        $this->authorize('locations.manage');

        $row = $this->model($type)::create($request->validated());

        $audit->log('location.created', $row, newValues: $row->getAttributes(), actor: $request->user());

        $this->flushIfCountry($type);

        return ApiResponse::created($row, 'Location created');
    }

    public function update(LocationRequest $request, string $type, int $id, AuditLogger $audit): JsonResponse
    {
        $this->authorize('locations.manage');

        $row = $this->model($type)::findOrFail($id);

        $row->fill($request->validated());
        $row->save();

        $audit->logChanges('location.updated', $row, 'Location edited', $request->user());

        $this->flushIfCountry($type);

        return ApiResponse::success($row->fresh(), 'Location updated');
    }

    /**
     * Refused while anything points at it, in either direction.
     *
     * The FKs here cascade rather than nulling — deleting a state takes its
     * cities with it — so an unchecked delete is not "a field goes blank", it
     * is "four hundred cities disappear and every profile in them loses its
     * location". Deactivation is the operation that was wanted.
     */
    public function destroy(Request $request, string $type, int $id, AuditLogger $audit): JsonResponse
    {
        $this->authorize('locations.manage');

        $row = $this->model($type)::findOrFail($id);

        [$childTable, $childColumn] = match ($type) {
            'countries' => ['states', 'country_id'],
            'states' => ['cities', 'state_id'],
            default => [null, null],
        };

        abort_if(
            $childTable && DB::table($childTable)->where($childColumn, $id)->limit(1)->exists(),
            409,
            'This location has entries beneath it. Deactivate it instead — deleting it would remove them all.',
        );

        $profileColumn = match ($type) {
            'countries' => 'country_id',
            'states' => 'state_id',
            'cities' => 'city_id',
        };

        abort_if(
            DB::table('user_profiles')->where($profileColumn, $id)->limit(1)->exists(),
            409,
            'Members live here. Deactivate it instead so their profiles keep their location.',
        );

        $audit->log('location.deleted', $row, oldValues: $row->getAttributes(), description: 'Location deleted', actor: $request->user());

        $row->delete();

        $this->flushIfCountry($type);

        return ApiResponse::success(null, 'Location deleted');
    }

    /**
     * Bulk city import from a CSV of `state_id,name[,slug,latitude,longitude]`.
     *
     * Upserted on `(state_id, slug)` — the table's own unique key — so running
     * the same file twice corrects the rows instead of duplicating them, which
     * is what actually happens when an import half-fails and somebody retries
     * it.
     *
     * Run inline rather than queued, and capped at 5,000 rows. An import is
     * one person waiting on one screen, and the feedback they need — which
     * lines were rejected — is worth more than the seconds a queue would save
     * them. Above the cap it stops being that, and the answer is the seeder.
     */
    public function import(ImportLocationsRequest $request, AuditLogger $audit): JsonResponse
    {
        $this->authorize('locations.manage');

        $handle = fopen($request->file('file')->getRealPath(), 'r');

        $header = fgetcsv($handle);

        abort_unless(
            is_array($header) && in_array('name', array_map('strtolower', $header), true),
            422,
            'The first row must be a header containing at least state_id and name.',
        );

        $columns = array_map(fn ($h) => strtolower(trim((string) $h)), $header);
        $rows = [];
        $rejected = [];
        $line = 1;

        while (($record = fgetcsv($handle)) !== false && count($rows) < 5000) {
            $line++;
            $data = array_combine($columns, array_pad(array_slice($record, 0, count($columns)), count($columns), null));

            $name = trim((string) ($data['name'] ?? ''));
            $stateId = (int) ($data['state_id'] ?? 0);

            if ($name === '' || $stateId === 0) {
                $rejected[] = ['line' => $line, 'reason' => 'Missing state_id or name'];

                continue;
            }

            $rows[] = [
                'state_id' => $stateId,
                'name' => $name,
                'slug' => Str::slug($data['slug'] ?? $name),
                'latitude' => is_numeric($data['latitude'] ?? null) ? $data['latitude'] : null,
                'longitude' => is_numeric($data['longitude'] ?? null) ? $data['longitude'] : null,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        fclose($handle);

        // A state_id that does not exist would fail the whole upsert on a
        // foreign key, taking 4,999 good rows with it. Filtered here, and the
        // rejected lines are reported so the operator can fix the file.
        $validStates = State::whereIn('id', array_column($rows, 'state_id'))->pluck('id')->flip();

        $rows = array_values(array_filter($rows, function ($row) use ($validStates, &$rejected) {
            if ($validStates->has($row['state_id'])) {
                return true;
            }

            $rejected[] = ['name' => $row['name'], 'reason' => "Unknown state_id {$row['state_id']}"];

            return false;
        }));

        foreach (array_chunk($rows, 500) as $chunk) {
            City::upsert($chunk, ['state_id', 'slug'], ['name', 'latitude', 'longitude', 'is_active', 'updated_at']);
        }

        $audit->log(
            'location.imported',
            null,
            newValues: ['imported' => count($rows), 'rejected' => count($rejected)],
            description: 'City CSV imported',
            actor: $request->user(),
        );

        // `upsert` bypasses Eloquent events, so CacheBustingObserver never
        // fires for any of these rows — the cache has to be cleared by hand or
        // the new cities are invisible for a day.
        $this->cache->flush();

        return ApiResponse::success([
            'imported' => count($rows),
            'rejected' => $rejected,
        ], count($rows).' cities imported');
    }

    // --- Internals ---------------------------------------------------------

    /** @return class-string<Model> */
    protected function model(string $type): string
    {
        $model = static::MODELS[$type] ?? null;

        abort_unless($model, 404, 'Not found.');

        return $model;
    }

    protected function flushIfCountry(string $type): void
    {
        if ($type === 'countries') {
            $this->cache->flush();
        }
    }
}
