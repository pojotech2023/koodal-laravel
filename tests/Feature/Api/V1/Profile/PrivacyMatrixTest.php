<?php

namespace Tests\Feature\Api\V1\Profile;

use App\Models\MatchRecord;
use App\Models\ProfilePhoto;
use App\Models\Role;
use App\Models\User;
use App\Models\UserProfile;
use App\Services\Membership\FeatureGate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\SeedsCoreData;
use Tests\TestCase;

/**
 * The stated invariant behind `ProfileVisibility` and `ProfileResource`: a
 * profile hidden from, or blocking, the viewer answers 404 — never 403 — and
 * a gated field is absent from the payload rather than present-and-null.
 * Neither had a test before this file.
 */
class PrivacyMatrixTest extends TestCase
{
    use RefreshDatabase, SeedsCoreData;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedRoles();
        $this->seedPlans();
        $this->seedMasterData();
    }

    /**
     * Unlike TestCase::member(), this hands back a profile with every
     * lookup field populated — PublicProfileController eager-loads all of
     * UserProfile::WITH_LOOKUPS and ProfileResource dereferences each one
     * unconditionally once the relation is loaded, so an owner used only as
     * a viewer (via TestCase::member()) is not good enough here.
     */
    protected function ownerMember(array $userAttrs = [], array $profileAttrs = [], array $privacyAttrs = []): User
    {
        $owner = User::factory()->create(array_merge([
            'role_id' => Role::firstWhere('name', 'user')?->id,
            'status' => 'active',
            'mobile_verified_at' => now(),
        ], $userAttrs));

        UserProfile::factory()->for($owner)->create(array_merge(
            ['approval_status' => 'approved'],
            $profileAttrs,
        ));

        $owner->privacySettings()->create(array_merge(
            config('matrimony.privacy.defaults'),
            $privacyAttrs,
        ));

        return $owner->refresh();
    }

    protected function match(User $a, User $b): void
    {
        [$one, $two] = $a->id < $b->id ? [$a->id, $b->id] : [$b->id, $a->id];

        MatchRecord::create([
            'user_one_id' => $one,
            'user_two_id' => $two,
            'matched_at' => now(),
            'status' => 'active',
        ]);
    }

    // --- Existence and discoverability --------------------------------------

    public function test_a_profile_code_that_does_not_exist_returns_404(): void
    {
        $this->actingAsMember();

        $response = $this->getJson('/api/v1/profiles/MAT2026999999');

        $response->assertStatus(404);
        $response->assertJsonPath('message', 'Not found.');
    }

    public function test_a_suspended_owner_is_invisible(): void
    {
        $this->actingAsMember();
        $owner = $this->ownerMember(['status' => 'suspended']);

        $this->getJson("/api/v1/profiles/{$owner->profile_code}")->assertStatus(404);
    }

    public function test_a_profile_still_pending_approval_is_invisible(): void
    {
        $this->actingAsMember();
        $owner = $this->ownerMember([], ['approval_status' => 'pending']);

        $this->getJson("/api/v1/profiles/{$owner->profile_code}")->assertStatus(404);
    }

    public function test_an_owner_who_opted_out_of_search_is_invisible_even_with_open_visibility(): void
    {
        $this->actingAsMember();
        $owner = $this->ownerMember([], [], [
            'profile_visibility' => 'all',
            'appear_in_search' => false,
        ]);

        $this->getJson("/api/v1/profiles/{$owner->profile_code}")->assertStatus(404);
    }

    // --- Blocking, both directions, and non-enumerability -------------------

    public function test_a_profile_the_viewer_has_blocked_is_invisible(): void
    {
        $viewer = $this->actingAsMember();
        $owner = $this->ownerMember([], [], ['profile_visibility' => 'all']);

        $viewer->blocks()->create(['blocked_user_id' => $owner->id]);

        $this->getJson("/api/v1/profiles/{$owner->profile_code}")->assertStatus(404);
    }

    public function test_a_profile_that_has_blocked_the_viewer_is_invisible(): void
    {
        $viewer = $this->actingAsMember();
        $owner = $this->ownerMember([], [], ['profile_visibility' => 'all']);

        $owner->blocks()->create(['blocked_user_id' => $viewer->id]);

        $this->getJson("/api/v1/profiles/{$owner->profile_code}")->assertStatus(404);
    }

    public function test_a_blocked_profile_and_a_nonexistent_one_answer_identically(): void
    {
        $viewer = $this->actingAsMember();
        $owner = $this->ownerMember([], [], ['profile_visibility' => 'all']);
        $viewer->blocks()->create(['blocked_user_id' => $owner->id]);

        $blocked = $this->getJson("/api/v1/profiles/{$owner->profile_code}");
        $missing = $this->getJson('/api/v1/profiles/MAT2026999999');

        $blocked->assertStatus(404);
        $missing->assertStatus(404);
        $this->assertSame($missing->json(), $blocked->json());
    }

    // --- Audience gating by profile_visibility ------------------------------

    public function test_members_visibility_is_visible_to_any_authenticated_member(): void
    {
        $this->actingAsMember();
        $owner = $this->ownerMember([], [], ['profile_visibility' => 'members']);

        $response = $this->getJson("/api/v1/profiles/{$owner->profile_code}");

        $this->assertEnvelope($response);
        $response->assertJsonPath('data.profile_code', $owner->profile_code);
    }

    public function test_premium_visibility_hides_from_a_free_plan_viewer(): void
    {
        $this->actingAsMember();
        $owner = $this->ownerMember([], [], ['profile_visibility' => 'premium']);

        $this->getJson("/api/v1/profiles/{$owner->profile_code}")->assertStatus(404);
    }

    public function test_premium_visibility_is_visible_to_a_subscribed_viewer(): void
    {
        $viewer = $this->actingAsMember();
        $this->subscribe($viewer, 'gold');
        $owner = $this->ownerMember([], [], ['profile_visibility' => 'premium']);

        $this->getJson("/api/v1/profiles/{$owner->profile_code}")->assertOk();
    }

    public function test_matches_only_visibility_hides_from_an_unconnected_viewer(): void
    {
        $this->actingAsMember();
        $owner = $this->ownerMember([], [], ['profile_visibility' => 'matches_only']);

        $this->getJson("/api/v1/profiles/{$owner->profile_code}")->assertStatus(404);
    }

    public function test_matches_only_visibility_is_visible_once_matched(): void
    {
        $viewer = $this->actingAsMember();
        $owner = $this->ownerMember([], [], ['profile_visibility' => 'matches_only']);
        $this->match($viewer, $owner);

        $this->getJson("/api/v1/profiles/{$owner->profile_code}")->assertOk();
    }

    // --- Gated field absence: contact ---------------------------------------

    // Since the 03-11 build a free viewer sees the contact *masked* until the
    // owner approves a contact request (ContactApproval); a paid viewer needs
    // no approval.
    public function test_contact_is_masked_for_a_free_plan_viewer_until_the_owner_approves(): void
    {
        $viewer = $this->actingAsMember();
        $owner = $this->ownerMember([], [], [
            'profile_visibility' => 'members',
            'contact_visibility' => 'members',
        ]);

        $response = $this->getJson("/api/v1/profiles/{$owner->profile_code}");

        $response->assertOk();
        $response->assertJsonPath('data.contact.is_approved', false);
        $response->assertJsonPath('data.contact.approval_status', 'none');
        $this->assertNotSame($owner->mobile, $response->json('data.contact.mobile'));

        \App\Models\ContactApproval::create(['requester_id' => $viewer->id, 'owner_id' => $owner->id, 'status' => 'approved']);

        $this->getJson("/api/v1/profiles/{$owner->profile_code}")
            ->assertJsonPath('data.contact.is_approved', true)
            ->assertJsonPath('data.contact.mobile', $owner->mobile);
    }

    public function test_contact_is_present_for_a_subscribed_viewer_with_quota_remaining(): void
    {
        $viewer = $this->actingAsMember();
        $this->subscribe($viewer, 'gold');
        $owner = $this->ownerMember([], [], [
            'profile_visibility' => 'members',
            'contact_visibility' => 'members',
        ]);

        $response = $this->getJson("/api/v1/profiles/{$owner->profile_code}");

        $response->assertOk();
        $response->assertJsonPath('data.contact.mobile', $owner->mobile);
        $response->assertJsonPath('data.contact.email', $owner->email);
    }

    public function test_contact_is_absent_once_the_monthly_quota_is_exhausted(): void
    {
        $viewer = $this->actingAsMember();
        $this->subscribe($viewer, 'gold');
        $owner = $this->ownerMember([], [], [
            'profile_visibility' => 'members',
            'contact_visibility' => 'members',
        ]);

        // Gold's snapshot allows 50 contact views/month; spend all of them
        // before ever calling the reveal endpoint.
        app(FeatureGate::class)->consume($viewer, 'contact_views_per_month', 50);

        $response = $this->getJson("/api/v1/profiles/{$owner->profile_code}");

        $response->assertOk();
        $this->assertNotSame($owner->mobile, $response->json('data.contact.mobile'));
    }

    // --- Gated field substitution: photos ------------------------------------

    public function test_on_request_photos_are_served_blurred_to_an_unconnected_viewer(): void
    {
        $this->actingAsMember();
        $owner = $this->ownerMember([], [], [
            'profile_visibility' => 'members',
            'photo_visibility' => 'on_request',
        ]);
        ProfilePhoto::create([
            'user_id' => $owner->id,
            'path' => 'photos/owner.jpg',
            'mime_type' => 'image/jpeg',
            'size_bytes' => 1000,
            'approval_status' => 'approved',
        ]);

        $response = $this->getJson("/api/v1/profiles/{$owner->profile_code}");

        $response->assertOk();
        $response->assertJsonPath('data.photos.0.blurred', true);
    }

    public function test_on_request_photos_are_unblurred_once_matched(): void
    {
        $viewer = $this->actingAsMember();
        $owner = $this->ownerMember([], [], [
            'profile_visibility' => 'members',
            'photo_visibility' => 'on_request',
        ]);
        $this->match($viewer, $owner);
        ProfilePhoto::create([
            'user_id' => $owner->id,
            'path' => 'photos/owner.jpg',
            'mime_type' => 'image/jpeg',
            'size_bytes' => 1000,
            'approval_status' => 'approved',
        ]);

        $response = $this->getJson("/api/v1/profiles/{$owner->profile_code}");

        $response->assertOk();
        $response->assertJsonPath('data.photos.0.blurred', false);
    }

    // --- The /contact endpoint: 404 for hidden, 403 for a real paywall ------

    public function test_the_contact_endpoint_answers_404_for_a_hidden_profile_not_403(): void
    {
        $viewer = $this->actingAsMember();
        $owner = $this->ownerMember([], [], ['profile_visibility' => 'all']);
        $viewer->blocks()->create(['blocked_user_id' => $owner->id]);

        $this->getJson("/api/v1/profiles/{$owner->profile_code}/contact")->assertStatus(404);
    }

    public function test_the_contact_endpoint_answers_403_approval_required_for_a_free_viewer_without_approval(): void
    {
        // A free-plan viewer: visible profile (audience is satisfied), but no
        // approved ContactApproval. This must stay 403, distinct from the 404
        // the privacy layer uses for hidden profiles.
        $this->actingAsMember();
        $owner = $this->ownerMember([], [], [
            'profile_visibility' => 'members',
            'contact_visibility' => 'members',
        ]);

        $response = $this->getJson("/api/v1/profiles/{$owner->profile_code}/contact");

        $response->assertStatus(403);
        $response->assertJsonPath('approval_required', true);
        $response->assertJsonPath('approval_status', 'none');
    }
}
