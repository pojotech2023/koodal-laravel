<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A member as staff sees them — the deliberate opposite of `ProfileResource`,
 * which exists to hide things.
 *
 * Nothing here is gated on the viewer's plan or the member's privacy settings,
 * because that is what "admin" means and pretending otherwise would only make
 * the panel useless while the same data sat one query away. What keeps this
 * honest is elsewhere: the route requires `abilities:admin`, the controller
 * authorises `users.view`, and any *mutation* writes an audit row naming the
 * staff member.
 *
 * Two things are still absent, and not by oversight: the password hash (hidden
 * on the model) and anything pointing at a verification document. A government
 * ID is reachable only through the audited endpoint on
 * `VerificationManagementController`, and a URL leaking into a list response
 * would be a second, unaudited door to it.
 */
class AdminUserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $profile = $this->whenLoaded('profile');
        $subscription = $this->whenLoaded('activeSubscription');

        return [
            'id' => $this->id,
            'profile_code' => $this->profile_code,
            'name' => $this->name,
            'email' => $this->email,
            'mobile' => $this->mobile,
            'gender' => $this->gender,
            'date_of_birth' => $this->date_of_birth?->toDateString(),
            'age' => $this->age(),

            'status' => $this->status,
            'suspension_reason' => $this->suspension_reason,
            'role' => $this->whenLoaded('role', fn () => $this->role->name, 'user'),

            'email_verified' => $this->email_verified_at !== null,
            'mobile_verified' => $this->mobile_verified_at !== null,

            // Flattened rather than nested: this is a table row, and the
            // moderation queue sorts and filters on exactly these four.
            'profile' => $profile ? [
                'approval_status' => $this->profile->approval_status,
                'rejection_reason' => $this->profile->rejection_reason,
                'is_verified' => (bool) $this->profile->is_verified,
                'completion_percentage' => $this->profile->completion_percentage,
                'photo_count' => $this->whenCounted('photos'),
            ] : null,

            'membership' => $subscription ? [
                'plan' => $this->activeSubscription->plan?->name,
                'status' => $this->activeSubscription->status,
                'expires_at' => $this->activeSubscription->expires_at?->toIso8601String(),
            ] : null,

            'registration_source' => $this->registration_source,
            'last_active_at' => $this->last_active_at?->toIso8601String(),
            'last_login_at' => $this->last_login_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),

            // Present only on a soft-deleted row, which is how the Users screen
            // distinguishes "deleted, inside the retention window" from gone.
            'deleted_at' => $this->deleted_at?->toIso8601String(),
        ];
    }
}
