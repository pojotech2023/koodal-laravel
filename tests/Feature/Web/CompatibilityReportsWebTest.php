<?php

namespace Tests\Feature\Web;

use App\Models\BlockedUser;
use App\Models\ProfilePhoto;
use App\Models\User;
use App\Services\Games\CompatibilityReports;
use App\Support\Astrology;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\PlaysGames;
use Tests\TestCase;

/**
 * Rasi Porutham and Partner Preference Match: the reports themselves (how a
 * stored raasi / star is read) and the two ways the website reaches them —
 * from a profile and from the games hub.
 */
class CompatibilityReportsWebTest extends TestCase
{
    use PlaysGames, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->bootGames();
        $this->withHeaders(['Accept' => 'text/html']);
    }

    /** @return array{0: User, 1: User} groom, bride */
    protected function couple(array $groom = [], array $bride = []): array
    {
        return [
            $this->member(['gender' => 'male'], $groom),
            $this->member(['gender' => 'female'], $bride),
        ];
    }

    protected function as(User $user): void
    {
        $this->app['auth']->forgetGuards();
        $this->actingAs($user, 'web');
    }

    protected function withPhoto(User $user, string $visibility): void
    {
        $photo = ProfilePhoto::create([
            'user_id' => $user->id,
            'path' => 'photos/'.$user->id.'.jpg',
            'mime_type' => 'image/jpeg',
            'size_bytes' => 1000,
            'approval_status' => 'approved',
        ]);
        $user->profile->update(['primary_photo_id' => $photo->id]);
        $user->privacySettings->update(['photo_visibility' => $visibility]);
    }

    // ── Reading a stored raasi / star ────────────────────────────────────

    public function test_every_spelling_resolves_to_its_own_sign_and_star(): void
    {
        // The second word matters: these used to read as Purva / Uttara Phalguni.
        $this->assertSame(19, Astrology::starIndex('Purva Ashadha'));
        $this->assertSame(20, Astrology::starIndex('Uttara Ashadha'));
        $this->assertSame(24, Astrology::starIndex('Purva Bhadrapada'));
        $this->assertSame(25, Astrology::starIndex('Uttara Bhadrapada'));

        // The app's keys, Tamil, and the website's names.
        $this->assertSame(22, Astrology::starIndex('Dhanishta'));
        $this->assertSame(22, Astrology::starIndex('அவிட்டம்'));
        $this->assertSame(1, Astrology::raasiIndex('Rishabha'));
        $this->assertSame(1, Astrology::raasiIndex('Vrishabha (Taurus)'));
        $this->assertSame(6, Astrology::raasiIndex('Thula'));
        $this->assertSame(7, Astrology::raasiIndex('Vrishchika'));
        $this->assertSame(8, Astrology::raasiIndex('Dhanu'));
        $this->assertSame(3, Astrology::raasiIndex('Kadagam / Cancer (கடகம்)'));

        $this->assertNull(Astrology::raasiIndex('Purva'));
        $this->assertNull(Astrology::starIndex(''));
    }

    public function test_the_apps_spellings_score_the_same_as_the_websites(): void
    {
        [$groomApp, $brideApp] = $this->couple(
            ['raasi' => 'Rishabha', 'star' => 'Dhanishta'],
            ['raasi' => 'Dhanu', 'star' => 'Purva Ashadha'],
        );
        [$groomWeb, $brideWeb] = $this->couple(
            ['raasi' => 'Vrishabha (Taurus)', 'star' => 'Dhanishtha'],
            ['raasi' => 'Dhanus (Sagittarius)', 'star' => 'Purva Ashadha'],
        );
        $reports = app(CompatibilityReports::class);

        $app = $reports->rasi($brideApp, $groomApp);
        $web = $reports->rasi($brideWeb, $groomWeb);

        $this->assertTrue($app['is_ready']);
        $this->assertSame($web['score'], $app['score']);
        $this->assertSame(array_column($web['poruthams'], 'matched'), array_column($app['poruthams'], 'matched'));
        $this->assertSame('அவிட்டம்', $app['groom_star_ta']);
        $this->assertSame('பூராடம்', $app['bride_star_ta']);
        $this->assertSame('ரிஷபம்', $app['groom_rasi_ta']);
    }

    public function test_a_raasi_that_cannot_be_read_counts_as_not_given(): void
    {
        [$groom, $bride] = $this->couple(
            ['raasi' => 'Something else', 'star' => 'Rohini'],
            ['raasi' => 'Mesha (Aries)', 'star' => 'Ashwini'],
        );

        $report = app(CompatibilityReports::class)->rasi($bride, $groom);

        $this->assertFalse($report['is_ready']);
        $this->assertSame('partner_missing', $report['status']);
        $this->assertNull($report['score']);
    }

    public function test_a_preferred_raasi_or_star_matches_however_either_side_spells_it(): void
    {
        [$groom, $bride] = $this->couple(
            ['raasi' => 'Rishabha', 'star' => 'Dhanishta'],
            ['raasi' => 'Kanya (Virgo)', 'star' => 'Hasta'],
        );
        $bride->preferences()->create(['raasis' => ['Vrishabha (Taurus)'], 'stars' => ['Dhanishtha']]);

        $reports = app(CompatibilityReports::class);
        $rasi = $reports->rasi($bride->fresh(), $groom);
        $this->assertTrue($rasi['pref_rasi_matched']);
        $this->assertTrue($rasi['pref_star_matched']);

        $checks = collect($reports->preference($bride->fresh(), $groom)['checks'])->keyBy('label');
        $this->assertTrue($checks['Rasi']['matched']);
        $this->assertTrue($checks['Natchathiram']['matched']);
    }

    // ── The pages ────────────────────────────────────────────────────────

    public function test_the_games_hub_reports_open_only_for_a_partner_you_may_play_with(): void
    {
        [$groom, $bride] = $this->couple();
        $this->as($bride);

        $this->get("/games/{$groom->id}/rasi")->assertOk()->assertViewIs('profile.rasi');
        $this->get("/games/{$groom->id}/preference")->assertOk()->assertViewIs('profile.preference');

        $this->get('/games/999999/rasi')->assertNotFound();

        BlockedUser::create(['user_id' => $groom->id, 'blocked_user_id' => $bride->id]);
        $this->get("/games/{$groom->id}/rasi")->assertNotFound();
        $this->get("/games/{$groom->id}/preference")->assertNotFound();
    }

    public function test_the_profile_reports_follow_profile_visibility_and_are_never_about_yourself(): void
    {
        [$groom, $bride] = $this->couple();
        $this->as($bride);

        $this->get("/profile/{$groom->profile_code}/rasi-porutham")->assertOk();
        $this->get("/profile/{$groom->profile_code}/preference")->assertOk();

        $this->get("/profile/{$bride->profile_code}/rasi-porutham")->assertNotFound();
        $this->get("/profile/{$bride->profile_code}/preference")->assertNotFound();
    }

    public function test_the_profile_reports_are_hidden_from_a_blocked_member(): void
    {
        // Its own test: ProfileVisibility keeps its answers for the life of
        // the app, which a test's requests share.
        [$groom, $bride] = $this->couple();
        BlockedUser::create(['user_id' => $groom->id, 'blocked_user_id' => $bride->id]);
        $this->as($bride);

        $this->get("/profile/{$groom->profile_code}/rasi-porutham")->assertNotFound();
        $this->get("/profile/{$groom->profile_code}/preference")->assertNotFound();
    }

    public function test_the_partners_photo_follows_their_photo_privacy_and_no_place_is_made_up(): void
    {
        [$groom, $bride] = $this->couple();
        $this->withPhoto($groom, 'on_request');
        $this->as($bride);

        foreach (["/games/{$groom->id}/rasi", "/profile/{$groom->profile_code}/preference", "/games/{$groom->id}/vibe"] as $url) {
            $page = $this->get($url)->assertOk();
            $this->assertNull($page->viewData('partnerPhotoUrl'), $url);
            $this->assertSame('', $page->viewData('partnerLocation'), $url);
            $page->assertDontSee('Coimbatore, TN')->assertDontSee('Chennai, TN');
        }

        $groom->privacySettings->update(['photo_visibility' => 'members']);
        $this->assertNotNull($this->get("/games/{$groom->id}/rasi")->viewData('partnerPhotoUrl'));
    }
}
