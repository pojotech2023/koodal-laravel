<?php

namespace App\Http\Resources;

use App\Support\Astrology;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;


/**
 * The user's own profile, unfiltered.
 *
 * Kept separate from ProfileResource on purpose. ProfileResource exists to
 * *hide* things; reusing it here would mean a user could not see their own
 * mobile number, and adding a "unless it's you" branch to it is exactly how a
 * visibility rule eventually gets inverted by accident.
 */
class OwnProfileResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $profile = $this->profile;

        return [
            'account' => new UserResource($this->resource),

            'basic' => [
                'profile_created_by' => $profile?->profile_created_by,
                'height_cm' => $profile?->height_cm,
                'height_label' => $profile?->heightLabel(),
                'weight_kg' => $profile?->weight_kg,
                'marital_status_id' => $profile?->marital_status_id,
                'marital_status' => $profile?->maritalStatus?->name,
                'body_type' => $profile?->body_type,
                'complexion' => $profile?->complexion,
                'physical_status' => $profile?->physical_status,
                'children_count' => $profile?->children_count,
                'children_living_status' => $profile?->children_living_status,
            ],

            'religious' => [
                'religion_id' => $profile?->religion_id,
                'religion' => $profile?->religion?->name,
                'community_id' => $profile?->community_id,
                'community' => $profile?->community?->name,
                'sub_community_id' => $profile?->sub_community_id,
                'mother_tongue_id' => $profile?->mother_tongue_id,
                'mother_tongue' => $profile?->motherTongue?->name,
                'caste' => $profile?->caste,
                'gothra' => $profile?->gothra,
                // The website's names, also for values the app saved in its
                // own spelling before the two were aligned.
                'star' => Astrology::star($profile?->star) ?? $profile?->star,
                'raasi' => Astrology::raasi($profile?->raasi) ?? $profile?->raasi,
                'manglik' => $profile?->manglik,
                'birth_time' => $profile?->birth_time,
                'birth_place' => $profile?->birth_place,
            ],

            'education' => [
                'education_id' => $profile?->education_id,
                'education' => $profile?->education?->name,
                'education_detail' => $profile?->education_detail,
                'college' => $profile?->college,
                'university' => $profile?->university,
                'specialization' => $profile?->specialization,
            ],

            'professional' => [
                'occupation_id' => $profile?->occupation_id,
                'occupation' => $profile?->occupation?->name,
                'company' => $profile?->company,
                'job_type' => $profile?->job_type,
                'income_range_id' => $profile?->income_range_id,
                'income_range' => $profile?->incomeRange?->name,
                'work_country_id' => $profile?->work_country_id,
                'work_state_id' => $profile?->work_state_id,
                'work_city_id' => $profile?->work_city_id,
            ],

            'location' => [
                'country_id' => $profile?->country_id,
                'country' => $profile?->country?->name,
                'state_id' => $profile?->state_id,
                'state' => $profile?->state?->name,
                'city_id' => $profile?->city_id,
                'city' => $profile?->city?->name,
                'residency_status' => $profile?->residency_status,
                'pincode' => $profile?->pincode,
            ],

            'family' => [
                'father_occupation' => $profile?->father_occupation,
                'mother_occupation' => $profile?->mother_occupation,
                'brothers_count' => $profile?->brothers_count,
                'brothers_married' => $profile?->brothers_married,
                'sisters_count' => $profile?->sisters_count,
                'sisters_married' => $profile?->sisters_married,
                'family_type' => $profile?->family_type,
                'family_status' => $profile?->family_status,
                'family_values' => $profile?->family_values,
                'family_location' => $profile?->family_location,
                'family_description' => $profile?->family_description,
            ],

            'lifestyle' => [
                'diet' => $profile?->diet,
                'smoking' => $profile?->smoking,
                'drinking' => $profile?->drinking,
                'hobbies' => $profile?->hobbies ?? [],
                'interests' => $profile?->interests ?? [],
            ],

            'about' => [
                'about_me' => $profile?->about_me,
                'expectations' => $profile?->expectations,
            ],

            'photos' => PhotoResource::collection($this->whenLoaded('photos')),

            'status' => [
                'completion_percentage' => $profile?->completion_percentage ?? 0,
                'approval_status' => $profile?->approval_status ?? 'pending',
                'rejection_reason' => $profile?->rejection_reason,
                'is_verified' => (bool) ($profile?->is_verified ?? false),
                'primary_photo_id' => $profile?->primary_photo_id,
            ],

            // Own counters only. These are the numbers the "profile insights"
            // card renders; they are never exposed on another user's profile.
            'stats' => [
                'profile_views' => $profile?->profile_views_count ?? 0,
                'shortlisted_by' => $profile?->shortlisted_count ?? 0,
                'interests_received' => $profile?->interests_received_count ?? 0,
            ],
        ];
    }
}
