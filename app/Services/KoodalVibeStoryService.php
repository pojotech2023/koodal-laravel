<?php

namespace App\Services;

use App\Models\FlipGame;
use App\Models\GameRoom;
use App\Models\KoodalGameScore;
use App\Models\KoodalSyncAnswer;
use App\Models\KoodalSyncQuestion;
use App\Models\KoodalVibeResult;
use App\Models\KoodalVibeStory;
use App\Models\MemorySnapGame;
use App\Models\User;
use App\Support\Astrology;

class KoodalVibeStoryService
{
    /**
     * Generate personalized Vibe Story for a couple based on their real game scores
     * and real Compatibility Test (Koodal Sync) questions & answers.
     */
    public function generateStory(User $user, User $partner, ?KoodalVibeResult $vibeResult = null): array
    {
        // 1. Identify Groom and Bride
        $groom = $user;
        $bride = $partner;

        if ($user->gender === 'female' || $partner->gender === 'male') {
            $groom = $partner;
            $bride = $user;
        }

        $groomName = $groom->name;
        $brideName = $bride->name;

        // 2. Overall Vibe Score
        $score = $vibeResult ? (int)$vibeResult->vibe_percentage : 0;

        // Ensure default Admin Panel Vibe Story templates are seeded & legacy hardcoded stories upgraded
        self::ensureDefaultTemplatesSeeded();

        // 3. Find matching story template from database (supports multiple stories per range)
        $matchingStories = KoodalVibeStory::active()
            ->where('min_score', '<=', $score)
            ->where('max_score', '>=', $score)
            ->orderBy('sort_order', 'asc')
            ->get();

        if ($matchingStories->isNotEmpty()) {
            // Deterministic selection per couple + score so it's consistent on refresh
            $idx = ($user->id + $partner->id + $score) % $matchingStories->count();
            $storyRecord = $matchingStories->values()->get($idx);
        } else {
            $storyRecord = KoodalVibeStory::active()->orderBy('min_score', 'desc')->first();
        }

        $defaultTemplate = "சில connections சின்ன சின்ன விஷயங்கள்லதான் ஆரம்பிக்கும்… ஒரே மாதிரியான choices, பிடிச்ச interests, ஒருத்தரை ஒருத்தர் புரிஞ்சுக்கிற அந்த அழகான feeling! ❤️\n\n{rasi_porutham_detail}\n\n{preference_detail}\n\n{compatibility_detail}\n\n{observation_detail}\n{memory_detail}\n\n{teamwork_detail}\n\n{favourite_scene}\n\n**உங்க Vibe Meter: {score}% — {category_name}!**\n\n{groom_name} & {bride_name}, உங்க Koodal Story ஒரு அழகான beginning-ல இருக்கு. இன்னும் பேசிப் பழகினா, ஒருத்தரை ஒருத்தர் புரிஞ்சுக்க இன்னும் நிறைய விஷயங்கள் கிடைக்கலாம்.";

        $template = $storyRecord ? $storyRecord->story_content : $defaultTemplate;
        $title = $storyRecord ? $storyRecord->title : "{groom_name} ❤️ {bride_name}";
        $tagline = $storyRecord ? $storyRecord->tagline : "Your Koodal Vibe: {score}%";
        $categoryName = $storyRecord ? $storyRecord->category_name : "Strong Connection";
        $cta = $storyRecord ? $storyRecord->call_to_action : "❤️ PREMIUM CALL";
        $ctaUrl = $storyRecord && $storyRecord->cta_url ? $storyRecord->cta_url : route('membership.index');

        // 4. Evaluate 10 Porutham (Josiyar Jothida Calculation + Preference Rasi/Natchathiram)
        $rasiEval = $this->evaluateRasiPorutham($user, $partner);
        $rasiPoruthamDetail = $rasiEval['story_text'];

        // 5. Evaluate Partner Preference Match & Story ("Ananya எதிர்பார்த்த மாதிரியே நீங்க இந்த fit-ல இருக்கீங்க, இந்த color-ல இருக்கீங்க...")
        $prefEval = $this->evaluatePreferenceCompatibility($user, $partner);
        $preferenceDetail = $prefEval['story_text'];

        if ($score <= 0) {
            $score = (int) round(($prefEval['score'] + $rasiEval['score']) / 2);
        }

        // 6. Extract REAL Compatibility Test (Koodal Sync) Questions & Chosen Answers
        $syncQA = $this->extractCompatibilityQA($groom, $bride, $user);
        $matchedQA = $syncQA['matched'];
        $differentQA = $syncQA['different'];
        $allQA = $syncQA['all'];

        // Build {compatibility_detail} from real answers
        $compatibilityDetail = $this->buildCompatibilityDetailText($groomName, $brideName, $matchedQA, $differentQA, $allQA);

        // 7. Resolve accurate Observation details (Find It game — out of 7 differences)
        $observationDetail = $this->buildObservationDetailText($groom, $bride, $groomName, $brideName);

        // 8. Resolve accurate Memory details (Memory Snap game)
        $memoryDetail = $this->buildMemoryDetailText($groom, $bride, $groomName, $brideName);

        // 9. Resolve accurate Teamwork details (Flip the Tiles game)
        $teamworkDetail = $this->buildTeamworkDetailText($groom, $bride, $groomName, $brideName);

        // 10. Extract Food and Location choices & build Special Romantic Story
        $foodAndLocation = $this->extractFoodAndLocationChoices($groom, $bride, $allQA, $syncQA['groom_map'] ?? [], $syncQA['bride_map'] ?? []);
        $foodLocationStory = $this->buildFoodAndLocationStory($groomName, $brideName, $foodAndLocation);
        $observationMemoryStory = $this->buildObservationMemoryStory($groom, $bride, $groomName, $brideName);
        $teamworkDetail = $this->buildTeamworkDetailText($groom, $bride, $groomName, $brideName);

        // 11. Curate Clean, Non-Repetitive Story Paragraphs (Chemistry Story)
        $para1 = "சில connections சின்ன சின்ன விஷயங்கள்லதான் ஆரம்பிக்கும்… ஒரே மாதிரியான choices, பிடிச்ச interests, ஒருத்தரை ஒருத்தர் புரிஞ்சுக்கிற அந்த அழகான feeling! ❤️";
        $para2 = $teamworkDetail;
        $para3 = $observationMemoryStory;
        $para4 = $foodLocationStory;

        $paragraphs = array_values(array_filter([$para1, $para2, $para3, $para4]));
        $renderedTitle = "{$groomName} ❤️ {$brideName} — Chemistry Story";
        $renderedTagline = "Kaadhal Games Compatibility & Vibe Story ✨";
        $renderedStory = implode("\n\n", $paragraphs);

        return [
            'category_name'       => $categoryName,
            'title'               => $renderedTitle,
            'tagline'             => $renderedTagline,
            'score'               => $score,
            'paragraphs'          => $paragraphs,
            'story_content'       => $renderedStory,
            'call_to_action'      => $cta,
            'cta_url'             => $ctaUrl,
            'groom_name'          => $groomName,
            'bride_name'          => $brideName,
            'sync_qa'             => $allQA,
            'sync_qa_grouped'     => $syncQA,
            'food_and_location'   => $foodAndLocation,
            'food_location_story' => $foodLocationStory,
            'preference_match'    => $prefEval,
            'rasi_porutham'       => $rasiEval,
        ];
    }

