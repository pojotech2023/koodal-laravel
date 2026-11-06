<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * One item in the approval queue.
 *
 * The moderator's job is to decide whether this profile is real and whether
 * the free text is clean, so the payload is built around exactly that: the
 * identity fields to sanity-check against each other, every free-text field a
 * spammer would use to smuggle a phone number, and the photos.
 *
 * Lookups are resolved to names. A queue screen that renders `religion_id: 4`
 * forces the moderator to hold nine lookup tables in their head, and the join
 * costs nothing at 84 rows a page.
 */
class AdminProfileResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'approval_status' => $this->approval_status,
            'rejection_reason' => $this->rejection_reason,
            'completion_percentage' => $this->completion_percentage,
            'is_verified' => (bool) $this->is_verified,

            'user' => $this->whenLoaded('user', fn () => [
                'id' => $this->user->id,
                'profile_code' => $this->user->profile_code,
                'name' => $this->user->name,
                'email' => $this->user->email,
                'mobile' => $this->user->mobile,
                'gender' => $this->user->gender,
                'age' => $this->user->age(),
                'status' => $this->user->status,
                'registration_source' => $this->user->registration_source,
                'created_at' => $this->user->created_at?->toIso8601String(),
            ]),

            'basic' => [
                'profile_created_by' => $this->profile_created_by,
                'height_cm' => $this->height_cm,
                'height_label' => $this->heightLabel(),
                'marital_status' => $this->whenLoaded('maritalStatus', fn () => $this->maritalStatus->name),
                'physical_status' => $this->physical_status,
                'children_count' => $this->children_count,
            ],

            'religious' => [
                'religion' => $this->whenLoaded('religion', fn () => $this->religion->name),
                'community' => $this->whenLoaded('community', fn () => $this->community->name),
                'sub_community' => $this->whenLoaded('subCommunity', fn () => $this->subCommunity->name),
                'mother_tongue' => $this->whenLoaded('motherTongue', fn () => $this->motherTongue->name),
                'caste' => $this->caste,
                'manglik' => $this->manglik,
            ],

            'education' => [
                'education' => $this->whenLoaded('education', fn () => $this->education->name),
                'college' => $this->college,
                'specialization' => $this->specialization,
            ],

            'professional' => [
                'occupation' => $this->whenLoaded('occupation', fn () => $this->occupation->name),
                'company' => $this->company,
                'job_type' => $this->job_type,
                'income_range' => $this->whenLoaded('incomeRange', fn () => $this->incomeRange->name),
            ],

            'location' => [
                'country' => $this->whenLoaded('country', fn () => $this->country->name),
                'state' => $this->whenLoaded('state', fn () => $this->state->name),
                'city' => $this->whenLoaded('city', fn () => $this->city->name),
                'residency_status' => $this->residency_status,
                'pincode' => $this->pincode,
            ],

            // The reason this screen exists. Contact details hidden in free
            // text are the most common rejection on the list, and they are only
            // ever visible if the moderator is shown the text itself.
            'free_text' => [
                'about_me' => $this->about_me,
                'expectations' => $this->expectations,
                'family_description' => $this->family_description,
            ],

            // `user.photos`, not `photos`. The relation on this model is
            // approved-only because it feeds the public profile payload, and a
            // moderation queue that hides the photos still awaiting moderation
            // shows the reviewer everything except what they are there for.
            'photos' => $this->relationLoaded('user') && $this->user?->relationLoaded('photos')
                ? AdminPhotoResource::collection($this->user->photos)
                : [],

            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
