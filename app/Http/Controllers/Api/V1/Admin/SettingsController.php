<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateSettingsRequest;
use App\Models\Setting;
use App\Services\AuditLogger;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * The runtime knobs an admin may change without a deploy.
 *
 * Values are returned **typed**, through `Setting::typed()`, not as the raw
 * strings the column stores. A `false` that reaches a client as the string
 * `"false"` is truthy in both JavaScript and Dart, which turns every boolean
 * toggle here into a switch that only works one way.
 *
 * Grouped for the UI, and the group is the only structure the screen needs —
 * `settings.group` is what the panel renders as sections, so a new row appears
 * in the right place with no client change.
 */
class SettingsController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->authorize('settings.view');

        $settings = Setting::query()
            ->group($request->string('group')->toString() ?: null)
            ->orderBy('group')
            ->orderBy('key')
            ->get()
            ->groupBy('group')
            ->map(fn ($rows) => $rows->map(fn (Setting $s) => [
                'key' => $s->key,
                'value' => $s->typed(),
                'type' => $s->type,
                'is_public' => (bool) $s->is_public,
            ])->values());

        return ApiResponse::success($settings, 'Settings retrieved successfully');
    }

    /**
     * Partial update, one transaction.
     *
     * All or nothing matters more here than it looks: these keys are read
     * together by the code that uses them, and a half-applied change — the new
     * commission rate saved, the new currency not — is a configuration nobody
     * intended and nobody can see from the screen.
     *
     * Unknown keys are rejected rather than created. Creating one writes a row
     * nothing reads and shows a saved value that does nothing.
     */
    public function update(UpdateSettingsRequest $request, AuditLogger $audit): JsonResponse
    {
        $this->authorize('settings.manage');

        $input = $request->input('settings');

        $rows = Setting::whereIn('key', array_keys($input))->get()->keyBy('key');

        if ($unknown = array_diff(array_keys($input), $rows->keys()->all())) {
            return ApiResponse::error(
                'Unknown setting keys.',
                ['keys' => array_values($unknown)],
                422,
            );
        }

        $changes = [];

        DB::transaction(function () use ($input, $rows, &$changes) {
            foreach ($input as $key => $value) {
                $setting = $rows[$key];
                $stored = $this->serialise($setting->type, $value);

                if ($stored === $setting->value) {
                    continue;
                }

                $changes[$key] = ['from' => $setting->typed(), 'to' => $value];

                // Saved one row at a time rather than in a mass update: the
                // observer that busts the settings cache listens to model
                // events, and a query-builder update would leave every worker
                // serving the old value until the key happened to expire —
                // and `Setting::all_cached` uses `rememberForever`, so it
                // never would.
                $setting->update(['value' => $stored]);
            }
        });

        if ($changes !== []) {
            $audit->log(
                'settings.updated',
                null,
                oldValues: array_map(fn ($c) => $c['from'], $changes),
                newValues: array_map(fn ($c) => $c['to'], $changes),
                description: 'Settings changed: '.implode(', ', array_keys($changes)),
                actor: $request->user(),
            );
        }

        return ApiResponse::success(
            ['changed' => array_keys($changes)],
            $changes === [] ? 'Nothing changed' : count($changes).' settings updated',
        );
    }

    // --- Internals ---------------------------------------------------------

    /**
     * The inverse of `Setting::typed()`. Booleans are stored as `1`/`0`
     * because that is what `filter_var(..., FILTER_VALIDATE_BOOLEAN)` reads
     * back unambiguously — `"true"` also works, `"True"` does not, and the
     * difference only shows up in production.
     */
    protected function serialise(string $type, mixed $value): ?string
    {
        return match ($type) {
            'bool' => filter_var($value, FILTER_VALIDATE_BOOLEAN) ? '1' : '0',
            'int' => (string) (int) $value,
            'json' => json_encode($value),
            default => $value === null ? null : (string) $value,
        };
    }
}
