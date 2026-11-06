<?php

namespace Tests\Feature\Web;

use App\Models\Conversation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\SeedsCoreData;
use Tests\TestCase;

/**
 * Answers the question PROGRESS.md §6 left open without a real PHP
 * environment: does `broadcasting.auth` (bootstrap/app.php's
 * ->withBroadcasting() call) actually authenticate a `web`-guard session user
 * and enforce routes/channels.php's per-channel authorization, the same way
 * the Sanctum-guarded API twin does. It did not — see bootstrap/app.php's
 * comment for the bug this suite caught.
 *
 * phpunit.xml sets BROADCAST_CONNECTION=log for the rest of the suite; this
 * class overrides it back to `reverb` because the Pusher-protocol auth
 * response shape (`{"auth": "key:signature"}`) is what's actually being
 * verified here.
 */
class BroadcastingAuthTest extends TestCase
{
    use RefreshDatabase;
    use SeedsCoreData;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedRoles();

        // phpunit.xml pins BROADCAST_CONNECTION=log for the rest of the suite
        // (deterministic, no live socket server). routes/channels.php's
        // Broadcast::channel() calls ran once at boot against *that* default
        // connection's Broadcaster instance; switching the default here would
        // resolve a brand new, never-registered `reverb` instance with an
        // empty channel map. Re-requiring channels.php re-registers the same
        // definitions against the freshly-selected `reverb` connection,
        // mirroring what boot does in an environment where reverb was the
        // default from the start.
        config(['broadcasting.default' => 'reverb']);
        require base_path('routes/channels.php');
    }

    public function test_a_guest_gets_a_401_rather_than_being_authenticated(): void
    {
        // TestCase sets Accept: application/json globally, so the `auth:web`
        // redirect-to-login behaviour (real browser requests) is bypassed in
        // favour of Authenticate::unauthenticated()'s JSON branch — matching
        // what the actual JS client (fetch, not a form submit) would see too.
        $response = $this->postJson('/broadcasting/auth', [
            'channel_name' => 'private-conversation.1',
            'socket_id' => '1234.5678',
        ]);

        $response->assertStatus(401);
    }

    public function test_a_logged_in_member_can_authenticate_for_a_conversation_they_belong_to(): void
    {
        $user = $this->member();
        $other = $this->member();

        $conversation = Conversation::create([
            'user_one_id' => min($user->id, $other->id),
            'user_two_id' => max($user->id, $other->id),
            'status' => 'active',
        ]);

        $response = $this->actingAs($user, 'web')->post('/broadcasting/auth', [
            'channel_name' => "private-conversation.{$conversation->id}",
            'socket_id' => '1234.5678',
        ]);

        $response->assertOk();
        $response->assertJsonStructure(['auth']);
    }

    public function test_a_member_cannot_authenticate_for_a_conversation_they_are_not_part_of(): void
    {
        $user = $this->member();
        $strangerA = $this->member();
        $strangerB = $this->member();

        $conversation = Conversation::create([
            'user_one_id' => min($strangerA->id, $strangerB->id),
            'user_two_id' => max($strangerA->id, $strangerB->id),
            'status' => 'active',
        ]);

        $response = $this->actingAs($user, 'web')->post('/broadcasting/auth', [
            'channel_name' => "private-conversation.{$conversation->id}",
            'socket_id' => '1234.5678',
        ]);

        $response->assertForbidden();
    }

    public function test_a_member_can_authenticate_their_own_private_user_channel(): void
    {
        $user = $this->member();

        $response = $this->actingAs($user, 'web')->post('/broadcasting/auth', [
            'channel_name' => "private-user.{$user->id}",
            'socket_id' => '1234.5678',
        ]);

        $response->assertOk();
        $response->assertJsonStructure(['auth']);
    }

    public function test_a_member_cannot_authenticate_another_users_private_channel(): void
    {
        $user = $this->member();
        $other = $this->member();

        $response = $this->actingAs($user, 'web')->post('/broadcasting/auth', [
            'channel_name' => "private-user.{$other->id}",
            'socket_id' => '1234.5678',
        ]);

        $response->assertForbidden();
    }

    public function test_a_suspended_member_is_bounced_before_reaching_the_broadcast_route(): void
    {
        $user = $this->member(['status' => 'suspended']);

        $response = $this->actingAs($user, 'web')->post('/broadcasting/auth', [
            'channel_name' => "private-user.{$user->id}",
            'socket_id' => '1234.5678',
        ]);

        // EnsureActiveWebUser (`active.web`) intercepts before the controller.
        $response->assertRedirect();
        $this->assertNotSame(200, $response->getStatusCode());
    }
}