    /**
     * Traditional Tamil Jothida 10 Porutham (தசப் பொருத்தம்) + Preference Rasi/Natchathiram Calculator.
     * Calculates all 10 Vedic/Tamil astrology poruthams from Bride's Rasi & Star to Groom's Rasi & Star,
     * plus checks whether the partner's Rasi & Star match the viewer's saved Step 8 Preferences.
     */
    public function evaluateRasiPorutham(User $viewer, User $partner): array
    {
        $viewer->loadMissing(['profile', 'preferences']);
        $partner->loadMissing(['profile', 'preferences']);

        $groom = ($viewer->gender === 'female' || $partner->gender === 'male') ? $partner : $viewer;
        $bride = ($groom->id === $viewer->id) ? $partner : $viewer;

        $groomRealRasi = trim((string) ($groom->profile?->raasi ?? ''));
        $groomRealStar = trim((string) ($groom->profile?->star ?? ''));
        $brideRealRasi = trim((string) ($bride->profile?->raasi ?? ''));
        $brideRealStar = trim((string) ($bride->profile?->star ?? ''));

        $viewerRealRasi = trim((string) ($viewer->profile?->raasi ?? ''));
        $viewerRealStar = trim((string) ($viewer->profile?->star ?? ''));
        $partnerRealRasi = trim((string) ($partner->profile?->raasi ?? ''));
        $partnerRealStar = trim((string) ($partner->profile?->star ?? ''));

        // A value counts only when it is a real raasi / star (whatever the
        // spelling); one we can't read is treated as not given, rather than
        // being scored as Mesha / Ashwini.
        $viewerHasRasi = Astrology::raasiIndex($viewerRealRasi) !== null && Astrology::starIndex($viewerRealStar) !== null;
        $partnerHasRasi = Astrology::raasiIndex($partnerRealRasi) !== null && Astrology::starIndex($partnerRealStar) !== null;
        $bothHaveRasi = $viewerHasRasi && $partnerHasRasi;

        $rasiTa = fn (string $v) => ($i = Astrology::raasiIndex($v)) !== null ? Astrology::raasiTamil($i) : ($v ?: 'Not specified');
        $starTa = fn (string $v) => ($i = Astrology::starIndex($v)) !== null ? Astrology::starTamil($i) : ($v ?: 'Not specified');

        $gRasiTa = $rasiTa($groomRealRasi);
        $bRasiTa = $rasiTa($brideRealRasi);
        $gStarTa = $starTa($groomRealStar);
        $bStarTa = $starTa($brideRealStar);

        // STRICT HOROSCOPE REQUIREMENT: Only evaluate Rasi Porutham if BOTH viewer and partner provided real Rasi & Star
        if (!$bothHaveRasi) {
            $missingStatus = 'both_missing';
            $missingMessage = "Both you and your partner ({$partner->name}) have not updated your Rasi and Natchathiram details yet. Please update your horoscope details in your profile to view the Rasi Porutham report.";

            if ($viewerHasRasi && !$partnerHasRasi) {
                $missingStatus = 'partner_missing';
                $missingMessage = "Your partner ({$partner->name}) has not updated their Rasi and Natchathiram details yet. Once they update their horoscope details in their profile, you will be able to view the full Rasi Porutham compatibility report.";
            } elseif (!$viewerHasRasi && $partnerHasRasi) {
                $missingStatus = 'viewer_missing';
                $missingMessage = "You have not updated your Rasi and Natchathiram details yet. Please update your horoscope details in your profile to view the Rasi Porutham compatibility report.";
            }

            return [
                'status'             => $missingStatus,
                'is_ready'           => false,
                'missing_message'    => $missingMessage,
                'viewer_has_rasi'    => $viewerHasRasi,
                'partner_has_rasi'   => $partnerHasRasi,
                'score'              => null,
                'matched_count'      => 0,
                'unmatched_count'    => 0,
                'total_poruthams'    => 10,
                'verdict_ta'         => 'விவரங்கள் தேவை',
                'matched_names_ta'   => '',
                'unmatched_names_ta' => '',
                'groom_name'         => $groom->name,
                'bride_name'         => $bride->name,
                'groom_rasi'         => $groomRealRasi ?: null,
                'groom_rasi_ta'      => $gRasiTa,
                'groom_star'         => $groomRealStar ?: null,
                'groom_star_ta'      => $gStarTa,
                'bride_rasi'         => $brideRealRasi ?: null,
                'bride_rasi_ta'      => $bRasiTa,
                'bride_star'         => $brideRealStar ?: null,
                'bride_star_ta'      => $bStarTa,
                'pref_rasi_matched'  => false,
                'pref_star_matched'  => false,
                'poruthams'          => [],
                'story_text'         => $missingMessage,
            ];
        }

        $groomRasi = $groomRealRasi;
        $brideRasi = $brideRealRasi;
        $groomStar = $groomRealStar;
        $brideStar = $brideRealStar;

        $gRasiIdx = Astrology::raasiIndex($groomRasi);
        $bRasiIdx = Astrology::raasiIndex($brideRasi);
        $gStarIdx = Astrology::starIndex($groomStar);
        $bStarIdx = Astrology::starIndex($brideStar);

        // Count from Bride's Star to Groom's Star (1..27) and Bride's Rasi to Groom's Rasi (1..12)
        $starCount = (($gStarIdx - $bStarIdx + 27) % 27) + 1;
        $rasiCount = (($gRasiIdx - $bRasiIdx + 12) % 12) + 1;

        // 1. Dina Porutham (தினப் பொருத்தம்)
        $dinaGood = in_array($starCount, [2, 4, 6, 8, 9, 11, 13, 15, 18, 20, 24, 26], true)
            || ($starCount === 27 && $gRasiIdx === $bRasiIdx)
            || in_array($starCount % 9, [2, 4, 6, 8, 0], true);

        // 2. Gana Porutham (கணப் பொருத்தம்: 0=Deva, 1=Manusha, 2=Rakshasa)
        $ganaMap = [
            0, 1, 2, 1, 0, 1, 0, 0, 2,
            2, 1, 1, 0, 2, 0, 2, 0, 2,
            2, 1, 1, 0, 2, 2, 1, 1, 0,
        ];
        $bGana = $ganaMap[$bStarIdx] ?? 0;
        $gGana = $ganaMap[$gStarIdx] ?? 0;
        $ganaGood = ($bGana === $gGana)
            || ($bGana !== 2 && $gGana !== 2)
            || ($gGana === 2 && $starCount > 14);

        // 3. Mahendra Porutham (மகேந்திரப் பொருத்தம்)
        $mahendraGood = in_array($starCount, [4, 7, 10, 13, 16, 19, 22, 25], true) || ($rasiCount >= 5);

        // 4. Stree Deergha Porutham (ஸ்திரீ தீர்க்கப் பொருத்தம்)
        $streeGood = ($starCount >= 7);

        // 5. Yoni Porutham (யோனிப் பொருத்தம்)
        $yoniGroup = [
            'horse', 'elephant', 'sheep', 'serpent', 'serpent', 'dog', 'cat', 'sheep', 'cat',
            'rat', 'rat', 'cow', 'buffalo', 'tiger', 'buffalo', 'tiger', 'deer', 'deer',
            'dog', 'monkey', 'mongoose', 'monkey', 'lion', 'horse', 'lion', 'cow', 'elephant',
        ];
        $enemyYoni = [
            'cat' => 'rat', 'rat' => 'cat',
            'serpent' => 'mongoose', 'mongoose' => 'serpent',
            'dog' => 'deer', 'deer' => 'dog',
            'monkey' => 'sheep', 'sheep' => 'monkey',
            'cow' => 'tiger', 'tiger' => 'cow',
            'horse' => 'buffalo', 'buffalo' => 'horse',
            'elephant' => 'lion', 'lion' => 'elephant',
        ];
        $bYoni = $yoniGroup[$bStarIdx] ?? 'horse';
        $gYoni = $yoniGroup[$gStarIdx] ?? 'elephant';
        $yoniGood = (($enemyYoni[$bYoni] ?? '') !== $gYoni);

        // 6. Rasi Porutham (ராசிப் பொருத்தம்)
        $rasiGood = in_array($rasiCount, [1, 3, 4, 7, 9, 10, 11, 12], true) || ($rasiCount === 2 && $starCount > 13);

        // 7. Rasi Adhipathi Porutham (ராசி அதிபதிப் பொருத்தம்)
        // Lords: 0:Mars, 1:Venus, 2:Mercury, 3:Moon, 4:Sun, 5:Mercury, 6:Venus, 7:Mars, 8:Jupiter, 9:Saturn, 10:Saturn, 11:Jupiter
        $lords = ['mars', 'venus', 'mercury', 'moon', 'sun', 'mercury', 'venus', 'mars', 'jupiter', 'saturn', 'saturn', 'jupiter'];
        $bLord = $lords[$bRasiIdx] ?? 'sun';
        $gLord = $lords[$gRasiIdx] ?? 'moon';
        $bitterFoes = [
            'sun' => ['saturn'], 'saturn' => ['sun', 'moon'],
            'moon' => ['saturn'], 'mars' => ['mercury'],
        ];
        $adhipathiGood = !in_array($gLord, $bitterFoes[$bLord] ?? [], true);

        // 8. Vasya Porutham (வசியப் பொருத்தம்)
        $vasyaPairs = [
            0 => [4, 7], 1 => [3, 6], 2 => [5], 3 => [7, 8],
            4 => [6, 9], 5 => [2, 11], 6 => [4, 5, 9], 7 => [3],
            8 => [11], 9 => [0, 10], 10 => [0, 9], 11 => [5, 8, 9],
        ];
        $vasyaGood = in_array($gRasiIdx, $vasyaPairs[$bRasiIdx] ?? [], true)
            || in_array($bRasiIdx, $vasyaPairs[$gRasiIdx] ?? [], true)
            || $adhipathiGood;

        // 9. Rajju Porutham (ரஜ்ஜுப் பொருத்தம் — மாங்கல்ய பலம்)
        $rajjuBody = [
            'pada', 'kati', 'nabhi', 'kanta', 'siro', 'kanta', 'nabhi', 'kati', 'pada',
            'pada', 'kati', 'nabhi', 'kanta', 'siro', 'kanta', 'nabhi', 'kati', 'pada',
            'pada', 'kati', 'nabhi', 'kanta', 'siro', 'kanta', 'nabhi', 'kati', 'pada',
        ];
        $rajjuDir = [
            'asc', 'asc', 'asc', 'asc', 'top', 'desc', 'desc', 'desc', 'desc',
            'asc', 'asc', 'asc', 'asc', 'top', 'desc', 'desc', 'desc', 'desc',
            'asc', 'asc', 'asc', 'asc', 'top', 'desc', 'desc', 'desc', 'desc',
        ];
        $rajjuGood = ($rajjuBody[$bStarIdx] !== $rajjuBody[$gStarIdx])
            || ($rajjuDir[$bStarIdx] !== $rajjuDir[$gStarIdx]);

        // 10. Vedha Porutham (வேதைப் பொருத்தம்)
        $vedhaPairs = [
            0 => 17, 17 => 0, 1 => 16, 16 => 1, 2 => 15, 15 => 2,
            3 => 14, 14 => 3, 5 => 21, 21 => 5, 6 => 20, 20 => 6,
            7 => 19, 19 => 7, 8 => 18, 18 => 8, 9 => 26, 26 => 9,
            10 => 25, 25 => 10, 11 => 24, 24 => 11, 12 => 23, 23 => 12,
            4 => 22, 22 => 4,
        ];
        $vedhaGood = (($vedhaPairs[$bStarIdx] ?? -1) !== $gStarIdx);

        $poruthams = [
            ['name_ta' => 'தினப் பொருத்தம்', 'name_en' => 'Dina Porutham', 'desc' => 'ஆரோக்கியம் & நல்வாழ்வு', 'matched' => $dinaGood],
            ['name_ta' => 'கணப் பொருத்தம்', 'name_en' => 'Gana Porutham', 'desc' => 'மன ஒற்றுமை & குண நலன்', 'matched' => $ganaGood],
            ['name_ta' => 'மகேந்திரப் பொருத்தம்', 'name_en' => 'Mahendra Porutham', 'desc' => 'புத்திர பாக்கியம் & வளர்ச்சி', 'matched' => $mahendraGood],
            ['name_ta' => 'ஸ்திரீ தீர்க்கப் பொருத்தம்', 'name_en' => 'Stree Deergha', 'desc' => 'சகல சம்பத்து & சுமங்கலி பாக்கியம்', 'matched' => $streeGood],
            ['name_ta' => 'யோனிப் பொருத்தம்', 'name_en' => 'Yoni Porutham', 'desc' => 'தம்பதியர் அந்நியோன்யம்', 'matched' => $yoniGood],
            ['name_ta' => 'ராசிப் பொருத்தம்', 'name_en' => 'Rasi Porutham', 'desc' => 'வம்ச விருத்தி & மகிழ்ச்சி', 'matched' => $rasiGood],
            ['name_ta' => 'ராசி அதிபதிப் பொருத்தம்', 'name_en' => 'Rasi Adhipathi', 'desc' => 'இரு குடும்ப நட்புறவு', 'matched' => $adhipathiGood],
            ['name_ta' => 'வசியப் பொருத்தம்', 'name_en' => 'Vasya Porutham', 'desc' => 'பரஸ்பர அன்பு & ஈர்ப்பு', 'matched' => $vasyaGood],
            ['name_ta' => 'ரஜ்ஜுப் பொருத்தம்', 'name_en' => 'Rajju Porutham', 'desc' => 'மாங்கல்ய பலம்', 'matched' => $rajjuGood],
            ['name_ta' => 'வேதைப் பொருத்தம்', 'name_en' => 'Vedha Porutham', 'desc' => 'தோஷ நிவர்த்தி & அமைதி', 'matched' => $vedhaGood],
        ];

        $matchedPoruthams = collect($poruthams)->where('matched', true);
        $unmatchedPoruthams = collect($poruthams)->where('matched', false);
        $matchedCount = $matchedPoruthams->count();
        $unmatchedCount = $unmatchedPoruthams->count();
        $jothidaPct = (int) round(($matchedCount / 10) * 100);

        // Also check Viewer's Step 8 Preference for Rasi & Natchathiram against Partner's profile
        $uPref = $viewer->preferences;
        $partnerRasi = $partner->profile?->raasi ?: ($partner->id === $groom->id ? $groomRasi : $brideRasi);
        $partnerStar = $partner->profile?->star ?: ($partner->id === $groom->id ? $groomStar : $brideStar);

        $prefRasiSet = $uPref && !empty($uPref->raasis);
        $prefStarSet = $uPref && !empty($uPref->stars);
        $prefRasiMatched = !$prefRasiSet || Astrology::raasiIn($partnerRasi, $uPref->raasis);
        $prefStarMatched = !$prefStarSet || Astrology::starIn($partnerStar, $uPref->stars);

        if ($prefRasiSet || $prefStarSet) {
            $prefPts = ($prefRasiMatched ? 50 : 25) + ($prefStarMatched ? 50 : 25);
            $finalScore = (int) min(100, max(65, round(($jothidaPct * 0.75) + ($prefPts * 0.25))));
        } else {
            $finalScore = (int) min(100, max(68, $jothidaPct));
        }

        $allMatchedNamesTa = $matchedPoruthams->pluck('name_ta')->implode(', ');
        $allUnmatchedNamesTa = $unmatchedPoruthams->pluck('name_ta')->implode(', ');
        $verdictTa = $matchedCount >= 8 ? 'உத்தம பொருத்தம்' : ($matchedCount >= 6 ? 'மத்திம பொருத்தம்' : 'சிறப்புப் பொருத்தம்');

        $unmatchedLine = $unmatchedCount > 0
            ? "❌ **இல்லாத பொருத்தங்கள் ({$unmatchedCount}):** இவர்களுக்கு **{$allUnmatchedNamesTa}** மட்டும் இல்லை."
            : "✅ **இல்லாத பொருத்தங்கள் (0):** இவர்களுக்கு 10 பொருத்தங்களில் எந்தப் பொருத்தக் குறையும் இல்லை!";

        $storyText = "🔮 **ராசி & நட்சத்திரப் பொருத்தம் (Simple Story):**\n"
            . "👦 **{$groom->name}:** **{$gRasiTa} ராசி, {$gStarTa} நட்சத்திரம்** | 👧 **{$bride->name}:** **{$bRasiTa} ராசி, {$bStarTa} நட்சத்திரம்**\n"
            . "✅ **இருக்கும் பொருத்தங்கள் ({$matchedCount}):** இவர்களுக்கு **{$allMatchedNamesTa}** ஆகிய பொருத்தங்கள் இருக்கு.\n"
            . "{$unmatchedLine}\n"
            . "📊 **மொத்தப் பொருத்தம்:** மொத்தம் **10-க்கு {$matchedCount} பொருத்தம் ({$matchedCount}/10 — {$finalScore}%)** இருக்கு (**{$verdictTa}**) ✨";

        return [
            'status'             => 'ready',
            'is_ready'           => true,
            'missing_message'    => null,
            'viewer_has_rasi'    => true,
            'partner_has_rasi'   => true,
            'score'              => $finalScore,
            'matched_count'      => $matchedCount,
            'unmatched_count'    => $unmatchedCount,
            'total_poruthams'    => 10,
            'verdict_ta'         => $verdictTa,
            'matched_names_ta'   => $allMatchedNamesTa,
            'unmatched_names_ta' => $allUnmatchedNamesTa,
            'groom_name'         => $groom->name,
            'bride_name'         => $bride->name,
            'groom_rasi'         => $groomRasi,
            'groom_rasi_ta'      => $gRasiTa,
            'groom_star'         => $groomStar,
            'groom_star_ta'      => $gStarTa,
            'bride_rasi'         => $brideRasi,
            'bride_rasi_ta'      => $bRasiTa,
            'bride_star'         => $brideStar,
            'bride_star_ta'      => $bStarTa,
            'pref_rasi_matched'  => $prefRasiMatched,
            'pref_star_matched'  => $prefStarMatched,
            'poruthams'          => $poruthams,
            'story_text'         => $storyText,
        ];
    }

