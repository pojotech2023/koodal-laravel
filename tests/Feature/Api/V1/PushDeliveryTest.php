<?php

namespace Tests\Feature\Api\V1;

use App\Events\InterestAccepted;
use App\Events\InterestSent;
use App\Events\ProfileShortlisted;
use App\Jobs\SendFirebasePushJob;
use App\Models\DeviceToken;
use App\Models\Notification;
use App\Models\UserFcmToken;
use App\Services\FirebaseNotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Event;
use Tests\Concerns\PlaysGames;
use Tests\TestCase;

class PushDeliveryTest extends TestCase
{
    use PlaysGames, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->bootGames();
        $this->seedSyncQuestions();
    }

    protected function register(string $token = 'tok-1'): void
    {
        $this->postJson('/api/v1/auth/device-token', [
            'token' => $token,
            'platform' => 'android',
            'app_version' => '3.3.4',
        ])->assertOk();
    }

    public function test_a_registered_phone_is_in_both_token_tables(): void
    {
        [$a] = $this->pair();
        $this->actingAsMember($a);

        $this->register();

        $this->assertSame($a->id, DeviceToken::firstWhere('token', 'tok-1')->user_id);

        $row = UserFcmToken::firstWhere('fcm_token', 'tok-1');
        $this->assertSame($a->id, $row->user_id);
        $this->assertSame('android', $row->device_type);
        $this->assertSame('3.3.4', $row->app_version);
    }

    public function test_a_token_follows_the_member_now_using_the_phone(): void
    {
        [$a, $b] = $this->pair();

        $this->actAs($a);
        $this->register();
        $this->actAs($b);
        $this->register();

        $this->assertSame(1, DeviceToken::count());
        $this->assertSame(1, UserFcmToken::count());
        $this->assertSame($b->id, DeviceToken::first()->user_id);
        $this->assertSame($b->id, UserFcmToken::first()->user_id);
    }

    public function test_logging_out_removes_the_phone_from_both_tables(): void
    {
        [$a] = $this->pair();
        $this->actingAsMember($a);
        $this->register();

        $this->deleteJson('/api/v1/auth/device-token', ['token' => 'tok-1'])->assertSuccessful();

        $this->assertSame(0, DeviceToken::count());
        $this->assertSame(0, UserFcmToken::count());
    }

    /** From the phone's registration through to the send to Firebase. */
    public function test_a_push_reaches_the_registered_phone_once(): void
    {
        [$a] = $this->pair();
        $this->actingAsMember($a);
        $this->register();

        $this->partialMock(FirebaseNotificationService::class, function ($mock) {
            $mock->shouldReceive('sendToTokens')
                ->once()
                ->withArgs(fn (array $tokens) => array_values($tokens) === ['tok-1'])
                ->andReturn(['sent' => 1, 'failed' => 0]);
        });

        $this->assertTrue(
            app(FirebaseNotificationService::class)
                ->sendDirectToUser($a->id, 'T', 'B', [], 'interest_received', false)
        );
    }

    public function test_no_listener_is_registered_twice(): void
    {
        foreach (Event::getRawListeners() as $event => $listeners) {
            if (! str_starts_with($event, 'App\\Events\\')) {
                continue;
            }

            $names = array_map(fn ($l) => str_replace('@handle', '', $l), array_filter($listeners, 'is_string'));

            $this->assertSame(array_values(array_unique($names)), array_values($names), $event);
        }

        // And none lost along the way.
        $this->assertTrue(Event::hasListeners(InterestSent::class));
        $this->assertTrue(Event::hasListeners(InterestAccepted::class));
        $this->assertTrue(Event::hasListeners(ProfileShortlisted::class));
    }

    public function test_a_free_member_is_told_about_a_shortlist(): void
    {
        Bus::fake([SendFirebasePushJob::class]);
        [$a, $b] = $this->pair();
        $this->actingAsMember($a);

        $this->postJson('/api/v1/shortlists', ['profile_code' => $b->profile_code])->assertSuccessful();

        $this->assertSame(1, Notification::where('user_id', $b->id)->where('type', 'shortlisted')->count());
        Bus::assertDispatchedAfterResponse(
            SendFirebasePushJob::class,
            fn ($job) => $job->userId === $b->id && $job->type === 'profile_shortlisted',
        );
    }

    public function test_game_chat_pushes_without_filling_the_notification_list(): void
    {
        Bus::fake([SendFirebasePushJob::class]);
        [$a, $b] = $this->pair();
        $this->actingAsMember($a);
        $code = $this->postJson("/api/v1/games/partners/{$b->profile_code}/invites", ['game_type' => 'sync'])
            ->json('data.room.code');

        $this->postJson("/api/v1/games/rooms/sync/{$code}/messages", ['message' => 'Wow! 🎉'])->assertOk();

        Bus::assertDispatchedAfterResponse(
            SendFirebasePushJob::class,
            fn ($job) => $job->userId === $b->id
                && $job->type === 'game_chat'
                && $job->recordInApp === false
                && $job->data['room_code'] === $code,
        );
        $this->assertSame(0, Notification::where('type', 'game_chat')->count());
    }
}
