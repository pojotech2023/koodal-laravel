<?php

namespace Tests\Feature\Api\V1;

use App\Models\ContactApproval;
use App\Models\Conversation;
use App\Models\KoodalGameScore;
use App\Models\MatchRecord;
use App\Models\MembershipPlan;
use App\Models\Notification;
use App\Models\User;
use App\Services\FirebaseNotificationService;
use App\Services\Games\GameChatPolicy;
use App\Services\Notification\NotificationDispatcher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\Concerns\SeedsCoreData;
use Tests\TestCase;

/**
 * The fixes made on 2026-11-03 to the 03-11 build: chat sending (the
 * SendMessage class had been overwritten), the free-member chat lock, the
 * contact-request lists, push de-duplication and the push payload.
 */
class Nov03FixesTest extends TestCase
{
    use RefreshDatabase, SeedsCoreData;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedRoles();
        $this->seedPlans();
    }

    /** Free plan as it is on the live site: chat allowed, games gate it. */
    protected function freeMember(): User
    {
        $user = $this->member();
        $plan = MembershipPlan::firstWhere('slug', 'free');

        $user->subscriptions()->create([
            'membership_plan_id' => $plan->id,
            'starts_at' => now(),
            'expires_at' => now()->addYear(),
            'status' => 'active',
            'features_snapshot' => array_merge((array) $plan->features, ['can_chat' => true]),
        ]);

        return $user->refresh();
    }

    protected function conversationBetween(User $a, User $b): Conversation
    {
        [$one, $two] = MatchRecord::pair($a->id, $b->id);
        MatchRecord::create(['user_one_id' => $one, 'user_two_id' => $two, 'matched_at' => now(), 'status' => 'active']);

        return Conversation::create(['user_one_id' => $one, 'user_two_id' => $two, 'status' => 'active']);
    }

    protected function playAllGames(User $a, User $b): void
    {
        foreach (['sync', 'find_it', 'memory_snap', 'flip'] as $game) {
            KoodalGameScore::create(['user_id' => $a->id, 'partner_id' => $b->id, 'game_type' => $game, 'score' => 5, 'max_score' => 10]);
        }
    }

    /** Switches the signed-in member; the guard caches the first one otherwise. */
    protected function signIn(User $user): void
    {
        $this->app['auth']->forgetGuards();
        $this->actingAsMember($user);
    }

    protected function send(Conversation $conversation)
    {
        return $this->postJson("/api/v1/conversations/{$conversation->id}/messages", [
            'type' => 'text',
            'body' => 'Hello',
            'client_message_id' => (string) Str::uuid(),
        ]);
    }

    // --- Chat ------------------------------------------------------------------

    public function test_a_paid_member_can_send_a_message(): void
    {
        $sender = $this->subscribe($this->member(), 'silver');
        $conversation = $this->conversationBetween($sender, $this->member());
        $this->actingAsMember($sender);

        $this->send($conversation)->assertCreated();
    }

    public function test_a_free_member_cannot_send_before_playing_all_games(): void
    {
        $sender = $this->freeMember();
        $conversation = $this->conversationBetween($sender, $this->member());
        $this->actingAsMember($sender);

        $this->send($conversation)
            ->assertStatus(403)
            ->assertJsonPath('chat_locked', true)
            ->assertJsonPath('message', 'You can access chat only after playing all the games.');
        $this->assertDatabaseCount('messages', 0);
    }

    public function test_a_free_member_can_send_once_all_games_are_played(): void
    {
        $sender = $this->freeMember();
        $partner = $this->member();
        $conversation = $this->conversationBetween($sender, $partner);
        $this->playAllGames($partner, $sender);
        $this->actingAsMember($sender);

        $this->send($conversation)->assertCreated();
    }

    public function test_the_conversation_list_reports_the_lock(): void
    {
        $sender = $this->freeMember();
        $this->conversationBetween($sender, $this->member());
        $this->actingAsMember($sender);

        $this->getJson('/api/v1/conversations')
            ->assertOk()
            ->assertJsonPath('data.0.chat_locked', true);
    }

    public function test_the_motivational_chips_pass_the_free_member_chat_filter(): void
    {
        $policy = app(GameChatPolicy::class);

        foreach ([
            'Please play quickly!', 'Oops! Hurry up!', 'Oh no! Your partner is waiting!',
            'Come on! Complete the games!', "Let's finish the games and start chatting!",
        ] as $chip) {
            $this->assertTrue($policy->isChip($chip), $chip);
        }
    }

    // --- Contact approval ------------------------------------------------------

    public function test_contact_requests_list_for_the_owner_and_approve_flow(): void
    {
        $requester = $this->freeMember();
        $owner = $this->member();

        $this->signIn($requester);
        $this->postJson('/api/v1/contact/request', ['profile_code' => $owner->profile_code])->assertCreated();

        $this->signIn($owner);
        $pending = $this->getJson("/api/v1/contact/requests/pending")->assertOk();
        $pending->assertJsonPath('data.0.status', 'pending');
        $pending->assertJsonPath('data.0.direction', 'received');
        $pending->assertJsonPath('data.0.profile.profile_code', $requester->profile_code);

        $this->postJson('/api/v1/contact/respond', [
            'request_id' => $pending->json('data.0.id'),
            'status' => 'approved',
        ])->assertOk();

        $this->signIn($requester);
        $approved = $this->getJson('/api/v1/contact/requests/approved')->assertOk();
        $approved->assertJsonPath('data.0.profile.profile_code', $owner->profile_code);
        $approved->assertJsonPath('data.0.contact.mobile', $owner->mobile);

        $this->getJson("/api/v1/profiles/{$owner->profile_code}")
            ->assertOk()
            ->assertJsonPath('data.contact.is_approved', true)
            ->assertJsonPath('data.contact.mobile', $owner->mobile);
    }

    // --- Push ------------------------------------------------------------------

    public function test_a_dispatched_notification_is_listed_once(): void
    {
        $user = $this->member();

        app(NotificationDispatcher::class)->send($user, 'game_invite', 'Invite', 'Tap to play', ['invite_id' => 9]);

        // The queue is sync in tests: SendPushNotification → PushSender →
        // FirebaseNotificationService ran above, and must not add a row.
        $this->assertSame(1, Notification::where('user_id', $user->id)->count());
    }

    public function test_a_game_invite_push_is_data_only_on_android_with_title_body_and_route(): void
    {
        $message = app(FirebaseNotificationService::class)
            ->buildCloudMessage('token', 'Asha invited you', 'Tap to accept', ['invite_id' => 9], 'game_invite')
            ->jsonSerialize();

        $this->assertArrayNotHasKey('notification', $message);
        $this->assertArrayNotHasKey('notification', $message['android'] ?? []);
        $this->assertSame('Asha invited you', $message['data']['title']);
        $this->assertSame('Tap to accept', $message['data']['body']);
        $this->assertSame('/games/invites/9', $message['data']['route']);
    }

    public function test_other_pushes_show_a_notification_without_click_action(): void
    {
        $message = app(FirebaseNotificationService::class)
            ->buildCloudMessage('token', 'Ravi', 'Hello', ['conversation_id' => 3, 'route' => '/chat/3'], 'chat_message')
            ->jsonSerialize();

        $this->assertSame('Ravi', $message['notification']['title']);
        $this->assertArrayNotHasKey('click_action', $message['android']['notification']);
        $this->assertSame('/chat/3', $message['data']['route']);
    }
}
