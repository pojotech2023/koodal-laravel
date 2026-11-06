<?php

namespace App\Services\Games;

use App\Models\User;
use App\Services\KoodalVibeStoryService;
use App\Services\Profile\ProfileVisibility;
use App\Support\Astrology;

/**
 * The Rasi Porutham and Partner Preference reports for a viewer and one other
 * member — the website's two pages (reached from a profile and from the games
 * hub) and the app's endpoints.
 *
 * A report is profile data (raasi, star, height, income, caste…), so it is
 * only for someone whose profile the viewer may open — being able to invite
 * them to a game is not enough — and the other member's photo shows only
 * where their photo privacy lets the viewer see it.
 */
class CompatibilityReports
{
    public function __construct(
        protected KoodalVibeStoryService $stories,
        protected GameAccess $access,
        protected ProfileVisibility $visibility,
    ) {}

    /** 404 unless the viewer may open the other member's profile (never their own). */
    public function assertCanSee(User $viewer, User $partner): void
    {
        abort_unless($this->canSee($viewer, $partner), 404, 'Member not found.');
    }

    public function canSee(User $viewer, User $partner): bool
    {
        return $viewer->id !== $partner->id && $this->visibility->canViewProfile($viewer, $partner);
    }

    public function rasi(User $viewer, User $partner): array
    {
        return $this->stories->evaluateRasiPorutham($viewer, $partner);
    }

    public function preference(User $viewer, User $partner): array
    {
        return $this->stories->evaluatePreferenceCompatibility($viewer, $partner);
    }

    /** What profile/rasi.blade.php and profile/preference.blade.php read. */
    public function pageData(User $viewer, User $partner): array
    {
        $this->assertCanSee($viewer, $partner);

        $viewer->loadMissing(['profile.primaryPhoto', 'profile.city', 'profile.state']);
        $partner->loadMissing(['profile.city', 'profile.state']);

        return [
            'viewer' => $viewer,
            'owner' => $partner,
            'user' => $viewer,
            'partner' => $partner,
            'rasiPorutham' => $this->rasi($viewer, $partner),
            'preferenceMatch' => $this->preference($viewer, $partner),
            'userPhotoUrl' => $viewer->profile?->primaryPhoto?->urlFor('thumbnail'),
            'partnerPhotoUrl' => $this->access->card($viewer, $partner)['avatar'],
            'userLocation' => $this->location($viewer),
            'partnerLocation' => $this->location($partner),
        ];
    }

    /**
     * Both reports for the app. The other member is named as their profile
     * names them to this viewer ("Priya S." until you are matched) — the
     * reports' text is built from `name`, so it is worked out on a copy that
     * carries the display name.
     */
    public function api(User $viewer, User $partner): array
    {
        $this->assertCanSee($viewer, $partner);

        $partner->loadMissing('profile');
        $partner->profile?->setRelation('user', $partner);
        $shown = clone $partner;
        $shown->name = $partner->profile?->displayNameFor($viewer) ?? $partner->name;

        $rasi = $this->rasi($viewer, $shown);
        $pref = $this->preference($viewer, $shown);
        // Under the website's names, as GET /profile reads them back.
        $side = fn (string $who) => [
            'name' => $rasi["{$who}_name"],
            'rasi' => Astrology::raasi((string) $rasi["{$who}_rasi"]) ?? $rasi["{$who}_rasi"],
            'rasi_ta' => $rasi["{$who}_rasi_ta"],
            'star' => Astrology::star((string) $rasi["{$who}_star"]) ?? $rasi["{$who}_star"],
            'star_ta' => $rasi["{$who}_star_ta"],
        ];

        return [
            'rasi_porutham' => [
                // ready | viewer_missing | partner_missing | both_missing
                'status' => $rasi['status'],
                'is_ready' => $rasi['is_ready'],
                'message' => $rasi['missing_message'],
                'score' => $rasi['score'],
                'matched_count' => $rasi['matched_count'],
                'total' => $rasi['total_poruthams'],
                'verdict_ta' => $rasi['verdict_ta'],
                'groom' => $side('groom'),
                'bride' => $side('bride'),
                'preferred_rasi_matched' => $rasi['pref_rasi_matched'],
                'preferred_star_matched' => $rasi['pref_star_matched'],
                'poruthams' => array_map(fn ($p) => [
                    'name_en' => $p['name_en'],
                    'name_ta' => $p['name_ta'],
                    'description_ta' => $p['desc'],
                    'matched' => $p['matched'],
                ], $rasi['poruthams']),
                'story' => $rasi['story_text'],
            ],
            'preference_match' => [
                'score' => $pref['score'],
                'category' => $pref['category'],
                'matched_count' => $pref['matched_cnt'],
                'total_checks' => $pref['total_checks'],
                'checks' => $pref['checks'],
                'story' => $pref['story_text'],
            ],
            'you' => $this->access->card($viewer, $viewer) + ['location' => $this->location($viewer) ?: null],
            'partner' => $this->access->card($viewer, $partner) + ['location' => $this->location($partner) ?: null],
        ];
    }

    /**
     * The two percentages for a button or a Vibe Meter link. The Rasi score is
     * null when not ready; a preference score with `total_checks` 0 checked
     * nothing the viewer asked for.
     */
    public function summary(array $rasi, array $preference): array
    {
        return [
            'rasi_porutham' => ['is_ready' => (bool) $rasi['is_ready'], 'score' => $rasi['score']],
            'preference_match' => ['score' => $preference['score'], 'total_checks' => $preference['total_checks']],
        ];
    }

    /** "City, State", or '' — never a made-up place. */
    public function location(User $user): string
    {
        return collect([$user->profile?->city?->name, $user->profile?->state?->name])->filter()->implode(', ');
    }
}