    /**
     * Evaluate how well $partner matches $viewer's chosen preferences (and vice-versa)
     * across all 12 Partner Preference fields, returning both the percentage score
     * and the personalized Tamil/Tanglish story narrative.
     */
    public function evaluatePreferenceCompatibility(User $viewer, User $partner): array
    {
        $viewer->loadMissing([
            'profile.maritalStatus', 'profile.occupation', 'profile.incomeRange',
            'profile.motherTongue', 'profile.state', 'profile.religion',
            'profile.community', 'profile.education', 'preferences',
        ]);
        $partner->loadMissing([
            'profile.maritalStatus', 'profile.occupation', 'profile.incomeRange',
            'profile.motherTongue', 'profile.state', 'profile.religion',
            'profile.community', 'profile.education', 'preferences',
        ]);

        $up = $viewer->profile;
        $pp = $partner->profile;
        $uPref = $viewer->preferences;

        $checks = [];
        $earnedPoints = 0.0;
        $totalChecks = 0;

        // Helper to format height
        $fmtHeight = function ($cm) {
            if (!$cm) return null;
            $totalIn = (int) round($cm / 2.54);
            $ft = intdiv($totalIn, 12);
            $in = $totalIn % 12;
            return "{$cm} cm ({$ft}'{$in}\")";
        };

        // 1. Age Range
        $partnerAge = $partner->age();
        $viewerAge = $viewer->age();
        if ($uPref && ($uPref->age_min || $uPref->age_max) && $partnerAge > 0) {
            $totalChecks++;
            $minA = $uPref->age_min ?? 18;
            $maxA = $uPref->age_max ?? 70;
            $matched = ($partnerAge >= $minA && $partnerAge <= $maxA);
            $near = ($partnerAge >= ($minA - 2) && $partnerAge <= ($maxA + 2));
            $earnedPoints += $matched ? 1.0 : ($near ? 0.8 : 0.45);
            $checks[] = [
                'label' => 'Age',
                'expected' => "{$minA}–{$maxA} yrs",
                'actual' => "{$partnerAge} yrs",
                'matched' => $matched || $near,
            ];
        }

        // 2. Height Range
        if ($uPref && ($uPref->height_min_cm || $uPref->height_max_cm) && $pp?->height_cm) {
            $totalChecks++;
            $minH = $uPref->height_min_cm ?? 122;
            $maxH = $uPref->height_max_cm ?? 213;
            $matched = ($pp->height_cm >= $minH && $pp->height_cm <= $maxH);
            $near = ($pp->height_cm >= ($minH - 5) && $pp->height_cm <= ($maxH + 5));
            $earnedPoints += $matched ? 1.0 : ($near ? 0.8 : 0.45);
            $checks[] = [
                'label' => 'Height',
                'expected' => "{$minH}–{$maxH} cm",
                'actual' => $fmtHeight($pp->height_cm),
                'matched' => $matched || $near,
            ];
        }

        // 3. Weight Range
        if ($uPref && ($uPref->weight_min_kg || $uPref->weight_max_kg) && $pp?->weight_kg) {
            $totalChecks++;
            $minW = $uPref->weight_min_kg ?? 35;
            $maxW = $uPref->weight_max_kg ?? 140;
            $matched = ($pp->weight_kg >= $minW && $pp->weight_kg <= $maxW);
            $near = ($pp->weight_kg >= ($minW - 5) && $pp->weight_kg <= ($maxW + 5));
            $earnedPoints += $matched ? 1.0 : ($near ? 0.8 : 0.5);
            $checks[] = [
                'label' => 'Weight / Fit',
                'expected' => "{$minW}–{$maxW} kg",
                'actual' => "{$pp->weight_kg} kg",
                'matched' => $matched || $near,
            ];
        }

        // 4. Marital Status
        if ($uPref && !empty($uPref->marital_status_ids) && $pp?->marital_status_id) {
            $totalChecks++;
            $matched = in_array((int) $pp->marital_status_id, array_map('intval', $uPref->marital_status_ids), true);
            $earnedPoints += $matched ? 1.0 : 0.4;
            $checks[] = [
                'label' => 'Marital Status',
                'expected' => 'Selected Preference',
                'actual' => $pp->maritalStatus?->name ?? 'Never Married',
                'matched' => $matched,
            ];
        }

        // 5. Color / Complexion
        if ($uPref && !empty($uPref->complexions) && !empty($pp?->complexion)) {
            $totalChecks++;
            $matched = in_array(strtolower(trim($pp->complexion)), array_map(fn($c) => strtolower(trim($c)), $uPref->complexions), true);
            $earnedPoints += $matched ? 1.0 : 0.55;
            $checks[] = [
                'label' => 'Color',
                'expected' => implode(', ', $uPref->complexions),
                'actual' => $pp->complexion,
                'matched' => $matched,
            ];
        }

        // 6. Job / Occupation
        if ($uPref && !empty($uPref->occupation_ids) && $pp?->occupation_id) {
            $totalChecks++;
            $matched = in_array((int) $pp->occupation_id, array_map('intval', $uPref->occupation_ids), true);
            $earnedPoints += $matched ? 1.0 : 0.55;
            $checks[] = [
                'label' => 'Job',
                'expected' => 'Preferred Job',
                'actual' => $pp->occupation?->name ?? 'Professional',
                'matched' => $matched,
            ];
        }

        // 7. Job Salary Range
        if ($uPref && !empty($uPref->income_range_ids) && $pp?->income_range_id) {
            $totalChecks++;
            $matched = in_array((int) $pp->income_range_id, array_map('intval', $uPref->income_range_ids), true);
            $earnedPoints += $matched ? 1.0 : 0.55;
            $checks[] = [
                'label' => 'Salary Range',
                'expected' => 'Preferred Income',
                'actual' => $pp->incomeRange?->name ?? 'Annual Income',
                'matched' => $matched,
            ];
        }

        // 8. Mother Tongue
        if ($uPref && !empty($uPref->mother_tongue_ids) && $pp?->mother_tongue_id) {
            $totalChecks++;
            $matched = in_array((int) $pp->mother_tongue_id, array_map('intval', $uPref->mother_tongue_ids), true);
            $earnedPoints += $matched ? 1.0 : 0.45;
            $checks[] = [
                'label' => 'Mother Tongue',
                'expected' => 'Preferred Language',
                'actual' => $pp->motherTongue?->name ?? 'Tamil',
                'matched' => $matched,
            ];
        }

        // 9. State
        if ($uPref && !empty($uPref->state_ids) && $pp?->state_id) {
            $totalChecks++;
            $matched = in_array((int) $pp->state_id, array_map('intval', $uPref->state_ids), true);
            $earnedPoints += $matched ? 1.0 : 0.55;
            $checks[] = [
                'label' => 'State',
                'expected' => 'Preferred State',
                'actual' => $pp->state?->name ?? 'Tamil Nadu',
                'matched' => $matched,
            ];
        }

        // 10. Rasi
        if ($uPref && !empty($uPref->raasis) && !empty($pp?->raasi)) {
            $totalChecks++;
            $matched = Astrology::raasiIn($pp->raasi, $uPref->raasis);
            $earnedPoints += $matched ? 1.0 : 0.6;
            $checks[] = [
                'label' => 'Rasi',
                'expected' => implode(', ', $uPref->raasis),
                'actual' => Astrology::raasi($pp->raasi) ?? $pp->raasi,
                'matched' => $matched,
            ];
        }

        // 11. Natchathiram / Star
        if ($uPref && !empty($uPref->stars) && !empty($pp?->star)) {
            $totalChecks++;
            $matched = Astrology::starIn($pp->star, $uPref->stars);
            $earnedPoints += $matched ? 1.0 : 0.6;
            $checks[] = [
                'label' => 'Natchathiram',
                'expected' => implode(', ', $uPref->stars),
                'actual' => Astrology::star($pp->star) ?? $pp->star,
                'matched' => $matched,
            ];
        }

        // 12. Caste
        if ($uPref && !empty($uPref->castes) && !empty($pp?->caste)) {
            $totalChecks++;
            $matched = in_array(trim($pp->caste), $uPref->castes, true);
            $earnedPoints += $matched ? 1.0 : 0.55;
            $checks[] = [
                'label' => 'Caste',
                'expected' => implode(', ', array_slice($uPref->castes, 0, 3)),
                'actual' => $pp->caste,
                'matched' => $matched,
            ];
        }

        if ($totalChecks > 0) {
            $prefScore = (int) min(100, max(60, round(($earnedPoints / $totalChecks) * 100)));
        } else {
            // Profile-to-profile natural preference score when viewer hasn't saved custom preferences yet
            $naturalPts = 0;
            $naturalMax = 4;
            if ($up && $pp) {
                if ($up->religion_id === $pp->religion_id) $naturalPts++;
                if ($up->mother_tongue_id === $pp->mother_tongue_id) $naturalPts++;
                if ($up->state_id === $pp->state_id) $naturalPts++;
                if ($up->marital_status_id === $pp->marital_status_id) $naturalPts++;
            }
            $prefScore = $naturalPts > 0 ? (int) min(98, max(78, round(75 + ($naturalPts / $naturalMax) * 20))) : 88;
        }

        // Build concrete attributes for Viewer & Partner for the story text
        $vHeightStr = $fmtHeight($up?->height_cm) ?: '170 cm (5\'7")';
        $vWeightStr = ($up?->weight_kg ?: 65) . ' kg';
        $vBodyStr = $up?->body_type ? " ({$up->body_type})" : '';
        $vFitText = "{$vHeightStr} உயரம், {$vWeightStr} எடை{$vBodyStr}";
        $vColorText = $up?->complexion ?: 'Fair';
        $vJobText = $up?->occupation?->name ?: 'Professional';
        $vIncomeText = $up?->incomeRange?->name ? " ({$up->incomeRange->name})" : '';
        $vLangText = $up?->motherTongue?->name ?: 'Tamil';
        $vStateText = $up?->state?->name ?: 'Tamil Nadu';
        $vRasiStar = collect([$up?->raasi, $up?->star])->filter()->implode(' – ') ?: 'நல்ல ராசி & நட்சத்திரம்';
        $vCasteText = $up?->caste ? ", **{$up->caste}**" : '';

        $pHeightStr = $fmtHeight($pp?->height_cm) ?: '162 cm (5\'4")';
        $pWeightStr = ($pp?->weight_kg ?: 55) . ' kg';
        $pBodyStr = $pp?->body_type ? " ({$pp->body_type})" : '';
        $pFitText = "{$pHeightStr} உயரம், {$pWeightStr} எடை{$pBodyStr}";
        $pColorText = $pp?->complexion ?: 'Fair';
        $pJobText = $pp?->occupation?->name ?: 'Professional';
        $pIncomeText = $pp?->incomeRange?->name ? " ({$pp->incomeRange->name})" : '';
        $pLangText = $pp?->motherTongue?->name ?: 'Tamil';
        $pStateText = $pp?->state?->name ?: 'Tamil Nadu';
        $pRasiStar = collect([$pp?->raasi, $pp?->star])->filter()->implode(' – ') ?: 'சிறந்த ராசி & நட்சத்திரம்';
        $pCasteText = $pp?->caste ? ", **{$pp->caste}**" : '';

        $matchedLabels = collect($checks)->where('matched', true)->pluck('label')->all();
        $matchedSummary = !empty($matchedLabels)
            ? ' (' . implode(', ', $matchedLabels) . ' matched)'
            : '';

        $storyText = "💝 **Partner Preference Match ({$prefScore}% Score{$matchedSummary}):**\n"
            . "**{$partner->name}** எதிர்பார்த்த மாதிரியே நீங்க (**{$viewer->name}**) **{$vFitText}** கொண்ட சூப்பர் Fit-ல இருக்கீங்க, அவங்க மனசுக்குப் பிடிச்ச **‘{$vColorText}’** நிறத்துல (Color) இருக்கீங்க! அதுமட்டுமில்லாம **{$vJobText}**{$vIncomeText}, தாய்மொழி **{$vLangText}** (**{$vStateText}**){$vCasteText} மற்றும் **{$vRasiStar}** என அவங்க எதிர்பார்த்த எல்லா விஷயங்கள்லயும் ஒரு **Perfect Match**-ஆ இருக்கீங்க! ✨\n\n"
            . "அதே மாதிரி, நீங்க உங்க Preference-ல தேர்வு செஞ்ச மாதிரியே **{$partner->name}**-உம் **{$pFitText}** கொண்ட அழகான Fit-லயும், **‘{$pColorText}’** நிறத்துலயும் (Color), **{$pJobText}**{$pIncomeText}, **{$pLangText}** (**{$pStateText}**){$pCasteText} மற்றும் **{$pRasiStar}** என நீங்க எதிர்பார்த்த தகுதிகளோடு **{$prefScore}% Preference Match** ஆகி ஒரு **Perfect Match**-ஆ ஜொலிக்கிறாங்க! ❤️";

        return [
            'score'        => $prefScore,
            'category'     => $prefScore >= 85 ? 'Perfect Match!' : ($prefScore >= 70 ? 'Great Match!' : 'Good Match!'),
            'checks'       => $checks,
            'total_checks' => $totalChecks,
            'matched_cnt'  => count($matchedLabels),
            'story_text'   => $storyText,
            'viewer_fit'   => $vFitText,
            'viewer_color' => $vColorText,
            'partner_fit'  => $pFitText,
            'partner_color'=> $pColorText,
        ];
    }

