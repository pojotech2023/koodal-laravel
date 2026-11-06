<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * The authenticated user's own identity. Never used to render *another* user —
 * that is ProfileResource, which applies visibility. Keeping them separate is
 * what stops a copy-paste from leaking a mobile number.
 */
class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
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
            'role' => $this->whenLoaded('role', fn () => $this->role->name, 'user'),
            'email_verified' => $this->email_verified_at !== null,
            'mobile_verified' => $this->mobile_verified_at !== null,
            'is_aadhaar_verified' => (bool) $this->is_aadhaar_verified,
            'aadhaar_verified_at' => $this->aadhaar_verified_at?->toIso8601String(),
            'aadhaar_number_masked' => $this->aadhaar_number ? 'XXXX XXXX ' . substr($this->aadhaar_number, -4) : null,
            'badge' => [
                'verified' => (bool) ($this->is_aadhaar_verified || $this->profile?->is_verified),
                'label' => ($this->is_aadhaar_verified || $this->profile?->is_verified) ? 'Verified Profile' : 'Unverified',
                'icon' => ($this->is_aadhaar_verified || $this->profile?->is_verified) ? '✓' : null,
            ],
            'registration_source' => $this->registration_source,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
