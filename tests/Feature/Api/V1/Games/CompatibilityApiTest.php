<?php

namespace Tests\Feature\Api\V1\Games;

use App\Models\BlockedUser;
use App\Models\GameInvite;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\PlaysGames;
use Tests\TestCase;

/**
 * What the app needs for the website's "split & show" changes: Rasi Porutham
 * and Partner Preference as reports of their own, the Vibe Meter down to its
 * game bars, offline Sync invites the website now sends as `sync_offline`,
 * and raasi / star stored under one spelling.
 */
class CompatibilityApiTest extends TestCase
{
    use PlaysGames, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->bootGames();
    }

    /** @return array{0: User, 1: User} groom, bride */
    protected function couple(): array
    {
        return [
            $this->member(['gender' => 'male', 'name' => 'Arun Kumar'], ['raasi' => 'Rishabha', 'star' => 'Dhanishta']),
            $this->member(['gender' => 'female', 'name' => 'Priya Sundar'], ['raasi' => 'Kanya (Virgo)', 'star' => 'Purva Ashadha']),
        ];
    }

    // ── Rasi Porutham / Preference ──────────────────────────────────────

    public function test_both_reports_come_from_one_call_with_the_partner_named_as_their_profile_names_them(): void
    {
        [$groom, $bride] = $this->couple();
        $this->actingAsMember($bride);

        $data = $this->getJson("/api/v1/profiles/{$groom->profile_code}/compatibility")->assertOk()->json('data');

        $rasi = $data['rasi_porutham'];
        $this->assertSame('ready', $rasi['status']);
        $this->assertTrue($rasi['is_ready']);
        $this->assertIsInt($rasi['score']);
        $this->assertCount(10, $rasi['poruthams']);
        $this->assertSame(10, $rasi['total']);
        $this->assertSame(['name_en', 'name_ta', 'description_ta', 'matched'], array_keys($rasi['poruthams'][0]));
        $this->assertSame('அவிட்டம்', $rasi['groom']['star_ta']);
        // A row saved under the app's old keys reads under the website's names.
        $this->assertSame('Vrishabha (Taurus)', $rasi['groom']['rasi']);
        $this->assertSame('Dhanishtha', $rasi['groom']['star']);
        $this->assertSame('பூராடம்', $rasi['bride']['star_ta']);

        // Not matched yet: "Arun K.", in the fields and in the story text.
        $this->assertSame('Arun K.', $rasi['groom']['name']);
        $this->assertSame('Priya Sundar', $rasi['bride']['name']);
        $this->assertStringNotContainsString('Arun Kumar', json_encode($data, JSON_UNESCAPED_UNICODE));

        $this->assertArrayHasKey('checks', $data['preference_match']);
        $this->assertIsInt($data['preference_match']['score']);
        $this->assertSame($groom->profile_code, $data['partner']['profile_code']);
    }

    public function test_a_missing_horoscope_says_whose_is_missing(): void
    {
        [$groom, $bride] = $this->couple();
        $groom->profile->update(['raasi' => null]);
        $this->actingAsMember($bride);

        $this->getJson("/api/v1/profiles/{$groom->profile_code}/compatibility")->assertOk()
            ->assertJsonPath('data.rasi_porutham.status', 'partner_missing')
            ->assertJsonPath('data.rasi_porutham.is_ready', false)
            ->assertJsonPath('data.rasi_porutham.score', null);
    }

    public function test_the_preference_report_checks_what_you_saved_under_the_websites_names(): void
    {
        [$groom, $bride] = $this->couple();
        $this->actingAsMember($bride);
        $this->putJson('/api/v1/preferences', ['age_min' => 18, 'age_max' => 60, 'raasis' => ['Rishabha'], 'stars' => ['Bharani']])->assertOk();
        $this->actAs($bride->fresh());

        $pref = $this->getJson("/api/v1/profiles/{$groom->profile_code}/compatibility")->assertOk()->json('data.preference_match');

        $this->assertSame(3, $pref['total_checks']);
        $this->assertSame(2, $pref['matched_count']);
        $checks = collect($pref['checks'])->keyBy('label');
        $this->assertSame(['label', 'expected', 'actual', 'matched'], array_keys($checks['Rasi']));
        $this->assertSame('Vrishabha (Taurus)', $checks['Rasi']['actual']);
        $this->assertTrue($checks['Rasi']['matched']);
        $this->assertSame('Dhanishtha', $checks['Natchathiram']['actual']);
        $this->assertFalse($checks['Natchathiram']['matched']);
    }

    public function test_the_reports_follow_profile_visibility_and_are_never_about_yourself(): void
    {
        [$groom, $bride] = $this->couple();
        $this->actingAsMember($bride);

        $this->getJson("/api/v1/profiles/{$bride->profile_code}/compatibility")->assertNotFound();
        $this->getJson('/api/v1/profiles/MAT2026999999/compatibility')->assertNotFound();

        $groom->privacySettings->update(['appear_in_search' => false]);
        $this->getJson("/api/v1/profiles/{$groom->profile_code}/compatibility")->assertNotFound();
    }

    public function test_a_blocked_member_gets_no_report(): void
    {
        [$groom, $bride] = $this->couple();
        BlockedUser::create(['user_id' => $groom->id, 'blocked_user_id' => $bride->id]);
        $this->actingAsMember($bride);

        $this->getJson("/api/v1/profiles/{$groom->profile_code}/compatibility")->assertNotFound();
    }

    // ── Vibe Meter ───────────────────────────────────────────────────────

    public function test_the_vibe_meter_has_the_four_game_bars_and_links_the_two_reports(): void
    {
        [$groom, $bride] = $this->couple();
        $this->actingAsMember($bride);

        $data = $this->getJson("/api/v1/games/partners/{$groom->profile_code}/vibe")->assertOk()->json('data');

        $this->assertSame(['Compatibility', 'Memory', 'Observation', 'Team Work'], array_column($data['compatibility_bars'], 'label'));
        $this->assertSame([false, false, false, false], array_column($data['compatibility_bars'], 'played'));
        $this->assertTrue($data['reports']['rasi_porutham']['is_ready']);
        $this->assertIsInt($data['reports']['rasi_porutham']['score']);
        $this->assertIsInt($data['reports']['preference_match']['score']);
        $this->assertSame(0, $data['reports']['preference_match']['total_checks']);
    }

    public function test_the_vibe_meter_leaves_the_reports_out_when_the_profile_is_not_open_to_you(): void
    {
        [$groom, $bride] = $this->couple();
        $groom->privacySettings->update(['appear_in_search' => false]);
        $this->actingAsMember($bride);

        $this->getJson("/api/v1/games/partners/{$groom->profile_code}/vibe")->assertOk()
            ->assertJsonPath('data.reports', null);
    }

    // ── Offline Sync invites ─────────────────────────────────────────────

    public function test_an_offline_sync_invite_from_the_website_reads_as_sync_and_accepts_offline(): void
    {
        [$me, $partner] = $this->pair();
        $invite = GameInvite::create([
            'sender_id' => $me->id, 'receiver_id' => $partner->id,
            'game_type' => GameInvite::SYNC_OFFLINE, 'status' => 'pending', 'expires_at' => now()->addDay(),
        ]);
        $this->actingAsMember($partner);

        $pending = $this->getJson('/api/v1/games/invites/pending')->assertOk()->json('data.incoming.0');
        $this->assertSame('sync', $pending['game_type']);
        $this->assertSame('offline', $pending['mode']);
        $this->assertTrue($pending['offline_waiting']);
        $this->assertNotNull($pending['message']);

        $hub = $this->getJson("/api/v1/games/partners/{$me->profile_code}")->json('data.games');
        $this->assertSame($invite->id, collect($hub)->firstWhere('type', 'sync')['incoming_invite']['id']);

        $accepted = $this->postJson("/api/v1/games/invites/{$invite->id}/accept")->assertOk();
        $this->assertNull($accepted->json('data.room'));
        $this->assertSame($me->profile_code, $accepted->json('data.offline_sync.partner_code'));
    }

    public function test_an_offline_invite_from_the_app_is_stored_as_the_website_stores_it_and_closes_once_both_answer(): void
    {
        $this->seedSyncQuestions();
        [$me, $partner] = $this->pair();
        $this->actingAsMember($me);

        $id = $this->postJson("/api/v1/games/partners/{$partner->profile_code}/invites", ['game_type' => 'sync', 'mode' => 'offline'])
            ->assertCreated()
            ->assertJsonPath('data.invite.game_type', 'sync')
            ->json('data.invite.id');
        $this->assertSame(GameInvite::SYNC_OFFLINE, GameInvite::find($id)->game_type);

        // The sender answers: their open offline invite is refreshed, not doubled.
        $questions = $this->getJson("/api/v1/games/partners/{$partner->profile_code}/sync")->json('data.questions');
        $answers = collect($questions)->mapWithKeys(fn ($q) => [$q['id'] => 'A'])->all();
        $this->postJson("/api/v1/games/partners/{$partner->profile_code}/sync", ['answers' => $answers])->assertCreated();
        $this->assertSame(1, GameInvite::where('sender_id', $me->id)->where('status', 'pending')->count());

        // The partner answers without opening the invite: it is done with.
        $this->actAs($partner);
        $this->postJson("/api/v1/games/partners/{$me->profile_code}/sync", ['answers' => $answers])->assertCreated();
        $this->assertSame('accepted', GameInvite::find($id)->status);
    }

    // ── One spelling for raasi / star ────────────────────────────────────

    public function test_the_apps_raasi_and_star_are_stored_and_read_under_the_websites_names(): void
    {
        $me = $this->actingAsMember();

        $this->putJson('/api/v1/profile/religious', ['raasi' => 'Rishabha', 'star' => 'Dhanishta'])->assertOk();
        $this->assertSame('Vrishabha (Taurus)', $me->profile->fresh()->raasi);
        $this->assertSame('Dhanishtha', $me->profile->fresh()->star);

        // A row the app saved before: read back under the website's name.
        $me->profile->update(['raasi' => 'Thula']);
        $this->getJson('/api/v1/profile')->assertOk()->assertJsonPath('data.religious.raasi', 'Tula (Libra)');

        $this->putJson('/api/v1/preferences', ['raasis' => ['Thula', 'Tula (Libra)', 'Meena'], 'stars' => ['Dhanishta']])->assertOk();
        $prefs = $me->preferences()->first();
        $this->assertSame(['Tula (Libra)', 'Meena (Pisces)'], $prefs->raasis);
        $this->assertSame(['Dhanishtha'], $prefs->stars);
    }
}
