<?php

namespace App\Http\Controllers\Web;

use App\Actions\Photo\DeleteProfilePhoto;
use App\Actions\Photo\SetPrimaryPhoto;
use App\Actions\Photo\UploadProfilePhoto;
use App\Actions\Profile\UpdateProfileSection;
use App\Http\Controllers\Controller;
use App\Http\Requests\Photo\UploadPhotoRequest;
use App\Http\Requests\Preference\UpdatePreferenceRequest;
use App\Http\Requests\Profile\UpdateAboutRequest;
use App\Http\Requests\Profile\UpdateBasicRequest;
use App\Http\Requests\Profile\UpdateEducationRequest;
use App\Http\Requests\Profile\UpdateFamilyRequest;
use App\Http\Requests\Profile\UpdateLifestyleRequest;
use App\Http\Requests\Profile\UpdateLocationRequest;
use App\Http\Requests\Profile\UpdateProfessionalRequest;
use App\Http\Requests\Profile\UpdateReligiousRequest;
use App\Models\ProfilePhoto;
use App\Models\UserProfile;
use App\Services\MasterData\MasterDataCache;
use App\Services\Profile\ProfileCompletion;
use App\Services\Verification\AadhaarOtpRequests;
use App\Services\Verification\AadhaarVerificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * "My profile" (read-only) and the completion wizard
 * (docs/05-website-guide.md §5). No `*Resource` anywhere — a Blade view has
 * no trust boundary to cross, so every method here works with `UserProfile`
 * directly (PROGRESS.md §6, "Data access — no client").
 */
class ProfileController extends Controller
{
    /** Wizard step slug → `ProfileCompletion` section keys it saves. */
    protected const STEP_SECTIONS = [
        'about-you' => ['basic'],
        'religion-location' => ['religious', 'location'],
        'education-career' => ['education', 'professional'],
        'family-lifestyle' => ['family', 'lifestyle', 'about'],
        'photos' => ['photos'],
        'jadhagam' => ['religious'],
        'aadhaar' => ['aadhaar'],
        'preferences' => ['preferences'],
    ];

    public function __construct(
        protected UpdateProfileSection $updateSection,
        protected ProfileCompletion $completion,
        protected MasterDataCache $masterData,
    ) {}

    /** GET /profile — read-only, the member's own view of their profile. */
    public function show(Request $request): View
    {
        $user = $request->user()->load(array_merge(
            ['profile', 'preferences', 'photos', 'jadhagams'],
            array_map(fn (string $relation) => 'profile.'.$relation, UserProfile::WITH_LOOKUPS),
        ));

        return view('profile.show', [
            'user' => $user,
            'completion' => $this->completion->report($user),
        ]);
    }

    /**
     * GET /profile/complete — jumps to the first incomplete step, same rule
     * as the React wizard: a member returning to finish should not have to
     * click through steps already saved. Stays on the first step if every
     * section is already complete, so the wizard doubles as an editor.
     */
    public function wizardStart(Request $request): RedirectResponse
    {
        $complete = collect($this->completion->report($request->user())['sections'])
            ->filter(fn (array $section) => $section['complete'])
            ->pluck('key');

        foreach (self::STEP_SECTIONS as $step => $sections) {
            if (array_diff($sections, $complete->all()) !== []) {
                return redirect()->route('profile.wizard.step', $step);
            }
        }

        return redirect()->route('profile.wizard.step', array_key_first(self::STEP_SECTIONS));
    }

    public function wizardStep(Request $request, string $step): View
    {
        $user = $request->user()->load(array_merge(
            ['profile', 'preferences', 'photos', 'jadhagams'],
            array_map(fn (string $relation) => 'profile.'.$relation, UserProfile::WITH_LOOKUPS),
        ));
        $profile = $user->profile;

        return view('profile.wizard', [
            'step' => $step,
            'steps' => array_keys(self::STEP_SECTIONS),
            'user' => $user,
            'profile' => $profile,
            'preferences' => $user->preferences,
            'completion' => $this->completion->report($user),
            'options' => $this->optionsFor($step, $profile),
        ]);
    }

    public function updateAboutYou(UpdateBasicRequest $request): RedirectResponse
    {
        ($this->updateSection)($request->user(), 'basic', $request->sectionData());

        return $this->nextStep('about-you');
    }

