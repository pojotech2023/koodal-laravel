<?php

namespace App\Support;

/**
 * Raasi and nakshatra names as members actually have them stored. The
 * website saves the config names ("Vrishabha (Taurus)", "Dhanishtha"), the
 * app its own keys ("Rishabha", "Dhanishta"), and people type Tamil or the
 * zodiac sign. All of them resolve to a position in
 * config('matrimony.astrology.*') — or to null when the value isn't one of
 * them, never to a default sign, so Porutham is not worked out on a guess.
 *
 * Matching is on whole names only: "Purva Ashadha" must not match "Purva
 * Phalguni" because the first word is the same.
 */
class Astrology
{
    /** Other spellings, per position in config('matrimony.astrology.raasis'). */
    private const RAASI_ALIASES = [
        ['mesham', 'மேஷம்'],
        ['rishabha', 'rishabam', 'rishabham', 'vrishabham', 'இடபம்', 'ரிஷபம்'],
        ['mithunam', 'மிதுனம்'],
        ['karka', 'kataka', 'karkata', 'kadagam', 'katakam', 'கடகம்'],
        ['simham', 'simmam', 'சிம்மம்'],
        ['kanni', 'கன்னி'],
        ['thula', 'thulam', 'tulam', 'துலாம்'],
        ['vrishchika', 'vrischikam', 'vrishchikam', 'viruchigam', 'virichigam', 'விருச்சிகம்'],
        ['dhanu', 'dhanusu', 'தனுசு'],
        ['makaram', 'மகரம்'],
        ['kumbham', 'kumbam', 'கும்பம்'],
        ['meenam', 'மீனம்'],
    ];

    private const RAASI_TA = [
        'மேஷம்', 'ரிஷபம்', 'மிதுனம்', 'கடகம்', 'சிம்மம்', 'கன்னி',
        'துலாம்', 'விருச்சிகம்', 'தனுசு', 'மகரம்', 'கும்பம்', 'மீனம்',
    ];

    /** Other spellings, per position in config('matrimony.astrology.nakshatras'). */
    private const STAR_ALIASES = [
        ['aswini', 'asvini', 'அஸ்வினி', 'அசுவினி'],
        ['பரணி'],
        ['karthigai', 'kartigai', 'kiruthigai', 'krithika', 'கார்த்திகை', 'கிருத்திகை'],
        ['ரோகிணி'],
        ['mrigasira', 'mrigasheersham', 'mirugasirisham', 'மிருகசீரிஷம்', 'மிருகசீரிடம்'],
        ['arudra', 'thiruvathirai', 'திருவாதிரை'],
        ['punarpoosam', 'புனர்பூசம்'],
        ['pushyami', 'poosam', 'பூசம்'],
        ['aslesha', 'ayilyam', 'ஆயில்யம்'],
        ['makam', 'magam', 'மகம்'],
        ['poorva phalguni', 'pooram', 'பூரம்'],
        ['uthiram', 'uthram', 'உத்திரம்'],
        ['hastham', 'அஸ்தம்', 'ஹஸ்தம்'],
        ['chithirai', 'chitirai', 'சித்திரை'],
        ['swathi', 'சுவாதி'],
        ['visakha', 'visakam', 'விசாகம்'],
        ['anusham', 'அனுஷம்', 'அனுசம்'],
        ['jyeshta', 'kettai', 'கேட்டை'],
        ['moola', 'moolam', 'மூலம்'],
        ['poorva ashadha', 'pooradam', 'பூராடம்'],
        ['uthiradam', 'உத்திராடம்'],
        ['sravana', 'thiruvonam', 'திருவோணம்'],
        ['dhanishta', 'avittam', 'அவிட்டம்'],
        ['satabhisha', 'sathayam', 'sadhayam', 'சதயம்'],
        ['poorva bhadrapada', 'poorattathi', 'pooratathi', 'பூரட்டாதி'],
        ['uthirattathi', 'uthiratathi', 'உத்திரட்டாதி'],
        ['revathi', 'ரேவதி'],
    ];

    private const STAR_TA = [
        'அஸ்வினி', 'பரணி', 'கார்த்திகை', 'ரோகிணி', 'மிருகசீரிஷம்', 'திருவாதிரை', 'புனர்பூசம்',
        'பூசம்', 'ஆயில்யம்', 'மகம்', 'பூரம்', 'உத்திரம்', 'அஸ்தம்', 'சித்திரை', 'சுவாதி',
        'விசாகம்', 'அனுஷம்', 'கேட்டை', 'மூலம்', 'பூராடம்', 'உத்திராடம்', 'திருவோணம்',
        'அவிட்டம்', 'சதயம்', 'பூரட்டாதி', 'உத்திரட்டாதி', 'ரேவதி',
    ];

    /** @var array<string, array<string, int>> */
    private static array $lookup = [];

    public static function raasiIndex(?string $value): ?int
    {
        return self::find('raasis', $value);
    }

    public static function starIndex(?string $value): ?int
    {
        return self::find('nakshatras', $value);
    }

    /** The config name ("Vrishabha (Taurus)") for a stored value, or null. */
    public static function raasi(?string $value): ?string
    {
        $i = self::raasiIndex($value);

        return $i === null ? null : config('matrimony.astrology.raasis')[$i];
    }

    public static function star(?string $value): ?string
    {
        $i = self::starIndex($value);

        return $i === null ? null : config('matrimony.astrology.nakshatras')[$i];
    }

    public static function raasiTamil(int $index): string
    {
        return self::RAASI_TA[$index];
    }

    public static function starTamil(int $index): string
    {
        return self::STAR_TA[$index];
    }

    /** Whether `$value` is one of `$wanted`, however either side is spelled. */
    public static function raasiIn(?string $value, array $wanted): bool
    {
        return self::in('raasis', $value, $wanted);
    }

    public static function starIn(?string $value, array $wanted): bool
    {
        return self::in('nakshatras', $value, $wanted);
    }

    private static function in(string $list, ?string $value, array $wanted): bool
    {
        $i = self::find($list, $value);

        if ($i === null) {
            return false;
        }

        foreach ($wanted as $w) {
            if (is_string($w) && self::find($list, $w) === $i) {
                return true;
            }
        }

        return false;
    }

    private static function find(string $list, ?string $value): ?int
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        $map = self::$lookup[$list] ??= self::build($list);

        // "Mesham / Aries (மேஷம்)" is three names; any one of them will do.
        foreach (preg_split('~[()/,|]~u', $value) as $part) {
            $key = self::key($part);
            if ($key !== '' && isset($map[$key])) {
                return $map[$key];
            }
        }

        return null;
    }

    /** @return array<string, int> */
    private static function build(string $list): array
    {
        $aliases = $list === 'raasis' ? self::RAASI_ALIASES : self::STAR_ALIASES;
        $map = [];

        foreach (config("matrimony.astrology.{$list}") as $i => $name) {
            // The config name and its bracketed sign: "Mesha (Aries)".
            foreach ([...preg_split('~[()]~u', $name), ...$aliases[$i]] as $alias) {
                $key = self::key($alias);
                if ($key !== '') {
                    $map[$key] = $i;
                }
            }
        }

        return $map;
    }

    /** Lower case, letters only, no spaces: "Purva-Ashadha " → "purvaashadha". */
    private static function key(string $name): string
    {
        return preg_replace('~[^\p{L}\p{M}]+~u', '', mb_strtolower(trim($name)));
    }
}
