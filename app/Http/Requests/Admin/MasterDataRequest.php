<?php

namespace App\Http\Requests\Admin;

use App\Services\MasterData\MasterDataCache;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * One request over the nine lookup tables, matching the single
 * `master-data/{type}` route.
 *
 * `slug` is what every client and every saved search stores, so it is unique
 * and — on update — deliberately still editable. A typo'd slug that can never
 * be corrected is a permanent wart in the API surface; the cost is that
 * changing one invalidates any client holding the old value, which is why the
 * panel warns and this does not forbid.
 */
class MasterDataRequest extends FormRequest
{
    public function rules(): array
    {
        $type = $this->route('type');
        $table = $this->table($type);
        $id = $this->route('id');
        $required = $this->isMethod('POST') ? 'required' : 'sometimes';

        $rules = [
            'name' => [$required, 'string', 'max:120'],
            'slug' => [$required, 'string', 'max:140', 'alpha_dash', Rule::unique($table, 'slug')->ignore($id)],
            'sort_order' => ['sometimes', 'integer', 'min:0', 'max:65535'],
            'is_active' => ['sometimes', 'boolean'],
        ];

        return array_merge($rules, match ($type) {
            'communities' => ['religion_id' => [$required, 'integer', 'exists:religions,id']],
            'sub-communities' => ['community_id' => [$required, 'integer', 'exists:communities,id']],
            'educations' => ['category' => ['sometimes', 'string', 'max:40']],
            'occupations' => ['category' => ['nullable', 'string', 'max:40']],
            'income-ranges' => [
                'name' => [$required, 'string', 'max:80'],
                'min_amount' => [$required, 'integer', 'min:0'],
                // Null is "and above", which is how the top band is expressed.
                // `gt:min_amount` rather than `gte`: a band whose ends are
                // equal matches exactly one salary figure and is always a typo.
                'max_amount' => ['nullable', 'integer', 'gt:min_amount'],
                'currency' => ['sometimes', 'string', 'size:3'],
            ],
            'lifestyle-options' => [
                'name' => [$required, 'string', 'max:80'],
                // Five sub-lists share this table, so `type` is what makes a
                // row mean anything. The slug is unique per type, not globally.
                'type' => [$required, Rule::in(['diet', 'smoking', 'drinking', 'body_type', 'complexion'])],
                'slug' => [
                    $required, 'string', 'max:100', 'alpha_dash',
                    Rule::unique($table, 'slug')
                        ->where(fn ($q) => $q->where('type', $this->input('type')))
                        ->ignore($id),
                ],
            ],
            default => [],
        });
    }

    protected function table(?string $type): string
    {
        $model = MasterDataCache::TYPES[$type] ?? null;

        abort_unless($model, 404, 'Not found.');

        return (new $model)->getTable();
    }
}
