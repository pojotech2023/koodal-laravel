<?php

namespace Tests\Feature\Api\V1;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * Clients show `message` as-is, so every failure has to read as a sentence a
 * member can act on — not "Something went wrong" or a bare status name.
 */
class ErrorMessagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_missing_or_expired_token_asks_the_member_to_log_in_again(): void
    {
        $this->getJson('/api/v1/games')
            ->assertStatus(401)
            ->assertJsonPath('message', 'Your session has expired. Please log in again.');
    }

    public function test_a_crash_says_to_try_again_without_saying_something_went_wrong(): void
    {
        Route::get('api/v1/_test/crash', fn () => throw new \RuntimeException('boom'));

        $response = $this->getJson('/api/v1/_test/crash')->assertStatus(500);

        $response->assertJsonPath('message', 'We could not complete your request right now. Please try again in a moment.');
        $this->assertNotNull($response->json('errors.reference'));
    }

    public function test_an_abort_without_a_sentence_gets_one_for_its_status(): void
    {
        Route::post('api/v1/_test/forbidden', fn () => abort(403));
        Route::post('api/v1/_test/conflict', fn () => abort(409, 'You have already sent an interest.'));
        Route::post('api/v1/_test/down', fn () => abort(503));

        $this->postJson('/api/v1/_test/forbidden')
            ->assertStatus(403)
            ->assertJsonPath('message', 'You do not have permission to do this.');
        $this->postJson('/api/v1/_test/conflict')
            ->assertStatus(409)
            ->assertJsonPath('message', 'You have already sent an interest.');
        $this->postJson('/api/v1/_test/down')
            ->assertStatus(503)
            ->assertJsonPath('message', 'Koodal is being updated. Please try again in a few minutes.');
    }
}
