<?php

namespace Tests\Feature\Api\V1\Games;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\PlaysGames;
use Tests\TestCase;

class GameChatTest extends TestCase
{
    use PlaysGames, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->bootGames();
        $this->seedSyncQuestions();
    }

    /** @return array{0: User, 1: User, 2: string} */
    protected function room(): array
    {
        [$a, $b] = $this->pair();
        $this->actingAsMember($a);
        $code = $this->postJson("/api/v1/games/partners/{$b->profile_code}/invites", ['game_type' => 'sync'])
            ->json('data.room.code');

        return [$a, $b, $code];
    }

    public function test_free_members_may_only_send_the_quick_reply_chips(): void
    {
        [, , $code] = $this->room();
        $url = "/api/v1/games/rooms/sync/{$code}/messages";

        $this->postJson($url, ['message' => 'Wow! 🎉'])->assertOk();
        $this->postJson($url, ['message' => 'super'])->assertOk();
        $this->postJson($url, ['message' => 'Nice Play 🔥'])->assertOk();
        $this->postJson($url, ['message' => 'All the Best 💖'])->assertOk();

        $blocked = $this->postJson($url, ['message' => 'hello how are you'])->assertForbidden();
        $this->assertTrue($blocked->json('errors.upgrade_required'));
        $this->assertSame('can_chat', $blocked->json('errors.required_feature'));
    }

    public function test_a_chip_word_inside_a_longer_message_does_not_get_through(): void
    {
        [, , $code] = $this->room();

        // The website lets this through (`str_contains`); the API does not.
        $this->postJson("/api/v1/games/rooms/sync/{$code}/messages", ['message' => 'wow call me on 9876543210'])
            ->assertForbidden();
    }

    public function test_paid_members_can_send_free_text_but_not_blocked_words(): void
    {
        [$a, , $code] = $this->room();
        $this->goPaid($a);
        $url = "/api/v1/games/rooms/sync/{$code}/messages";

        $this->postJson($url, ['message' => 'Good luck to us both'])->assertOk();
        $this->postJson($url, ['message' => 'this is spam'])->assertStatus(422);
        $this->postJson($url, ['message' => '   '])->assertStatus(422);
    }

    public function test_each_player_sees_messages_from_their_own_point_of_view(): void
    {
        [$a, $b, $code] = $this->room();
        $this->postJson("/api/v1/games/rooms/sync/{$code}/messages", ['message' => 'good'])->assertOk();

        $mine = $this->getJson("/api/v1/games/rooms/sync/{$code}")->json('data.messages');
        $this->assertSame('me', end($mine)['sender']);

        $this->actAs($b);
        $theirs = $this->getJson("/api/v1/games/rooms/sync/{$code}")->json('data.messages');
        $this->assertSame('partner', end($theirs)['sender']);
        $this->assertFalse(end($theirs)['read']);

        $this->postJson("/api/v1/games/rooms/sync/{$code}/messages/read")->assertOk();
        $this->actAs($a);
        $after = $this->getJson("/api/v1/games/rooms/sync/{$code}")->json('data.messages');
        $this->assertTrue(end($after)['read']);
    }

    public function test_chat_works_the_same_in_flip_and_memory_snap(): void
    {
        [$a, $b] = $this->pair();
        $this->actingAsMember($a);

        foreach (['flip', 'memory_snap'] as $type) {
            $code = $this->postJson("/api/v1/games/partners/{$b->profile_code}/invites", ['game_type' => $type])
                ->json('data.room.code');

            $this->postJson("/api/v1/games/rooms/{$type}/{$code}/messages", ['message' => 'excellent'])->assertOk();
            $this->postJson("/api/v1/games/rooms/{$type}/{$code}/messages", ['message' => 'not a chip'])->assertForbidden();

            $messages = $this->getJson("/api/v1/games/rooms/{$type}/{$code}")->json('data.messages');
            $this->assertSame('excellent', end($messages)['text'], $type);
            $this->assertSame('me', end($messages)['sender'], $type);
        }
    }
}
