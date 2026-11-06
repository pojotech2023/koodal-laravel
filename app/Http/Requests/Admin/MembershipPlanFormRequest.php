<?php

namespace App\Http\Requests\Admin;

/**
 * `MembershipPlanRequest` submitted by an HTML form instead of a JSON body.
 *
 * The parent validates `features` **by type** — `is_int()` for a quota,
 * `is_bool()` for a capability — which is exactly right for the API, where a
 * client that sends `"true"` has a bug worth failing on. An HTML form has no
 * types at all: every field arrives as a string, and an unticked checkbox
 * arrives not at all. Submitting the panel's plan editor straight into the
 * parent would fail every capability with "must be true or false" and every
 * quota with "must be a whole number", on a correct form.
 *
 * So this subclass — rather than loosening the parent's rules, which would let
 * a real API bug through, and rather than a second rule set, which would drift
 * from `config('matrimony.membership.features')` the moment a feature is
 * added. One rule set, one schema, two encodings.
 *
 * Building the map from the schema's keys (not from what was posted) also
 * means the panel always submits the complete set, which is the parent's
 * "missing feature keys" rule: a plan with an absent key does not have that
 * feature off, it silently inherits the default plan's — see `FeatureGate`.
 */
class MembershipPlanFormRequest extends MembershipPlanRequest
{
    protected function prepareForValidation(): void
    {
        $schema = config('matrimony.membership.features', []);

        $features = [];

        foreach ($schema as $key => $meta) {
            $raw = $this->input("features.{$key}");

            $features[$key] = match ($meta['type']) {
                // "" (the field cleared) stays as-is so the parent reports it
                // rather than this silently storing 0 — an accidentally empty
                // quota is a plan that grants nothing.
                'quota' => is_numeric($raw) ? (int) $raw : $raw,

                // Paired with a hidden "0" in the form, so an unticked box
                // arrives as "0" rather than not arriving at all.
                'capability' => (bool) $raw,

                default => $raw,
            };
        }

        $this->merge(['features' => $features]);
    }
}