    /**
     * Extract actual Compatibility Test questions & chosen answers for Groom and Bride.
     */
    protected function extractCompatibilityQA(User $groom, User $bride, ?User $viewer = null): array
    {
        $groomMap = []; // question_id => option_key
        $brideMap = []; // question_id => option_key
        $orderedQIds = [];

        // 1. Check latest sync GameRoom for this couple
        $syncRoom = GameRoom::whereIn('user_a_id', [$groom->id, $bride->id])
            ->whereIn('user_b_id', [$groom->id, $bride->id])
            ->where('game_type', 'sync')
            ->latest()
            ->first();

        if ($syncRoom) {
            $groomIsA = ($syncRoom->user_a_id === $groom->id);
            $gRaw = $groomIsA ? ($syncRoom->user_a_answers ?? []) : ($syncRoom->user_b_answers ?? []);
            $bRaw = $groomIsA ? ($syncRoom->user_b_answers ?? []) : ($syncRoom->user_a_answers ?? []);

            foreach ($gRaw as $entry) {
                if (!empty($entry['question_id']) && !empty($entry['option'])) {
                    $qId = (int)$entry['question_id'];
                    $groomMap[$qId] = $entry['option'];
                    $orderedQIds[] = $qId;
                }
            }
            foreach ($bRaw as $entry) {
                if (!empty($entry['question_id']) && !empty($entry['option'])) {
                    $qId = (int)$entry['question_id'];
                    $brideMap[$qId] = $entry['option'];
                    $orderedQIds[] = $qId;
                }
            }
        }

        // 2. Also check KoodalSyncAnswer table for both users
        $gAnswers = KoodalSyncAnswer::where('user_id', $groom->id)->orderBy('id', 'desc')->get();
        foreach ($gAnswers as $ans) {
            $qId = (int)$ans->question_id;
            if (!isset($groomMap[$qId])) {
                $groomMap[$qId] = $ans->selected_option;
                $orderedQIds[] = $qId;
            }
        }

        $bAnswers = KoodalSyncAnswer::where('user_id', $bride->id)->orderBy('id', 'desc')->get();
        foreach ($bAnswers as $ans) {
            $qId = (int)$ans->question_id;
            if (!isset($brideMap[$qId])) {
                $brideMap[$qId] = $ans->selected_option;
                $orderedQIds[] = $qId;
            }
        }

        $orderedQIds = array_values(array_unique($orderedQIds));
        if (empty($orderedQIds)) {
            return ['matched' => [], 'different' => [], 'all' => [], 'groom_map' => $groomMap, 'bride_map' => $brideMap];
        }

        $questions = KoodalSyncQuestion::whereIn('id', $orderedQIds)->get()->keyBy('id');

        $matched = [];
        $different = [];
        $all = [];

        // Prioritize questions answered by BOTH groom and bride first
        $sharedQIds = array_values(array_intersect(array_keys($groomMap), array_keys($brideMap)));
        $targetQIds = !empty($sharedQIds) ? array_slice($sharedQIds, 0, 10) : array_slice($orderedQIds, 0, 10);

        foreach ($targetQIds as $qId) {
            $q = $questions->get($qId);
            if (!$q) continue;

            $gOptKey = $groomMap[$qId] ?? null;
            $bOptKey = $brideMap[$qId] ?? null;

            $gLabelEn = $this->resolveOptionLabel($q->options ?? [], $gOptKey, 'en');
            $gLabelTa = $this->resolveOptionLabel($q->options ?? [], $gOptKey, 'ta');
            $bLabelEn = $this->resolveOptionLabel($q->options ?? [], $bOptKey, 'en');
            $bLabelTa = $this->resolveOptionLabel($q->options ?? [], $bOptKey, 'ta');

            $gDisplay = $gLabelTa ? "{$gLabelEn} ({$gLabelTa})" : $gLabelEn;
            $bDisplay = $bLabelTa ? "{$bLabelEn} ({$bLabelTa})" : $bLabelEn;

            $viewerIsGroom = !$viewer || ($viewer->id === $groom->id);
            $isSame = ($gOptKey !== null && $bOptKey !== null && $gOptKey === $bOptKey);

            $item = [
                'question_id'    => $qId,
                'category'       => $q->category ?: 'Lifestyle',
                'question_en'    => $q->question_text_en,
                'question_ta'    => $q->question_text_ta ?: $q->question_text_en,
                'question_text'  => $q->question_text_en ?: $q->question_text_ta,
                'groom_option'   => $gOptKey,
                'groom_answer'   => $gLabelEn ?: $gOptKey,
                'groom_ta'       => $gLabelTa ?: $gLabelEn,
                'groom_full'     => $gDisplay ?: $gOptKey,
                'bride_option'   => $bOptKey,
                'bride_answer'   => $bLabelEn ?: $bOptKey,
                'bride_ta'       => $bLabelTa ?: $bLabelEn,
                'bride_full'     => $bDisplay ?: $bOptKey,
                'user_option'    => $viewerIsGroom ? ($gLabelEn ?: $gOptKey) : ($bLabelEn ?: $bOptKey),
                'partner_option' => $viewerIsGroom ? ($bLabelEn ?: $bOptKey) : ($gLabelEn ?: $gOptKey),
                'is_match'       => $isSame,
                'is_same'        => $isSame,
            ];

            $all[] = $item;
            if ($item['is_match']) {
                $matched[] = $item;
            } elseif ($gOptKey !== null && $bOptKey !== null) {
                $different[] = $item;
            }
        }

        return [
            'matched'   => $matched,
            'different' => $different,
            'all'       => $all,
            'groom_map' => $groomMap,
            'bride_map' => $brideMap,
        ];
    }

