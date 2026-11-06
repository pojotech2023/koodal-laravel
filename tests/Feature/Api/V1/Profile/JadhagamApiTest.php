<?php

namespace Tests\Feature\Api\V1\Profile;

use App\Models\Role;
use App\Models\User;
use App\Models\UserJadhagam;
use App\Models\UserProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\SeedsCoreData;
use Tests\TestCase;

/** The app's Jadhagam upload — same rules as the website's wizard step. */
class JadhagamApiTest extends TestCase
{
    use RefreshDatabase, SeedsCoreData;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedRoles();
        $this->seedPlans();
        Storage::fake('public');
    }

    public function test_upload_stores_a_pending_jadhagam(): void
    {
        $user = $this->actingAsMember();

        $response = $this->post('/api/v1/profile/jadhagams', [
            'jadhagam' => UploadedFile::fake()->image('chart.jpg', 800, 1000),
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.approval_status', 'pending')
            ->assertJsonStructure(['data' => ['id', 'url', 'approval_status', 'rejection_reason', 'created_at']]);

        $row = UserJadhagam::where('user_id', $user->id)->sole();
        Storage::disk('public')->assertExists($row->path);
    }

    public function test_upload_rejects_a_non_image(): void
    {
        $this->actingAsMember();

        $this->post('/api/v1/profile/jadhagams', [
            'jadhagam' => UploadedFile::fake()->create('chart.pdf', 100, 'application/pdf'),
        ])->assertUnprocessable()->assertJsonValidationErrors('jadhagam');

        $this->assertSame(0, UserJadhagam::count());
    }

    public function test_index_lists_only_my_uploads(): void
    {
        $me = $this->actingAsMember();
        $other = $this->member();

        $mine = UserJadhagam::create([
            'user_id' => $me->id, 'path' => 'jadhagams/a.jpg', 'disk' => 'public',
            'mime_type' => 'image/jpeg', 'size_bytes' => 10, 'approval_status' => 'rejected',
            'rejection_reason' => 'Blurry',
        ]);
        UserJadhagam::create([
            'user_id' => $other->id, 'path' => 'jadhagams/b.jpg', 'disk' => 'public',
            'mime_type' => 'image/jpeg', 'size_bytes' => 10, 'approval_status' => 'approved',
        ]);

        $this->getJson('/api/v1/profile/jadhagams')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $mine->id)
            ->assertJsonPath('data.0.rejection_reason', 'Blurry');
    }

    public function test_delete_removes_my_upload_and_its_file(): void
    {
        $me = $this->actingAsMember();
        Storage::disk('public')->put('jadhagams/a.jpg', 'x');

        $row = UserJadhagam::create([
            'user_id' => $me->id, 'path' => 'jadhagams/a.jpg', 'disk' => 'public',
            'mime_type' => 'image/jpeg', 'size_bytes' => 1, 'approval_status' => 'pending',
        ]);

        $this->deleteJson("/api/v1/profile/jadhagams/{$row->id}")->assertNoContent();

        $this->assertModelMissing($row);
        Storage::disk('public')->assertMissing('jadhagams/a.jpg');
    }

    public function test_someone_elses_upload_is_404(): void
    {
        $this->actingAsMember();
        $other = $this->member();

        $row = UserJadhagam::create([
            'user_id' => $other->id, 'path' => 'jadhagams/b.jpg', 'disk' => 'public',
            'mime_type' => 'image/jpeg', 'size_bytes' => 1, 'approval_status' => 'approved',
        ]);

        $this->deleteJson("/api/v1/profile/jadhagams/{$row->id}")->assertNotFound();

        $this->assertModelExists($row);
    }

    public function test_an_approved_jadhagam_shows_on_the_profile_for_other_members(): void
    {
        $this->seedMasterData();
        $this->actingAsMember();
        $owner = $this->ownerWithFullProfile();

        $approved = UserJadhagam::create([
            'user_id' => $owner->id, 'path' => 'jadhagams/ok.jpg', 'disk' => 'public',
            'mime_type' => 'image/jpeg', 'size_bytes' => 1, 'approval_status' => 'approved',
        ]);
        foreach (['pending', 'rejected'] as $status) {
            UserJadhagam::create([
                'user_id' => $owner->id, 'path' => "jadhagams/{$status}.jpg", 'disk' => 'public',
                'mime_type' => 'image/jpeg', 'size_bytes' => 1, 'approval_status' => $status,
            ]);
        }

        $this->getJson("/api/v1/profiles/{$owner->profile_code}")
            ->assertOk()
            ->assertJsonCount(1, 'data.jadhagams')
            ->assertJsonPath('data.jadhagams.0.id', $approved->id)
            ->assertJsonPath('data.jadhagams.0.url', Storage::disk('public')->url('jadhagams/ok.jpg'))
            // Moderation state is the owner's business, not the viewer's.
            ->assertJsonMissingPath('data.jadhagams.0.approval_status');
    }

    public function test_a_profile_without_an_approved_jadhagam_has_an_empty_list(): void
    {
        $this->seedMasterData();
        $this->actingAsMember();
        $owner = $this->ownerWithFullProfile();

        UserJadhagam::create([
            'user_id' => $owner->id, 'path' => 'jadhagams/p.jpg', 'disk' => 'public',
            'mime_type' => 'image/jpeg', 'size_bytes' => 1, 'approval_status' => 'pending',
        ]);

        $this->getJson("/api/v1/profiles/{$owner->profile_code}")
            ->assertOk()
            ->assertJsonPath('data.jadhagams', []);
    }

    /** Every lookup filled: the profile endpoint dereferences all of them. */
    protected function ownerWithFullProfile(): User
    {
        $owner = User::factory()->create([
            'role_id' => Role::firstWhere('name', 'user')?->id,
            'status' => 'active',
            'mobile_verified_at' => now(),
        ]);

        UserProfile::factory()->for($owner)->create(['approval_status' => 'approved']);
        $owner->privacySettings()->create(config('matrimony.privacy.defaults'));

        return $owner->refresh();
    }
}
