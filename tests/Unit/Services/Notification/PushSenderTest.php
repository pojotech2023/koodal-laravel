<?php

namespace Tests\Unit\Services\Notification;

use App\Services\Notification\PushSender;
use Tests\TestCase;

class PushSenderTest extends TestCase
{
    public function test_a_game_invite_is_data_only_on_android_so_the_app_can_add_buttons(): void
    {
        $message = (new PushSender)->message('🎮 Priya invited you', 'Tap to accept and play together.', [
            'type' => 'game_invite',
            'route' => '/games/invites/5',
            'invite_id' => 5,
        ])->jsonSerialize();

        $this->assertArrayNotHasKey('notification', $message);
        $this->assertSame('high', $message['android']['priority']);
        $this->assertSame('/games/invites/5', $message['data']['route']);
        $this->assertSame('5', $message['data']['invite_id']);
        $this->assertSame('🎮 Priya invited you', $message['data']['title']);
        $this->assertSame('Tap to accept and play together.', $message['data']['body']);
        // iOS still shows a normal alert.
        $this->assertSame('🎮 Priya invited you', $message['apns']['payload']['aps']['alert']['title']);
    }

    public function test_other_types_keep_the_notification_block(): void
    {
        $message = (new PushSender)->message('New interest', 'Someone likes you', [
            'type' => 'interest_received',
            'route' => '/interests',
        ])->jsonSerialize();

        $this->assertSame('New interest', $message['notification']['title']);
        $this->assertArrayNotHasKey('title', $message['data']);
    }
}