    public function resolveOptionLabel(array $options, ?string $key, string $lang = 'en'): string
    {
        if (!$key) return '';
        if (preg_match('/^opt_([0-9]+)$/', $key, $m)) {
            $idx = (int)$m[1];
            if (isset($options[$idx])) {
                $opt = $options[$idx];
                return $lang === 'ta' && !empty($opt['label_ta']) ? trim($opt['label_ta']) : trim($opt['label_en'] ?? $opt['label_ta'] ?? $key);
            }
        }
        foreach ($options as $idx => $opt) {
            $optKey = $opt['key'] ?? (string)$idx;
            $optEn = $opt['label_en'] ?? '';
            $optTa = $opt['label_ta'] ?? '';
            if ($optKey === $key || $optEn === $key || $optTa === $key) {
                if ($lang === 'ta' && !empty($optTa)) {
                    return trim($optTa);
                }
                return trim($optEn ?: ($optTa ?: $key));
            }
        }
        return $key;
    }

    protected function buildCompatibilityDetailText(string $groomName, string $brideName, array $matchedQA, array $differentQA, array $allQA): string
    {
        if (empty($allQA)) {
            return "Compatibility Test (Koodal Sync)-ல நீங்க ரெண்டு பேரும் உங்க விருப்பங்களைப் பகிர்ந்துக்கும்போது, உங்களுக்குள் இருக்கும் common choices மற்றும் தனித்தனி ரசனைகளை இன்னும் அழகா தெரிஞ்சுக்கலாம்!";
        }

        $totalCount = count($allQA);
        $matchCount = count($matchedQA);
        $lines = [];

        $lines[] = "🎯 **Compatibility Test (Koodal Sync) Results ({$matchCount}/{$totalCount} Matched):**";

        if (!empty($matchedQA)) {
            $matchBadges = [];
            foreach (array_slice($matchedQA, 0, 4) as $m) {
                $qShort = $m['question_ta'] ?: $m['question_en'];
                $ans = $m['groom_answer'];
                $matchBadges[] = "“{$qShort}” கேள்விக்கு இருவருமே **{$ans}**";
            }
            $lines[] = "✅ **ஒரே மாதிரி சிந்தித்த விஷயங்கள்:** " . implode('; ', $matchBadges) . " என ஒரே மாதிரி பதில் அளித்திருக்கீங்க! இது உங்க ரெண்டு பேரோட இயல்பான ஒத்துப்போதலைக் காட்டுது. ❤️";
        }

        if (!empty($differentQA)) {
            $diffBadges = [];
            foreach (array_slice($differentQA, 0, 3) as $d) {
                $qShort = $d['question_ta'] ?: $d['question_en'];
                $diffBadges[] = "“{$qShort}” கேள்விக்கு {$groomName} **{$d['groom_answer']}** என்றும், {$brideName} **{$d['bride_answer']}** என்றும்";
            }
            $lines[] = "✨ **ஒருத்தர் இன்னொருத்தருக்கு அறிமுகப்படுத்தக்கூடிய விருப்பங்கள்:** " . implode('; ', $diffBadges) . " தேர்ந்தெடுத்திருக்கீங்க.";
        }

        return implode("\n\n", $lines);
    }

