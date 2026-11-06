<?php

namespace Tests\Feature\Web;

use App\Models\KoodalSyncQuestion;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\PlaysGames;
use Tests\TestCase;

/**
 * The game-content admin pages sat inside the admin group, which only asks
 * for a login, and their controllers checked no permission — any member could
 * open them and add or delete game content.
 */
class GameAdminAccessTest extends TestCase
{
    use PlaysGames, RefreshDatabase;

    private const PAGES = [
        '/admin/games/questions',
        '/admin/games/find-it',
        '/admin/games/memory-snap',
        '/admin/games/vibe-stories',
        '/admin/puzzles',
        '/admin/flip-tiles',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->bootGames();
        $this->withHeaders(['Accept' => 'text/html']);
    }

    public function test_a_member_cannot_open_or_change_game_content(): void
    {
        $this->seedSyncQuestions(1);
        $question = KoodalSyncQuestion::firstOrFail();
        $this->actingAs($this->member(), 'web');

        foreach (self::PAGES as $page) {
            $this->get($page)->assertForbidden();
        }

        $this->deleteJson("/admin/games/questions/{$question->id}")->assertForbidden();
        $this->assertModelExists($question);
    }

    public function test_an_admin_still_can(): void
    {
        $admin = User::factory()->create(['role_id' => Role::firstWhere('name', 'admin')->id, 'status' => 'active']);
        $this->actingAs($admin, 'web');

        foreach (self::PAGES as $page) {
            $this->get($page)->assertOk();
        }
    }
}