    public function updateReligionLocation(UpdateReligiousRequest $religious, UpdateLocationRequest $location): RedirectResponse
    {
        ($this->updateSection)($religious->user(), 'religious', $religious->sectionData());
        ($this->updateSection)($location->user(), 'location', $location->sectionData());

        return $this->nextStep('religion-location');
    }

    public function updateEducationCareer(UpdateEducationRequest $education, UpdateProfessionalRequest $professional): RedirectResponse
    {
        ($this->updateSection)($education->user(), 'education', $education->sectionData());
        ($this->updateSection)($professional->user(), 'professional', $professional->sectionData());

        return $this->nextStep('education-career');
    }

    public function updateFamilyLifestyle(UpdateFamilyRequest $family, UpdateLifestyleRequest $lifestyle, UpdateAboutRequest $about): RedirectResponse
    {
        ($this->updateSection)($family->user(), 'family', $family->sectionData());
        ($this->updateSection)($lifestyle->user(), 'lifestyle', $lifestyle->sectionData());
        ($this->updateSection)($about->user(), 'about', $about->sectionData());

        return $this->nextStep('family-lifestyle');
    }

    // --- Photos --------------------------------------------------------
    //
    // Not a `ProfileSectionRequest` — photos are their own resource
    // (`ProfilePhotoController`), same as the API. Every response redirects
    // back to the photos wizard step rather than returning a view, matching
    // the plain-`<form>`-first rule used for chat (guide §7): no Alpine
    // required for the upload/delete/set-primary loop to work.

    public function uploadPhoto(UploadPhotoRequest $request, UploadProfilePhoto $upload): RedirectResponse
    {
        $upload($request->user(), $request->file('photo'));

        $this->completion->recalculate($request->user());

        return redirect()->route('profile.wizard.step', 'photos')
            ->with('status', config('matrimony.profile.photo_moderation_required')
                ? 'Photo uploaded. It will appear once approved.'
                : 'Photo uploaded successfully.');
    }

    public function setPrimaryPhoto(Request $request, ProfilePhoto $photo, SetPrimaryPhoto $setPrimary): RedirectResponse
    {
        $setPrimary($request->user(), $photo);

        return redirect()->route('profile.wizard.step', 'photos')->with('status', 'Primary photo updated.');
    }

    public function destroyPhoto(Request $request, ProfilePhoto $photo, DeleteProfilePhoto $delete): RedirectResponse
    {
        $delete($request->user(), $photo);

        $this->completion->recalculate($request->user());

        return redirect()->route('profile.wizard.step', 'photos')->with('status', 'Photo removed.');
    }

    // --- Jadhagam ------------------------------------------------------