    protected function buildObservationDetailText(User $groom, User $bride, string $groomName, string $brideName): string
    {
        $findItScore = KoodalGameScore::whereIn('user_id', [$groom->id, $bride->id])
            ->whereIn('partner_id', [$groom->id, $bride->id])
            ->where('game_type', 'find_it')
            ->latest()
            ->first();

        if (!$findItScore) {
            return "🔍 **Observation Game (Find It):** இன்னும் இந்த game-ஐ நீங்க விளையாடவில்லை. 7 வித்தியாசங்களைக் கண்டுபிடிக்கும் இந்த விளையாட்டை விளையாடும்போது உங்க கவனிப்புத் திறன் (Observation score) இங்கே இடம் பெறும்!";
        }

        $details = is_array($findItScore->details) ? $findItScore->details : [];
        $totalDiffs = (int)($details['total'] ?? 7);
        if ($totalDiffs > 7) $totalDiffs = 7;

        $foundCount = isset($details['found_count']) ? min($totalDiffs, (int)$details['found_count']) : null;
        $myCount = isset($details['my_count']) ? (int)$details['my_count'] : null;
        $partnerCount = isset($details['partner_count']) ? (int)$details['partner_count'] : null;
        $pct = (int)$findItScore->score;

        if ($myCount !== null && $partnerCount !== null) {
            $groomSpotted = ($findItScore->user_id === $groom->id) ? $myCount : $partnerCount;
            $brideSpotted = ($findItScore->user_id === $bride->id) ? $myCount : $partnerCount;
            $totalFound = min($totalDiffs, $groomSpotted + $brideSpotted);
            return "🔍 **Observation Game (Find It):** சின்ன சின்ன details-ஐ கவனிச்சு {$groomName} ({$groomSpotted}/{$totalDiffs}) மற்றும் {$brideName} ({$brideSpotted}/{$totalDiffs}) என மொத்தம் **{$totalFound}/{$totalDiffs} differences** கண்டுபிடிச்சு **{$pct}% Observation Score** எடுத்திருக்கீங்க!";
        }

        if ($foundCount !== null) {
            return "🔍 **Observation Game (Find It):** சின்ன சின்ன details-ஐ கவனிச்சு {$groomName} & {$brideName} மொத்தம் **{$foundCount}/{$totalDiffs} differences** கண்டுபிடிச்சு **{$pct}% Observation Score** எடுத்திருக்கீங்க!";
        }

        return "🔍 **Observation Game (Find It):** சின்ன சின்ன details-ஐ கவனிச்சு **{$pct}% Observation Score** எடுத்திருக்கீங்க!";
    }

    protected function buildMemoryDetailText(User $groom, User $bride, string $groomName, string $brideName): string
    {
        // Check latest completed MemorySnapGame room for this pair first
        $msGame = MemorySnapGame::where(function ($q) use ($groom, $bride) {
                $q->where('user_one_id', $groom->id)->where('user_two_id', $bride->id);
            })
            ->orWhere(function ($q) use ($groom, $bride) {
                $q->where('user_one_id', $bride->id)->where('user_two_id', $groom->id);
            })
            ->where('status', 'completed')
            ->latest()
            ->first();

        $memoryScore = KoodalGameScore::whereIn('user_id', [$groom->id, $bride->id])
            ->whereIn('partner_id', [$groom->id, $bride->id])
            ->whereIn('game_type', ['memory_snap', 'memory'])
            ->latest()
            ->first();

        if ($msGame) {
            $totalQ = max(1, is_array($msGame->questions_data) ? count($msGame->questions_data) : 5);
            $gScore = ($msGame->user_one_id === $groom->id) ? (int)$msGame->user_one_score : (int)$msGame->user_two_score;
            $bScore = ($msGame->user_one_id === $bride->id) ? (int)$msGame->user_one_score : (int)$msGame->user_two_score;
            $combinedPct = (int) round(((($gScore + $bScore) / 2) / $totalQ) * 100);
            return "🧠 **Memory Snap Game:** விஷயங்களை நினைவில் வச்சுக்கிற திறமையைக் காட்டி {$groomName} (**{$gScore}/{$totalQ}**) மற்றும் {$brideName} (**{$bScore}/{$totalQ}**) எடுத்திருக்கீங்க (Memory Chemistry: **{$combinedPct}%**)!";
        }

        if ($memoryScore) {
            $details = is_array($memoryScore->details) ? $memoryScore->details : [];
            $totalQ = (int)($details['total'] ?? $details['total_questions'] ?? 5);
            $pct = (int)$memoryScore->score;

            if (isset($details['user_one_score']) && isset($details['user_two_score'])) {
                $gScore = (($details['user_one_id'] ?? 0) === $groom->id) ? (int)$details['user_one_score'] : (int)$details['user_two_score'];
                $bScore = (($details['user_one_id'] ?? 0) === $bride->id) ? (int)$details['user_one_score'] : (int)$details['user_two_score'];
                return "🧠 **Memory Snap Game:** விஷயங்களை நினைவில் வச்சுக்கிற திறமையைக் காட்டி {$groomName} (**{$gScore}/{$totalQ}**) மற்றும் {$brideName} (**{$bScore}/{$totalQ}**) எடுத்திருக்கீங்க (Memory Score: **{$pct}%**)!";
            }

            $rawCorrect = $details['score'] ?? $details['correct_count'] ?? round(($pct / 100) * $totalQ);
            $scorerName = ($memoryScore->user_id === $groom->id) ? $groomName : $brideName;
            return "🧠 **Memory Game:** விஷயங்களை நினைவில் வச்சுக்கிற திறமையைக் காட்டி {$scorerName} **{$rawCorrect}/{$totalQ} ({$pct}%)** score எடுத்திருக்காங்க!";
        }

        return "🧠 **Memory Snap Game:** இன்னும் இந்த game-ஐ நீங்க விளையாடவில்லை. விளையாடியதும் உங்க உண்மையான Memory score இங்கே காட்டப்படும்!";
    }

    protected function buildTeamworkDetailText(User $groom, User $bride, string $groomName, string $brideName): string
    {
        $flipScore = KoodalGameScore::whereIn('user_id', [$groom->id, $bride->id])
            ->whereIn('partner_id', [$groom->id, $bride->id])
            ->where('game_type', 'flip')
            ->latest()
            ->first();

        if ($flipScore) {
            $details = is_array($flipScore->details) ? $flipScore->details : [];
            $pct = (int)$flipScore->score;
            $p1Pairs = $details['pairs_matched'] ?? null;
            $p2Pairs = $details['opponent_pairs'] ?? null;
            if ($p1Pairs !== null && $p2Pairs !== null) {
                $gPairs = ($flipScore->user_id === $groom->id) ? $p1Pairs : $p2Pairs;
                $bPairs = ($flipScore->user_id === $bride->id) ? $p1Pairs : $p2Pairs;
                return "🤝 **Teamwork Game (Flip the Tiles):** ஒருத்தரை ஒருத்தர் ஜெயிக்க வைக்கிற இந்த விளையாட்டுல {$groomName} ({$gPairs} pairs) மற்றும் {$brideName} ({$bPairs} pairs) எடுத்து **{$pct}% Teamwork Chemistry** காட்டியிருக்கீங்க! ஒருத்தருடைய முயற்சிக்கு இன்னொருத்தர் பக்கபலமா இருந்து வெற்றிகளைச் சேர்ந்து கொண்டாடுற அந்த mutual support வாழ்க்கைப் பயணத்தையும் அழகாக்கும். ❤️";
            }
            return "🤝 **Teamwork Game (Flip the Tiles):** {$groomName} மற்றும் {$brideName} ரெண்டு பேரும் ஒருத்தருக்கு ஒருத்தர் support பண்ணி விளையாடி **{$pct}% Teamwork Score** எடுத்திருக்கீங்க! ❤️";
        }

        return "🤝 **Teamwork Challenge (Flip the Tiles):** {$groomName} மற்றும் {$brideName} ரெண்டு பேரும் ஒருத்தருக்கு ஒருத்தர் support பண்ணி விளையாடும்போது, ஒருத்தருடைய முயற்சிக்கு இன்னொருத்தர் பக்கபலமா இருந்து வெற்றிகளைச் சேர்ந்து கொண்டாடுற அந்த mutual support வாழ்க்கைப் பயணத்தையும் அழகாக்கும். ❤️";
    }

    /**
     * Check if a question is in the Food & Dining category.
     */
    public function isFoodQuestion($q): bool
    {
        if (!$q) return false;
        $category = is_object($q) ? ($q->category ?? '') : ($q['category'] ?? '');
        $en = strtolower(is_object($q) ? ($q->question_text_en ?? '') : ($q['question_en'] ?? $q['question_text_en'] ?? $q['question_text'] ?? ''));
        $ta = is_object($q) ? ($q->question_text_ta ?? '') : ($q['question_ta'] ?? $q['question_text_ta'] ?? '');

        if (in_array($category, ['Food', 'Food & Dining'], true)) return true;

        $foodKeywords = ['food', 'breakfast', 'meal', 'biryani', 'dosa', 'cook', 'dessert', 'spic', 'dish', 'dining'];
        foreach ($foodKeywords as $kw) {
            if (str_contains($en, $kw)) return true;
        }

        $taKeywords = ['சாப்பாடு', 'உணவு', 'சமையல்', 'பிரியாணி', 'சாப்பிட'];
        foreach ($taKeywords as $tkw) {
            if (str_contains($ta, $tkw)) return true;
        }

        return false;
    }

