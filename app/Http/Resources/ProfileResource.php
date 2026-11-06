<?php

namespace App\Http\Resources;

use App\Models\ProfilePhoto;
use App\Services\Profile\ProfileVisibility;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;

/**
 * Where "may this viewer see this field" is finally answered.
 *
 * Gated fields are ABSENT from the payload, not null and not present-but-empty.
 * A client cannot leak what it never received, and a null key still tells an
 * attacker the field exists and has a value worth hiding.
 */
class ProfileResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $viewer = $request->user();

        // The profile being rendered IS the owner's profile, and owner() hands
        // back the User with that inverse relation already wired — which is
        // what stops ProfileVisibility lazy-loading it once per row. Was three
        // lines here until the Blade cards turned out to need the identical
        // thing; see UserProfile::owner().
        $owner = $this->resource->owner();

        $visibility = app(ProfileVisibility::class);
        $canSeePhotos = $visibility->canViewPhotos($viewer, $owner);
        $canSeeContact = $visibility->canViewContact($viewer, $owner);

        // Part D: Game completion & chat access
        $gamesCompleted = false;
        $canAccessChat = true;
        if ($viewer && $owner && $viewer->id !== $owner->id) {
            $gamesCompleted = app(\App\Services\KoodalGameService::class)->hasCompletedAllGames($viewer, $owner);
            $canAccessChat = $viewer->hasPaidPlan() || $gamesCompleted;
        }

        // Part G: Contact details customer approval
        $isPaidViewer = $viewer && $viewer->hasPaidPlan();
        $contactApprovalStatus = 'none';
        $isContactApproved = false;
        if ($viewer && $owner && $viewer->id !== $owner->id) {
            if ($isPaidViewer) {
                // Upgraded members do not need approval
                $isContactApproved = true;
                $contactApprovalStatus = 'approved';
            } else {
                $approval = \App\Models\ContactApproval::where('requester_id', $viewer->id)
                    ->where('owner_id', $owner->id)
                    ->first();
                if ($approval) {
                    $contactApprovalStatus = $approval->status;
                    $isContactApproved = ($approval->status === 'approved');
                }
            }
        } elseif ($viewer && $owner && $viewer->id === $owner->id) {
            $isContactApproved = true;
            $contactApprovalStatus = 'approved';
        }

        $canViewContactDetails = $isPaidViewer ? $canSeeContact : $isContactApproved;

        $maskedMobile = $owner->mobile ? substr($owner->mobile, 0, 3).'*****'.substr($owner->mobile, -2) : null;
        $maskedEmail = $owner->email ? preg_replace('/(?<=.{2}).(?=.*@)/u', '*', $owner->email) : null;

        return [
            'profile_code' => $owner->profile_code,
            'name' => $this->displayName($viewer, $owner),
            'age' => $owner->age(),
            'gender' => $owner->gender,
            'height_cm' => $this->height_cm,
            'can_access_chat' => (bool) $canAccessChat,
            'games_completed' => (bool) $gamesCompleted,
            'chat_locked' => ! $canAccessChat,

            'religion' => $this->whenLoaded('religion', fn () => $this->religion->name),
            'community' => $this->whenLoaded('community', fn () => $this->community->name),
            'mother_tongue' => $this->whenLoaded('motherTongue', fn () => $this->motherTongue->name),
            'marital_status' => $this->whenLoaded('maritalStatus', fn () => $this->maritalStatus->name),

            'education' => $this->whenLoaded('education', fn () => $this->education->name),
            'occupation' => $this->whenLoaded('occupation', fn () => $this->occupation->name),
            'city' => $this->whenLoaded('city', fn () => $this->city->name),
            'state' => $this->whenLoaded('state', fn () => $this->state->name),

            'about_me' => $this->about_me,
            'family' => $this->familySection(),
            'lifestyle' => [
                'diet' => $this->diet,
                'smoking' => $this->smoking,
                'drinking' => $this->drinking,
            ],

            // Blurred derivatives are generated server-side. A CSS blur still
            // ships the real pixels to the device.
            //
            // The flag is set on each PhotoResource rather than on the
            // collection: ->additional() on a collection lands on the envelope,
            // never on the items, so a collection-level flag would blur nothing.
            'photos' => $this->whenLoaded('photos', fn () => $this->photoPayload($this->photos, $canSeePhotos)),

            // The card needs one photo, not the album. Search returns this and
            // omits `photos` entirely.
            'primary_photo' => $this->whenLoaded(
                'primaryPhoto',
                fn () => $this->primaryPhoto
                    ? $this->photoPayload(collect([$this->primaryPhoto]), $canSeePhotos)[0]
                    : null,
            ),

            // Approved by a moderator, and shown to every member who may open
            // the profile — the website's profile page does the same. Only
            // the full profile loads them; search cards never carry the key.
            'jadhagams' => $this->when(
                $owner->relationLoaded('approvedJadhagams'),
                fn () => $owner->approvedJadhagams
                    ->map(fn ($jadhagam) => ['id' => $jadhagam->id, 'url' => $jadhagam->url()])
                    ->values()
                    ->all(),
            ),

            'is_verified' => (bool) ($this->is_verified || $owner->is_aadhaar_verified),
            'is_aadhaar_verified' => (bool) $owner->is_aadhaar_verified,
            'verified_badge' => ($this->is_verified || $owner->is_aadhaar_verified) ? [
                'verified' => true,
                'label' => 'Verified Profile',
                'icon' => '✓',
            ] : null,
            'completion_percentage' => $this->completion_percentage,
            'last_active_at' => $this->when(
                $owner->privacySettings?->show_online_status ?? true,
                fn () => $owner->last_active_at?->toIso8601String(),
            ),

            // Part G: Contact details - revealed if paid & allowed OR if free member approved by owner
            'contact' => $canViewContactDetails ? [
                'mobile' => $owner->mobile,
                'email' => $owner->email,
                'whatsapp' => $owner->mobile,
                'is_approved' => (bool) $isContactApproved,
                'approval_status' => $contactApprovalStatus,
            ] : [
                'mobile' => $maskedMobile,
                'email' => $maskedEmail,
                'whatsapp' => null,
                'is_approved' => false,
                'approval_status' => $contactApprovalStatus,
            ],

            // Saves the client N extra calls to decide whether to draw a filled
            // or outlined heart — and stops the button flickering after mount.
            'viewer_state' => $this->when($viewer !== null, fn () => [
                'shortlisted' => $this->when(isset($this->viewer_shortlisted), (bool) $this->viewer_shortlisted),
                'interest_sent' => $this->when(isset($this->viewer_interest_status), $this->viewer_interest_status !== null),
                'interest_status' => $this->viewer_interest_status ?? null,
                'is_matched' => $this->when(isset($this->viewer_is_matched), (bool) $this->viewer_is_matched),
                'can_access_chat' => (bool) $canAccessChat,
                'games_completed' => (bool) $gamesCompleted,
                'chat_locked' => ! $canAccessChat,
                'contact_approval_status' => $contactApprovalStatus,
                'is_contact_approved' => (bool) $isContactApproved,
            ]),

            'match_score' => $this->when(isset($this->match_score), $this->match_score),
            'match_reasons' => $this->when(isset($this->match_reasons), $this->match_reasons),
        ];
    }

    /**
     * Delegates to `UserProfile::displayNameFor()` — the masking rule itself
     * lives on the model now, shared with every `Web\*` view, so the API and
     * the website cannot disagree about whose surname is visible.
     */
    protected function displayName(?object $viewer, object $owner): string
    {
        return $this->resource->displayNameFor($viewer);
    }

    /**
     * @param  Collection<int, ProfilePhoto>  $photos
     */
    protected function photoPayload(Collection $photos, bool $canSeePhotos): array
    {
        return $photos
            ->map(fn ($photo) => (new PhotoResource($photo))
                ->additional(['blurred' => ! $canSeePhotos])
                ->resolve())
            ->values()
            ->all();
    }

    protected function familySection(): array
    {
        return [
            'family_type' => $this->family_type,
            'family_status' => $this->family_status,
            'family_values' => $this->family_values,
            'father_occupation' => $this->father_occupation,
            'mother_occupation' => $this->mother_occupation,
            'brothers_count' => $this->brothers_count,
            'sisters_count' => $this->sisters_count,
        ];
    }
}
