<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Plan create and edit.
 *
 * `features` is validated **key by key against the config schema**, not as a
 * free-form JSON blob. `FeatureGate` reads these keys at runtime and a typo —
 * `can_view_contacts` for `can_view_contact` — produces a plan that silently
 * grants nothing, discovered by the first member who pays for it. The schema
 * in `config('matrimony.membership.features')` is the single definition of
 * what a feature key is, and this is where a plan is held to it.
 *
 * Quota values accept -1 for unlimited, which is the convention `FeatureGate`
 * and `PlanResource` both already implement.
 */
class MembershipPlanRequest extends FormRequest
{
    public function rules(): array
    {
        $planId = $this->route('membership_plan')?->id;

        return [
            'name' => [$this->isMethod('POST') ? 'required' : 'sometimes', 'string', 'max:80'],
            'slug' => [
                $this->isMethod('POST') ? 'required' : 'sometimes',
                'string', 'max:100', 'alpha_dash',
                Rule::unique('membership_plans', 'slug')->ignore($planId),
            ],
            'description' => ['nullable', 'string', 'max:2000'],

            'price' => [$this->isMethod('POST') ? 'required' : 'sometimes', 'numeric', 'min:0', 'max:99999999.99'],
            'currency' => ['sometimes', 'string', 'size:3'],

            // Null means "never expires", which is how the free tier is
            // expressed. Only one plan should carry it, and that plan is the
            // default one.
            'duration_days' => ['nullable', 'integer', 'min:1', 'max:3650'],

            'features' => [$this->isMethod('POST') ? 'required' : 'sometimes', 'array'],
            'features.*' => ['present'],

            'is_active' => ['sometimes', 'boolean'],
            'is_featured' => ['sometimes', 'boolean'],
            'badge_text' => ['nullable', 'string', 'max:40'],
            'sort_order' => ['sometimes', 'integer', 'min:0', 'max:9999'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if (! is_array($features = $this->input('features'))) {
                return;
            }

            $schema = config('matrimony.membership.features');

            foreach ($features as $key => $value) {
                if (! isset($schema[$key])) {
                    $validator->errors()->add("features.{$key}", "Unknown feature key [{$key}].");

                    continue;
                }

                $error = match ($schema[$key]['type']) {
                    'quota' => is_int($value) && ($value >= 0 || $value === -1)
                        ? null
                        : 'must be a whole number, or -1 for unlimited',
                    'capability' => is_bool($value) ? null : 'must be true or false',
                    'enum' => in_array($value, $schema[$key]['options'], true)
                        ? null
                        : 'must be one of: '.implode(', ', $schema[$key]['options']),
                    default => null,
                };

                if ($error) {
                    $validator->errors()->add("features.{$key}", "{$schema[$key]['label']} {$error}.");
                }
            }

            // A plan missing a key is not a plan with that feature disabled —
            // `FeatureGate` falls back to the default plan for anything absent,
            // so an omitted `can_chat` quietly grants whatever the free tier
            // grants. Requiring the full set makes every plan explicit.
            if ($missing = array_diff(array_keys($schema), array_keys($features))) {
                $validator->errors()->add('features', 'Missing feature keys: '.implode(', ', $missing).'.');
            }
        });
    }
}
