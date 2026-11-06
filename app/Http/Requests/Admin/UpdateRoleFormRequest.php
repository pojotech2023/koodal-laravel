<?php

namespace App\Http\Requests\Admin;

/**
 * `UpdateRoleRequest` submitted by an HTML checkbox grid instead of a JSON
 * body — the same relationship `MembershipPlanFormRequest` has to its parent,
 * and for the same reason: the rules are right, the encoding is not.
 *
 * Two differences, both forced by how checkboxes serialise:
 *
 * 1. A grid with every box unticked submits **no** `permissions` key at all,
 *    which the parent's `required` rejects. But "this role holds nothing" is a
 *    real state an admin may legitimately want — it is how a role is retired
 *    without deleting it — so the panel always submits an empty marker and
 *    this asks for `present` rather than `required`.
 * 2. That marker is an empty string, which would fail `permissions.*`'s
 *    `integer` rule. It is stripped here, before validation, rather than
 *    filtered in the controller — where it would arrive already rejected.
 */
class UpdateRoleFormRequest extends UpdateRoleRequest
{
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'permissions' => ['present', 'array'],
        ]);
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'permissions' => array_values(array_filter(
                (array) $this->input('permissions', []),
                fn ($id) => $id !== '' && $id !== null,
            )),
        ]);
    }
}