    public function uploadJadhagam(Request $request): RedirectResponse
    {
        $request->validate([
            'jadhagam' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);

        $file = $request->file('jadhagam');
        $path = $file->store('jadhagams', 'public');

        \App\Models\UserJadhagam::create([
            'user_id' => $request->user()->id,
            'path' => $path,
            'disk' => 'public',
            'mime_type' => $file->getClientMimeType(),
            'size_bytes' => $file->getSize(),
            'approval_status' => 'pending',
        ]);

        return redirect()->route('profile.wizard.step', 'jadhagam')
            ->with('status', 'Jadhagam uploaded successfully! It will appear on your profile once approved by admin.');
    }

    public function destroyJadhagam(Request $request, \App\Models\UserJadhagam $jadhagam): RedirectResponse
    {
        abort_unless($jadhagam->user_id === $request->user()->id, 403);

        if (\Illuminate\Support\Facades\Storage::disk($jadhagam->disk)->exists($jadhagam->path)) {
            \Illuminate\Support\Facades\Storage::disk($jadhagam->disk)->delete($jadhagam->path);
        }

        $jadhagam->delete();

        return redirect()->route('profile.wizard.step', 'jadhagam')->with('status', 'Jadhagam removed.');
    }

    // --- Aadhaar Verification ------------------------------------------

    /**
     * Same rules as the app's `Api\V1\User\VerificationController`, and the
     * same pending request (one per member, see AadhaarOtpRequests).
     */
    public function generateAadhaarOtp(Request $request, AadhaarVerificationService $service, AadhaarOtpRequests $requests): JsonResponse
    {
        $validated = $request->validate([
            'id_number' => ['required', 'string'],
        ]);

        $cleanAadhaar = preg_replace('/\D/', '', $validated['id_number']);

        if (strlen($cleanAadhaar) !== 12) {
            return response()->json([
                'success' => false,
                'message' => 'Please enter a valid 12-digit Aadhaar number.',
            ], 422);
        }

        if ($this->aadhaarLinkedElsewhere($cleanAadhaar, $request->user()->id)) {
            return response()->json(['success' => false, 'message' => AadhaarOtpRequests::LINKED], 422);
        }

        $result = $service->generateOtp($cleanAadhaar);

        if (! $result['success']) {
            return response()->json([
                'success' => false,
                'message' => $result['message'],
            ], 400);
        }

        $requestId = (string) $result['request_id'];
        $requests->remember(AadhaarOtpRequests::memberKey($request->user()->id), $requestId, $cleanAadhaar);

        return response()->json([
            'success' => true,
            'request_id' => $requestId,
            'message' => $result['message'],
        ]);
    }

    /**
     * Only for the request this member started with generateAadhaarOtp(), and
     * only for the number that request was for. `aadhaar_number` is still
     * sent by the page, and must match.
     */
    public function verifyAadhaarOtp(Request $request, AadhaarOtpRequests $requests): JsonResponse
    {
        $validated = $request->validate([
            'request_id' => ['required', 'string'],
            'otp' => ['required', 'string', 'min:6', 'max:6'],
            'aadhaar_number' => ['nullable', 'string'],
        ]);

        $user = $request->user();
        $key = AadhaarOtpRequests::memberKey($user->id);
        $pending = $requests->find($key, (string) $validated['request_id']);

        if (! $pending) {
            return response()->json(['success' => false, 'message' => AadhaarOtpRequests::EXPIRED], 422);
        }

        if (! $requests->claims($pending, $validated['aadhaar_number'] ?? null)) {
            return response()->json(['success' => false, 'message' => AadhaarOtpRequests::OTHER_NUMBER], 422);
        }

        $cleanAadhaar = $pending['aadhaar'];

        // Checked again here: another account may have verified it since.
        if ($this->aadhaarLinkedElsewhere($cleanAadhaar, $user->id)) {
            $requests->forget($key);

            return response()->json(['success' => false, 'message' => AadhaarOtpRequests::LINKED], 422);
        }

        $result = $requests->submit($key, $pending, $validated['otp']);

        if (! $result['success']) {
            return response()->json([
                'success' => false,
                'message' => $result['message'],
            ], $result['status']);
        }

        $user->update([
            'aadhaar_number' => $cleanAadhaar,
            'is_aadhaar_verified' => true,
            'aadhaar_verified_at' => now(),
            'status' => 'active',
            'mobile_verified_at' => $user->mobile_verified_at ?? now(),
        ]);

        $user->profile?->update([
            'is_verified' => true,
            'verified_at' => now(),
        ]);

        $percentage = $this->completion->recalculate($user);

        return response()->json([
            'success' => true,
            'message' => 'Aadhaar verified successfully! ✓',
            'completion_percentage' => $percentage,
            'aadhaar_masked' => 'XXXX XXXX ' . substr($cleanAadhaar, -4),
            'verified_at' => now()->format('d M Y, h:i A'),
            'full_name' => $result['data']['full_name'] ?? $user->name,
        ]);
    }

    protected function aadhaarLinkedElsewhere(string $aadhaar, int $userId): bool
    {
        return \App\Models\User::where('aadhaar_number', $aadhaar)->where('id', '!=', $userId)->exists();
    }

    // --- Partner Preferences (Step 8) ----------------------------------

    public function updatePreferences(UpdatePreferenceRequest $request): RedirectResponse
    {
        $user = $request->user();
        $validated = $request->validated();

        $payload = [
            'age_min' => $validated['age_min'] ?? null,
            'age_max' => $validated['age_max'] ?? null,
            'height_min_cm' => $validated['height_min_cm'] ?? null,
            'height_max_cm' => $validated['height_max_cm'] ?? null,
            'weight_min_kg' => $validated['weight_min_kg'] ?? null,
            'weight_max_kg' => $validated['weight_max_kg'] ?? null,
            'marital_status_ids' => array_values(array_map('intval', $validated['marital_status_ids'] ?? [])),
            'occupation_ids' => array_values(array_map('intval', $validated['occupation_ids'] ?? [])),
            'income_range_ids' => array_values(array_map('intval', $validated['income_range_ids'] ?? [])),
            'complexions' => array_values($validated['complexions'] ?? []),
            'mother_tongue_ids' => array_values(array_map('intval', $validated['mother_tongue_ids'] ?? [])),
            'state_ids' => array_values(array_map('intval', $validated['state_ids'] ?? [])),
            'raasis' => array_values($validated['raasis'] ?? []),
            'stars' => array_values($validated['stars'] ?? []),
            'castes' => array_values($validated['castes'] ?? []),
        ];

        $user->preferences()->updateOrCreate(
            ['user_id' => $user->id],
            $payload
        );

        $user->unsetRelation('preferences');
        $this->completion->recalculate($user);

        return redirect()->route('matches.reference')
            ->with('status', 'Partner preferences saved successfully! Here are the profiles matching your preferences.');
    }

    protected function nextStep(string $current): RedirectResponse
    {
        $steps = array_keys(self::STEP_SECTIONS);
        $next = $steps[array_search($current, $steps, true) + 1] ?? $steps[0];

        return redirect()->route('profile.wizard.step', $next)->with('status', 'Saved.');
    }

    /**
     * Options each step's `<select>`s need. Cascades (community/state/city)
     * are filtered by the *currently saved or just-submitted* parent value
     * (`old()` falling back to the profile) rather than fetched by Alpine on
     * change — the plain-form-first rule again: picking a new religion and
     * submitting reloads the step with the right communities, no JS required.
     * A future session can layer an Alpine fetch on top for instant cascading
     * without changing this contract. See PROGRESS.md §6.
     */
    protected function optionsFor(string $step, ?UserProfile $profile): array
    {
        return match ($step) {
            'about-you' => [
                'marital_statuses' => $this->masterData->list('marital-statuses'),
                'body_types' => $this->masterData->list('lifestyle-options', ['type' => 'body_type']),
                'complexions' => $this->masterData->list('lifestyle-options', ['type' => 'complexion']),
            ],
            'religion-location' => [
                'religions' => $this->masterData->list('religions'),
                'communities' => ($religionId = old('religion_id', $profile?->religion_id))
                    ? $this->masterData->list('communities', ['religion_id' => $religionId]) : [],
                'sub_communities' => ($communityId = old('community_id', $profile?->community_id))
                    ? $this->masterData->list('sub-communities', ['community_id' => $communityId]) : [],
                'mother_tongues' => $this->masterData->list('mother-tongues'),
                'castes' => $this->masterData->list('castes'),
                'gothras' => $this->masterData->list('gothras'),
                'countries' => $this->masterData->countries(),
                'states' => ($countryId = old('country_id', $profile?->country_id))
                    ? $this->masterData->states($countryId) : [],
                'cities' => ($stateId = old('state_id', $profile?->state_id))
                    ? $this->masterData->cities($stateId) : [],
            ],
            'education-career' => [
                'educations' => $this->masterData->list('educations'),
                'occupations' => $this->masterData->list('occupations'),
                'income_ranges' => $this->masterData->list('income-ranges'),
            ],
            'family-lifestyle' => [
                'diets' => $this->masterData->list('lifestyle-options', ['type' => 'diet']),
                'smoking' => $this->masterData->list('lifestyle-options', ['type' => 'smoking']),
                'drinking' => $this->masterData->list('lifestyle-options', ['type' => 'drinking']),
            ],
            'preferences' => [
                'marital_statuses' => $this->masterData->list('marital-statuses'),
                'occupations' => $this->masterData->list('occupations'),
                'income_ranges' => $this->masterData->list('income-ranges'),
                'complexions' => $this->masterData->list('lifestyle-options', ['type' => 'complexion']),
                'mother_tongues' => $this->masterData->list('mother-tongues'),
                'states' => $this->masterData->states(null),
                'raasis' => collect(config('matrimony.astrology.raasis'))->map(fn ($name) => ['id' => $name, 'name' => $name])->all(),
                'stars' => collect(config('matrimony.astrology.nakshatras'))->map(fn ($name) => ['id' => $name, 'name' => $name])->all(),
                'castes' => $this->masterData->list('castes'),
            ],
            default => [],
        };
    }
}
