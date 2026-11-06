<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ImportLocationsRequest;
use App\Http\Requests\Admin\LocationRequest;
use App\Models\City;
use App\Models\Country;
use App\Models\State;
use App\Services\AuditLogger;
use App\Services\MasterData\MasterDataCache;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * The `Web\Admin` sibling of {@see \App\Http\Controllers\Api\V1\Admin\
 * LocationManagementController} — the cascading country → state → city tables,
 * plus the city CSV import.
 *
 * The asymmetry worth not rediscovering: `CacheBustingObserver` handles City
 * and State precisely (a renamed city forgets only its own state's key) but
 * **Country is not observed**, so country writes flush the whole master-data
 * cache here explicitly. Miss it and a new country is absent from every signup
 * form for a day.
 *
 * The delete guards are stricter than master data's for a reason that is not
 * stylistic: these FKs *cascade* rather than nulling, so an unchecked delete
 * of a state is not "a field goes blank" — it is four hundred cities gone and
 * every profile in them losing its location.
 */
class LocationController extends Controller
{
    /** URL segment → model. Keeps the route honest without a `where` clause. */
    protected const MODELS = [
        'countries' => Country::class,
        'states' => State::class,
        'cities' => City::class,
    ];

    public function __construct(protected MasterDataCache $cache) {}

    public function index(Request $request, string $type): View
    {
        $this->authorize('locations.view');

        $request->validate([
            'country_id' => ['nullable', 'integer'],
            'state_id' => ['nullable', 'integer'],
            'q' => ['nullable', 'string', 'max:60'],
        ]);

        $rows = $this->model($type)::query()
            ->when($type === 'states', fn ($q) => $q->with('country:id,name'))
            ->when($type === 'cities', fn ($q) => $q->with('state:id,name,country_id', 'state.country:id,name'))
            ->when($type === 'states' && $request->integer('country_id'), fn ($q, $id) => $q->where('country_id', $id))
            ->when($type === 'cities' && $request->integer('state_id'), fn ($q, $id) => $q->where('state_id', $id))
            ->when($request->filled('q'), fn ($q) => $q->where('name', 'like', trim($request->string('q')->toString()).'%'))
            ->orderBy('name')

            // Paginated for every type here, unlike the API — which returns
            // countries and states whole because a client renders them as one
            // dropdown. A management table is not a dropdown, and 200 country
            // rows with an edit form each is a slow page for no benefit.
            ->paginate($type === 'cities' ? 100 : 50)
            ->withQueryString();

        return view('admin.locations.index', [
            'type' => $type,
            'rows' => $rows,
            'types' => array_keys(static::MODELS),

            // Parent pickers, loaded only for the type that renders them.
            'countries' => $type === 'states'
                ? Country::query()->orderBy('name')->get(['id', 'name'])
                : collect(),
            'states' => $type === 'cities'
                ? State::query()->with('country:id,name')->orderBy('name')->get(['id', 'name', 'country_id'])
                : collect(),
        ]);
    }

    public function store(LocationRequest $request, string $type, AuditLogger $audit): RedirectResponse
    {
        $this->authorize('locations.manage');

        $row = $this->model($type)::create($request->validated());

        $audit->log('location.created', $row, newValues: $row->getAttributes(), actor: $request->user());

        $this->flushIfCountry($type);

        return back()->with('status', "“{$row->name}” added.");
    }

    public function update(LocationRequest $request, string $type, int $id, AuditLogger $audit): RedirectResponse
    {
        $this->authorize('locations.manage');

        $row = $this->model($type)::findOrFail($id);

        $row->fill($request->validated());
        $row->save();

        $audit->logChanges('location.updated', $row, 'Location edited', $request->user());

        $this->flushIfCountry($type);

        return back()->with('status', "“{$row->name}” updated.");
    }

    public function destroy(Request $request, string $type, int $id, AuditLogger $audit): RedirectResponse
    {
        $this->authorize('locations.manage');

        $row = $this->model($type)::findOrFail($id);

        [$childTable, $childColumn] = match ($type) {
            'countries' => ['states', 'country_id'],
            'states' => ['cities', 'state_id'],
            default => [null, null],
        };

        if ($childTable && DB::table($childTable)->where($childColumn, $id)->limit(1)->exists()) {
            return back()->withErrors([
                'delete' => 'This location has entries beneath it. Deactivate it instead — deleting it would remove them all.',
            ]);
        }

        $profileColumn = match ($type) {
            'countries' => 'country_id',
            'states' => 'state_id',
            'cities' => 'city_id',
        };

        if (DB::table('user_profiles')->where($profileColumn, $id)->limit(1)->exists()) {
            return back()->withErrors([
                'delete' => 'Members live here. Deactivate it instead so their profiles keep their location.',
            ]);
        }

        $audit->log('location.deleted', $row, oldValues: $row->getAttributes(), description: 'Location deleted', actor: $request->user());

        $row->delete();

        $this->flushIfCountry($type);

        return back()->with('status', 'Location deleted.');
    }

    /**
     * The city CSV import, run inline and capped at 5,000 rows — the API
     * sibling's reasoning holds here even harder: an import is one person
     * waiting on one screen, and what they need is which lines were rejected.
     * This version can actually give them that, in the page, rather than in a
     * JSON body somebody has to read.
     *
     * Upserted on `(state_id, slug)` — the table's own unique key — so running
     * the same file twice corrects rows instead of duplicating them, which is
     * what happens when an import half-fails and somebody retries it.
     */
    public function import(ImportLocationsRequest $request, AuditLogger $audit): RedirectResponse
    {
        $this->authorize('locations.manage');

        $handle = fopen($request->file('file')->getRealPath(), 'r');

        $header = fgetcsv($handle);

        if (! is_array($header) || ! in_array('name', array_map('strtolower', $header), true)) {
            fclose($handle);

            return back()->withErrors(['file' => 'The first row must be a header containing at least state_id and name.']);
        }

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
                $rejected[] = "Line {$line}: missing state_id or name";

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

        // An unknown state_id would fail the whole upsert on a foreign key,
        // taking 4,999 good rows with it. Filtered here, and reported so the
        // operator can fix the file.
        $validStates = State::whereIn('id', array_column($rows, 'state_id'))->pluck('id')->flip();

        $rows = array_values(array_filter($rows, function ($row) use ($validStates, &$rejected) {
            if ($validStates->has($row['state_id'])) {
                return true;
            }

            $rejected[] = "{$row['name']}: unknown state_id {$row['state_id']}";

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

        // `upsert` bypasses Eloquent events, so `CacheBustingObserver` never
        // fires for any of these rows — the cache has to be cleared by hand or
        // the new cities are invisible for a day.
        $this->cache->flush();

        return back()
            ->with('status', count($rows).' cities imported.')

            // Flashed rather than returned: the rejected lines are the whole
            // point of running this inline, and they have to survive the
            // redirect to be read. Capped so a pathological file cannot fill
            // the session.
            ->with('import_rejected', array_slice($rejected, 0, 50));
    }

    // --- Internals ---------------------------------------------------------

    /** @return class-string<Model> */
    protected function model(string $type): string
    {
        $model = static::MODELS[$type] ?? null;

        abort_unless($model, 404, 'Not found.');

        return $model;
    }

    /** Country is the one of the three `CacheBustingObserver` does not watch. */
    protected function flushIfCountry(string $type): void
    {
        if ($type === 'countries') {
            $this->cache->flush();
        }
    }
}