    /**
     * Check if a question is in the Location & Travel category.
     */
    public function isLocationQuestion($q): bool
    {
        if (!$q) return false;
        $category = is_object($q) ? ($q->category ?? '') : ($q['category'] ?? '');
        $en = strtolower(is_object($q) ? ($q->question_text_en ?? '') : ($q['question_en'] ?? $q['question_text_en'] ?? $q['question_text'] ?? ''));
        $ta = is_object($q) ? ($q->question_text_ta ?? '') : ($q['question_ta'] ?? $q['question_text_ta'] ?? '');

        if (in_array($category, ['Travel', 'Location', 'Travel & Vacation', 'Location & Travel'], true)) return true;

        $locKeywords = ['location', 'living setup', 'destination', 'holiday', 'trip', 'beach', 'hill station', 'city', 'town', 'village', 'vacation', 'place to live'];
        foreach ($locKeywords as $kw) {
            if (str_contains($en, $kw)) return true;
        }

        $taKeywords = ['இடம்', 'சுற்றுலா', 'வசிக்கும்', 'ஊர்', 'பயணம்', 'நகரம்', 'கிராமம்'];
        foreach ($taKeywords as $tkw) {
            if (str_contains($ta, $tkw)) return true;
        }

        return false;
    }

    /**
     * Format clean bilingual label (Tamil (English)).
     */
    public function formatBilingualLabel(?string $ta, ?string $en): string
    {
        $ta = trim($ta ?? '');
        $en = trim($en ?? '');
        if (!empty($ta) && !empty($en) && $ta !== $en) {
            return "{$ta} ({$en})";
        }
        return $ta ?: ($en ?: '');
    }

    /**
     * Build standard QA item array from question and user options.
     */
    public function buildQAItem(KoodalSyncQuestion $q, ?string $gOptKey, ?string $bOptKey, int $groomId): array
    {
        $gLabelEn = $this->resolveOptionLabel($q->options ?? [], $gOptKey, 'en');
        $gLabelTa = $this->resolveOptionLabel($q->options ?? [], $gOptKey, 'ta');
        $bLabelEn = $this->resolveOptionLabel($q->options ?? [], $bOptKey, 'en');
        $bLabelTa = $this->resolveOptionLabel($q->options ?? [], $bOptKey, 'ta');

        $gDisplay = $this->formatBilingualLabel($gLabelTa, $gLabelEn);
        $bDisplay = $this->formatBilingualLabel($bLabelTa, $bLabelEn);

        $isSame = ($gOptKey !== null && $bOptKey !== null && $gOptKey === $bOptKey);

        return [
            'question_id'    => (int)$q->id,
            'category'       => $q->category ?: 'Lifestyle',
            'question_en'    => $q->question_text_en,
            'question_ta'    => $q->question_text_ta ?: $q->question_text_en,
            'question_text'  => $q->question_text_en ?: $q->question_text_ta,
            'groom_option'   => $gOptKey,
            'groom_answer'   => $gLabelEn ?: $gOptKey,
            'groom_ta'       => $gLabelTa ?: $gLabelEn,
            'groom_full'     => $gDisplay ?: $gOptKey,
            'bride_option'   => $bOptKey,
            'bride_answer'   => $bLabelEn ?: $bOptKey,
            'bride_ta'       => $bLabelTa ?: $bLabelEn,
            'bride_full'     => $bDisplay ?: $bOptKey,
            'is_match'       => $isSame,
            'is_same'        => $isSame,
        ];
    }

    /**
     * Extract Food and Location choices for Groom and Bride.
     */
    public function extractFoodAndLocationChoices(User $groom, User $bride, array $allQA = [], array $groomMap = [], array $brideMap = []): array
    {
        $foodItem = null;
        $locationItem = null;

        // 1. Search in $allQA
        foreach ($allQA as $item) {
            if (!$foodItem && $this->isFoodQuestion($item)) {
                $foodItem = $item;
            }
            if (!$locationItem && $this->isLocationQuestion($item)) {
                $locationItem = $item;
            }
        }

        // 2. Search database answers if missing
        if (!$foodItem || !$locationItem) {
            $gAns = KoodalSyncAnswer::where('user_id', $groom->id)->pluck('selected_option', 'question_id')->toArray();
            $bAns = KoodalSyncAnswer::where('user_id', $bride->id)->pluck('selected_option', 'question_id')->toArray();
            $allQIds = array_unique(array_merge(array_keys($gAns), array_keys($bAns), array_keys($groomMap), array_keys($brideMap)));

            if (!empty($allQIds)) {
                $qs = KoodalSyncQuestion::whereIn('id', $allQIds)->get();
                foreach ($qs as $q) {
                    $gKey = $gAns[$q->id] ?? $groomMap[$q->id] ?? null;
                    $bKey = $bAns[$q->id] ?? $brideMap[$q->id] ?? null;

                    if (!$foodItem && $this->isFoodQuestion($q)) {
                        $foodItem = $this->buildQAItem($q, $gKey, $bKey, $groom->id);
                    }
                    if (!$locationItem && $this->isLocationQuestion($q)) {
                        $locationItem = $this->buildQAItem($q, $gKey, $bKey, $groom->id);
                    }
                }
            }
        }

        // 3. Fallbacks if questions exist in system but neither answered yet
        if (!$foodItem) {
            $fallbackFoodQ = KoodalSyncQuestion::where('is_active', true)
                ->where(function ($q) {
                    $q->whereIn('category', ['Food', 'Food & Dining'])
                      ->orWhere('question_text_en', 'like', '%food%')
                      ->orWhere('question_text_ta', 'like', '%உணவு%');
                })
                ->first();
            if ($fallbackFoodQ) {
                $foodItem = $this->buildQAItem($fallbackFoodQ, null, null, $groom->id);
            }
        }

        if (!$locationItem) {
            $fallbackLocQ = KoodalSyncQuestion::where('is_active', true)
                ->where(function ($q) {
                    $q->whereIn('category', ['Travel', 'Location', 'Travel & Vacation', 'Location & Travel'])
                      ->orWhere('question_text_en', 'like', '%location%')
                      ->orWhere('question_text_ta', 'like', '%இடம்%');
                })
                ->first();
            if ($fallbackLocQ) {
                $locationItem = $this->buildQAItem($fallbackLocQ, null, null, $groom->id);
            }
        }

        $groomLocationLabel = '';
        $groomFoodLabel = '';
        $brideLocationLabel = '';
        $brideFoodLabel = '';

        $hasLocationChoice = false;
        $hasFoodChoice = false;
        $locationMatched = false;
        $foodMatched = false;

        if ($locationItem) {
            $gTa = $locationItem['groom_ta'] ?? '';
            $gEn = $locationItem['groom_answer'] ?? '';
            $bTa = $locationItem['bride_ta'] ?? '';
            $bEn = $locationItem['bride_answer'] ?? '';

            if (!empty($locationItem['groom_option'])) {
                $groomLocationLabel = $this->formatBilingualLabel($gTa, $gEn);
                $hasLocationChoice = true;
            }
            if (!empty($locationItem['bride_option'])) {
                $brideLocationLabel = $this->formatBilingualLabel($bTa, $bEn);
                $hasLocationChoice = true;
            }
            $locationMatched = !empty($locationItem['is_match']);
        }

        if ($foodItem) {
            $gTa = $foodItem['groom_ta'] ?? '';
            $gEn = $foodItem['groom_answer'] ?? '';
            $bTa = $foodItem['bride_ta'] ?? '';
            $bEn = $foodItem['bride_answer'] ?? '';

            if (!empty($foodItem['groom_option'])) {
                $groomFoodLabel = $this->formatBilingualLabel($gTa, $gEn);
                $hasFoodChoice = true;
            }
            if (!empty($foodItem['bride_option'])) {
                $brideFoodLabel = $this->formatBilingualLabel($bTa, $bEn);
                $hasFoodChoice = true;
            }
            $foodMatched = !empty($foodItem['is_match']);
        }

        return [
            'groom_location_label' => $groomLocationLabel,
            'groom_food_label'     => $groomFoodLabel,
            'bride_location_label' => $brideLocationLabel,
            'bride_food_label'     => $brideFoodLabel,
            'has_location_choice'  => $hasLocationChoice,
            'has_food_choice'      => $hasFoodChoice,
            'location_matched'     => $locationMatched,
            'food_matched'         => $foodMatched,
            'location_item'        => $locationItem,
            'food_item'            => $foodItem,
        ];
    }

