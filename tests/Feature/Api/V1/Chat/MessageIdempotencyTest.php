<?php

namespace Tests\Feature\Api\V1\Chat;

use App\Models\Conversation;
use App\Models\MatchRecord;
use App\Models\Message;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\Concerns\SeedsCoreData;
use Tests\TestCase;

/**
 * `SendMessage`'s own docblock: "Without client_message_id every flaky
 * connection produces a double message." The unique index on
 * (conversation_id, client_message_id) is the real guard; the `existing()`
 * lookup is a fast path in front of it, and the `QueryException` 1062 catch
 * is the race the fast path cannot always win. Neither had a test before.
 */
class MessageIdempotencyTest extends TestCase
{
    use RefreshDatabase, SeedsCoreData;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedRoles();
        $this->seedPlans();
    }

    /**
     * `StartConversation`'s consent gate (matches_only + mutual_interest, both
     * defaults) requires an active MatchRecord, and Free plan's `can_chat` is
     * false — so both participants are matched and subscribed.
     */
    protected function matchedPair(): array
    {
        $a = $this->member();
        $b = $this->member();
        $this->subscribe($a, 'silver');
        $this->subscribe($b, 'silver');

        [$one, $two] = MatchRecord::pair($a->id, $b->id);
        MatchRecord::create([
            'user_one_id' => $one,
            'user_two_id' => $two,
            'matched_at' => now(),
            'status' => 'active',
        ]);

        $conversation = Conversation::create([
            'user_one_id' => $one,
            'user_two_id' => $two,
            'status' => 'active',
        ]);

        return [$a, $b, $conversation];
    }

    public function test_sending_the_same_message_twice_creates_only_one_row(): void
    {
        [$sender, , $conversation] = $this->matchedPair();
        $this->actingAsMember($sender);

        $clientMessageId = (string) Str::uuid();
        $payload = ['type' => 'text', 'body' => 'Hello there', 'client_message_id' => $clientMessageId];

        $first = $this->postJson("/api/v1/conversations/{$conversation->id}/messages", $payload);
        $first->assertCreated();
        $first->assertJsonPath('data.client_message_id', $clientMessageId);

        $second = $this->postJson("/api/v1/conversations/{$conversation->id}/messages", $payload);
        $second->assertOk();
        $second->assertJsonPath('message', 'Message already sent');
        $second->assertJsonPath('data.id', $first->json('data.id'));

        $this->assertDatabaseCount('messages', 1);
        $this->assertSame('Hello there', Message::sole()->body);
    }

    public function test_a_retry_that_races_past_the_lookup_still_returns_the_original_row(): void
    {
        // Simulates the exact race SendMessage's QueryException/1062 branch
        // exists for: a row with this client_message_id is already committed
        // by the time the request reaches persist(), rather than being caught
        // by the existing() fast-path lookup (which runs regardless, but this
        // proves the unique index — not just the lookup — is what prevents
        // the duplicate).
        [$sender, , $conversation] = $this->matchedPair();
        $this->actingAsMember($sender);

        $clientMessageId = (string) Str::uuid();
        $original = Message::create([
            'conversation_id' => $conversation->id,
            'sender_id' => $sender->id,
            'type' => 'text',
            'body' => 'Already sent',
            'status' => 'sent',
            'client_message_id' => $clientMessageId,
        ]);

        $response = $this->postJson("/api/v1/conversations/{$conversation->id}/messages", [
            'type' => 'text',
            'body' => 'Already sent',
            'client_message_id' => $clientMessageId,
        ]);

        $response->assertOk();
        $response->assertJsonPath('data.id', $original->id);
        $this->assertDatabaseCount('messages', 1);
    }

    public function test_a_different_idempotency_key_creates_a_second_message(): void
    {
        [$sender, , $conversation] = $this->matchedPair();
        $this->actingAsMember($sender);

        $this->postJson("/api/v1/conversations/{$conversation->id}/messages", [
            'type' => 'text', 'body' => 'First', 'client_message_id' => (string) Str::uuid(),
        ])->assertCreated();

        $this->postJson("/api/v1/conversations/{$conversation->id}/messages", [
            'type' => 'text', 'body' => 'Second', 'client_message_id' => (string) Str::uuid(),
        ])->assertCreated();

        $this->assertDatabaseCount('messages', 2);
    }

    public function test_sending_without_an_idempotency_key_is_rejected(): void
    {
        [$sender, , $conversation] = $this->matchedPair();
        $this->actingAsMember($sender);

        $response = $this->postJson("/api/v1/conversations/{$conversation->id}/messages", [
            'type' => 'text', 'body' => 'No key at all',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('client_message_id');
        $this->assertDatabaseCount('messages', 0);
    }

    public function test_a_non_uuid_idempotency_key_is_rejected(): void
    {
        [$sender, , $conversation] = $this->matchedPair();
        $this->actingAsMember($sender);

        $response = $this->postJson("/api/v1/conversations/{$conversation->id}/messages", [
            'type' => 'text', 'body' => 'Not a uuid', 'client_message_id' => 'not-a-uuid',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('client_message_id');
    }

    /**
     * `Conversation::firstOrCreate` in StartConversation is the same pattern
     * one level up — the unique index on (user_one_id, user_two_id) is what
     * makes a repeated "start conversation" tap a lookup, not a duplicate row.
     */
    public function test_starting_the_same_conversation_twice_is_also_idempotent(): void
    {
        $a = $this->member();
        $b = $this->member();
        $this->subscribe($a, 'silver');
        $this->subscribe($b, 'silver');

        [$one, $two] = MatchRecord::pair($a->id, $b->id);
        MatchRecord::create([
            'user_one_id' => $one, 'user_two_id' => $two, 'matched_at' => now(), 'status' => 'active',
        ]);

        $this->actingAsMember($a);

        $first = $this->postJson('/api/v1/conversations', ['user_id' => $b->id]);
        $first->assertCreated();

        $second = $this->postJson('/api/v1/conversations', ['user_id' => $b->id]);
        $second->assertOk();
        $second->assertJsonPath('data.id', $first->json('data.id'));

        $this->assertDatabaseCount('conversations', 1);
    }
}
