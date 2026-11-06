<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A staff account. Narrower than `AdminUserResource` because a staff row has
 * none of a member's profile, and wider in the one way that matters: the
 * effective permission list, which is what the person editing roles is
 * actually trying to see.
 */
class StaffResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'mobile' => $this->mobile,
            'status' => $this->status,

            'role' => $this->whenLoaded('role', fn () => [
                'id' => $this->role->id,
                'name' => $this->role->name,
                'label' => $this->role->label,
            ]),

            // Resolved, not inherited-and-inferred. A super admin's row lists
            // whatever their role happens to hold, but `Gate::before` lets them
            // past every dotted ability regardless — so saying `*` is the only
            // honest answer, and it is the one the panel checks against.
            'permissions' => $this->when(
                $this->relationLoaded('role') && $this->role?->relationLoaded('permissions'),
                fn () => $this->isSuperAdmin() ? ['*'] : $this->role->permissions->pluck('name')->all(),
            ),

            'last_login_at' => $this->last_login_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