    /**
     * Build the cute Food & Location mini story based on Groom and Bride's chosen preferences.
     * Incorporates Groom's chosen location, Bride's chosen food, and invites them to learn more about each other.
     */
    /**
     * Build non-score based narrative on Observation & Memory power.
     * Highlights who has sharper observation/memory and frames it sweetly as a Perfect Match.
     */
    protected function buildObservationMemoryStory(User $groom, User $bride, string $groomName, string $brideName): string
    {
        // 1. Find It observation score
        $findItScore = KoodalGameScore::whereIn('user_id', [$groom->id, $bride->id])
            ->whereIn('partner_id', [$groom->id, $bride->id])
            ->where('game_type', 'find_it')
            ->latest()
            ->first();

        $details = is_array($findItScore?->details) ? $findItScore->details : [];
        $myCount = (int) ($details['my_count'] ?? 0);
        $partnerCount = (int) ($details['partner_count'] ?? 0);
        $groomFound = ($findItScore && $findItScore->user_id === $groom->id) ? $myCount : $partnerCount;
        $brideFound = ($findItScore && $findItScore->user_id === $groom->id) ? $partnerCount : $myCount;

        // 2. Memory Snap score
        $msGame = MemorySnapGame::where(function ($q) use ($groom, $bride) {
                $q->where('user_one_id', $groom->id)->where('user_two_id', $bride->id);
            })->orWhere(function ($q) use ($groom, $bride) {
                $q->where('user_one_id', $bride->id)->where('user_two_id', $groom->id);
            })->where('status', 'completed')->latest()->first();

        $groomMem = $msGame ? (int)(($msGame->user_one_id === $groom->id) ? $msGame->user_one_score : $msGame->user_two_score) : 0;
        $brideMem = $msGame ? (int)(($msGame->user_one_id === $bride->id) ? $msGame->user_one_score : $msGame->user_two_score) : 0;

        $groomTotal = $groomFound + $groomMem;
        $brideTotal = $brideFound + $brideMem;

        if ($groomTotal > $brideTotal && $groomTotal > 0) {
            return "🎯 **கூர்மையான கவனிப்பும் நினைவாற்றலும் (Observation & Memory Chemistry):**\nகேமில் {$groomName}-க்கு சின்ன சின்ன நுணுக்கங்களையும் கவனிக்கும் பார்வையும் நினைவாற்றலும் ரொம்பவே சுட்டியா இருக்கு! அதேசமயம் {$brideName}-ன் அமைதியான அணுகுமுறையும் இயல்பான ரசனையும் இணையும்போது, ஒருத்தருக்கு ஒருத்தர் பக்கபலமா இருந்து வாழ்க்கையின் எல்லா தருணங்களையும் அழகா வழிநடத்த நீங்க ரெண்டு பேரும் ஒரு அருமையான Perfect Match! ✨";
        } elseif ($brideTotal > $groomTotal && $brideTotal > 0) {
            return "🎯 **கூர்மையான கவனிப்பும் நினைவாற்றலும் (Observation & Memory Chemistry):**\nகேமில் {$brideName}-க்கு சின்ன சின்ன நுணுக்கங்களையும் சட்டுனு கவனிக்கும் பார்வையும் நினைவாற்றலும் ரொம்பவே சுட்டியா இருக்கு! அதேசமயம் {$groomName}-ன் துடிப்பான அணுகுமுறையும் இயல்பான ரசனையும் இணையும்போது, ஒருத்தருக்கு ஒருத்தர் பக்கபலமா இருந்து வாழ்க்கையின் எல்லா தருணங்களையும் அழகா வழிநடத்த நீங்க ரெண்டு பேரும் ஒரு அருமையான Perfect Match! ✨";
        } else {
            return "🎯 **கூர்மையான கவனிப்பும் நினைவாற்றலும் (Observation & Memory Chemistry):**\n{$groomName} & {$brideName} ரெண்டு பேருக்குமே நுணுக்கங்களை கவனிக்கும் பார்வையும் அருமையான நினைவாற்றலும் சமமா அமைஞ்சிருக்கு! ஒருத்தர் யோசிக்கும்போது இன்னொருத்தர் புரிந்து கொள்ளும் இந்த சுட்டித்தனம் உங்க ரெண்டு பேரையும் ஒருத்தருக்கு ஒருத்தர் பொருத்தமான Perfect Match-ஆ அழகா இணைக்குது! ✨";
        }
    }

    /**
     * Build Romantic Food & Location Dream Scene.
     * Incorporates Groom's chosen location, Bride's chosen food, and invites them to chat.
     */
    public function buildFoodAndLocationStory(string $groomName, string $brideName, array $fnl): string
    {
        $gLoc = $fnl['groom_location_label'] ?: ($fnl['bride_location_label'] ?: 'பிடித்த அழகான இடம்');
        $bFood = $fnl['bride_food_label'] ?: ($fnl['groom_food_label'] ?: 'சுவையான உணவு');
        $chatUrl = url('/messages');

        return "கொஞ்சம் கற்பனை பண்ணிப் பாருங்க... {$groomName} ஆசைப்பட்ட அந்த அழகான இடத்தில் ({$gLoc}) உட்கார்ந்து, {$brideName}-க்கு பிடிச்ச அந்த சுவையான உணவை ({$bFood}) ரெண்டு பேரும் சேர்ந்து ரசிச்சு சாப்பிட்டுக்கிட்டே... உங்க கனவுகளையும் நினைவுகளையும் பகிர்ந்துகிட்டா எவ்வளவு அழகா இருக்கும்! 💬💖\n\n✨ உங்களைப் பற்றி இன்னும் நிறைய தெரிஞ்சுக்கோங்க, பேச்சைத் தொடங்குங்க! [💬 Chat Now]({$chatUrl})";
    }

    /**
     * Build the {favourite_scene} paragraph using Food & Location choices.
     */
    protected function buildFavouriteSceneFromAnswers(string $groomName, string $brideName, array $matchedQA, array $differentQA, array $allQA): string
    {
        $dummyGroom = new User(['id' => 0, 'name' => $groomName, 'gender' => 'male']);
        $dummyBride = new User(['id' => 1, 'name' => $brideName, 'gender' => 'female']);
        $fnl = $this->extractFoodAndLocationChoices($dummyGroom, $dummyBride, $allQA);
        return $this->buildFoodAndLocationStory($groomName, $brideName, $fnl);
    }

    /**
     * Ensure default Admin Panel Vibe Story templates exist and upgrade any legacy stories.
     */
    public static function ensureDefaultTemplatesSeeded(): void
    {
        try {
            foreach (self::getDefaultTemplates() as $tpl) {
                KoodalVibeStory::updateOrCreate(
                    ['category_key' => $tpl['category_key']],
                    $tpl
                );
            }
        } catch (\Throwable $e) {
            // Ignore DB errors if migrations haven't run yet
        }
    }

    /**
     * Default Admin Panel Vibe Story Templates powered by real Compatibility Test answers and game scores.
     */
    public static function getDefaultTemplates(): array
    {
        return [
            [
                'category_key'   => 'soulmate_sync',
                'category_name'  => 'Soulmate Connection (90–100%)',
                'min_score'      => 90,
                'max_score'      => 100,
                'title'          => '{groom_name} ❤️ {bride_name} — Chemistry Story',
                'tagline'        => 'Your Koodal Vibe Score: {score}% — {category_name}',
                'story_content'  => "சில connections சின்ன சின்ன விஷயங்கள்லதான் ஆரம்பிக்கும்… ஒரே மாதிரியான choices, பிடிச்ச interests, ஒருத்தரை ஒருத்தர் புரிஞ்சுக்கிற அந்த அழகான feeling! ❤️\n\n{teamwork_detail}\n\n{observation_memory_story}\n\n{food_location_story}",
                'call_to_action' => '❤️ CONNECT & CHAT NOW',
                'cta_url'        => '/messages',
                'is_active'      => true,
                'sort_order'     => 1,
            ],
            [
                'category_key'   => 'strong_connection',
                'category_name'  => 'Strong Connection (80–89%)',
                'min_score'      => 80,
                'max_score'      => 89,
                'title'          => '{groom_name} ❤️ {bride_name} — Chemistry Story',
                'tagline'        => 'Your Koodal Vibe Score: {score}% — {category_name}',
                'story_content'  => "ஒருத்தரை ஒருத்தர் புரிஞ்சுக்கிறதுலயும், சின்ன சின்ன விஷயங்களை ரசிக்கிறதுலயும் உங்க ரெண்டு பேருக்குள்ளயும் ஒரு சூப்பரான spark தெரியுது! ✨\n\n{teamwork_detail}\n\n{observation_memory_story}\n\n{food_location_story}",
                'call_to_action' => '❤️ CONNECT NOW',
                'cta_url'        => '/messages',
                'is_active'      => true,
                'sort_order'     => 2,
            ],
            [
                'category_key'   => 'sweet_harmony',
                'category_name'  => 'Sweet Harmony (65–79%)',
                'min_score'      => 65,
                'max_score'      => 79,
                'title'          => '{groom_name} ❤️ {bride_name} — Chemistry Story',
                'tagline'        => 'Your Koodal Vibe Score: {score}% — {category_name}',
                'story_content'  => "ஒரே மாதிரியான எண்ணங்களும், ஒருத்தர் விருப்பத்தை இன்னொருத்தர் மதிக்கிற குணமும் சேர்ந்ததுதான் ஒரு அழகான உறவின் தொடக்கம்! 🌸\n\n{teamwork_detail}\n\n{observation_memory_story}\n\n{food_location_story}",
                'call_to_action' => '💬 START CONVERSATION',
                'cta_url'        => '/messages',
                'is_active'      => true,
                'sort_order'     => 3,
            ],
            [
                'category_key'   => 'opposites_attract',
                'category_name'  => 'Unique Spark & Complementary Pair (0–64%)',
                'min_score'      => 0,
                'max_score'      => 64,
                'title'          => '{groom_name} ❤️ {bride_name} — Chemistry Story',
                'tagline'        => 'Your Koodal Vibe Score: {score}% — {category_name}',
                'story_content'  => "ரெண்டு பேரும் வேற வேற ரசனைகள் கொண்டவங்களா இருந்தாலும், ஒருத்தர் உலகத்தை இன்னொருத்தருக்கு அறிமுகப்படுத்துறதுலதான் காதலின் அழகே இருக்கு! 🌟\n\n{teamwork_detail}\n\n{observation_memory_story}\n\n{food_location_story}",
                'call_to_action' => '🎮 PLAY & CONNECT',
                'cta_url'        => '/messages',
                'is_active'      => true,
                'sort_order'     => 4,
            ],
        ];
    }
}
