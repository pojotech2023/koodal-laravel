<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * The `Web\Admin` sibling of {@see \App\Http\Controllers\Api\V1\Admin\
 * SettingsController} — the runtime knobs an admin may change without a
 * deploy, grouped by `settings.group`, which is the only structure the screen
 * needs: a new row appears in the right section with no view change.
 *
 * Three things carried over exactly, each of which is a bug if it is not:
 *
 * 1. **One transaction, all or nothing.** These keys are read together by the
 *    code that uses them, and a half-applied change — the new rate saved, the
 *    new currency not — is a configuration nobody intended and nobody can see
 *    from the screen.
 * 2. **Saved one row at a time, never a mass update.** The observer that busts
 *    the settings cache listens to model events, and `Setting::all_cached`
 *    uses `rememberForever` — a query-builder update would leave every worker
 *    serving the old value indefinitely, not just until an expiry.
 * 3. **Unknown keys are refused, not created.** A settings screen that accepts
 *    a typo'd key writes a row nothing reads and shows a saved value that does
 *    nothing.
 *
 * `UpdateSettingsRequest` is *not* reused, and the reason is worth stating
 * because reusing it would look tidier: it accepts any value for any key,
 * which is correct for a typed JSON body and unsafe for a form, where a `json`
 * setting arrives as free text a person typed. Invalid JSON is refused here
 * (see `invalidJsonKeys()`) rather than stored as a string that
 * `Setting::typed()` will hand back as `null` — a broken setting that reads as
 * "not set".
 *
 * The other form-vs-JSON difference lives in the view: an unticked checkbox
 * submits nothing at all, so every boolean is paired with a hidden `0`.
 * Without that, a toggle could be switched on and never off.
 */
class SettingsController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('settings.view');

        return view('admin.settings.index', [
            'groups' => Setting::query()
                ->orderBy('group')
                ->orderBy('key')
                ->get()
                ->groupBy('group'),
        ]);
    }

    public function update(Request $request, AuditLogger $audit): RedirectResponse
    {
        $this->authorize('settings.manage');

        $request->validate([
            'settings' => ['required', 'array', 'min:1'],
        ]);

        $input = $request->input('settings');

        $rows = Setting::whereIn('key', array_keys($input))->get()->keyBy('key');

        if ($unknown = array_diff(array_keys($input), $rows->keys()->all())) {
            return back()->withErrors([
                'settings' => 'Unknown setting keys: '.implode(', ', $unknown).'. Nothing was saved.',
            ]);
        }

        if ($invalid = $this->invalidJsonKeys($input, $rows)) {
            return back()->withErrors([
                'settings' => 'These values must be valid JSON: '.implode(', ', $invalid).'. Nothing was saved.',
            ]);
        }

        $changes = $this->changesFrom($input, $rows);

        if ($changes === []) {
            return back()->with('status', 'Nothing changed.');
        }

        DB::transaction(function () use ($changes, $rows) {
            foreach ($changes as $key => $change) {
                $rows[$key]->update(['value' => $change['stored']]);
            }
        });

        $audit->log(
            'settings.updated',
            null,
            oldValues: array_map(fn ($c) => $c['from'], $changes),
            newValues: array_map(fn ($c) => $c['to'], $changes),
            description: 'Settings changed: '.implode(', ', array_keys($changes)),
            actor: $request->user(),
        );

        return back()->with('status', count($changes).' setting(s) updated.');
    }

    // --- Internals ---------------------------------------------------------

    /**
     * `json` settings arrive from a textarea as whatever the admin typed.
     * Stored unparsed, a stray comma makes `Setting::typed()` return null and
     * the setting reads as "not set" everywhere it is used — a failure with no
     * error message anywhere. Checked before the transaction, so one bad field
     * saves nothing rather than half the form.
     *
     * @param  array<string, mixed>  $input
     * @param  Collection<string, Setting>  $rows
     * @return array<int, string>
     */
    protected function invalidJsonKeys(array $input, $rows): array
    {
        $invalid = [];

        foreach ($input as $key => $value) {
            if ($rows[$key]->type !== 'json' || blank($value)) {
                continue;
            }

            json_decode((string) $value, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                $invalid[] = $key;
            }
        }

        return $invalid;
    }

    /**
     * What actually changed, typed both ways.
     *
     * Computed before the transaction so the audit row records the *old*
     * values — reading them after the write would log the new value twice, and
     * this trail is the only record of what a setting used to be.
     *
     * @param  array<string, mixed>  $input
     * @param  Collection<string, Setting>  $rows
     * @return array<string, array{from: mixed, to: mixed, stored: ?string}>
     */
    protected function changesFrom(array $input, $rows): array
    {
        $changes = [];

        foreach ($input as $key => $value) {
            $setting = $rows[$key];
            $stored = $this->serialise($setting->type, $value);

            if ($stored === $setting->value) {
                continue;
            }

            $changes[$key] = [
                'from' => $setting->typed(),
                'to' => $value,
                'stored' => $stored,
            ];
        }

        return $changes;
    }

    /**
     * The inverse of `Setting::typed()`. Booleans store `1`/`0` because that
     * is what `filter_var(..., FILTER_VALIDATE_BOOLEAN)` reads back
     * unambiguously — `"true"` also works, `"True"` does not, and the
     * difference only shows up in production.
     */
    protected function serialise(string $type, mixed $value): ?string
    {
        return match ($type) {
            'bool' => filter_var($value, FILTER_VALIDATE_BOOLEAN) ? '1' : '0',
            'int' => (string) (int) $value,

            // Already JSON text from a textarea, not a PHP structure: this is
            // a form, so re-encoding it would store a JSON string containing
            // JSON. Validity is checked by `invalidJsonKeys()` before we get
            // here.
            'json' => $value === null ? null : (string) $value,

            default => $value === null ? null : (string) $value,
        };
    }
}
