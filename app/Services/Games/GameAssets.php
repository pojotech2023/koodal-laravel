<?php

namespace App\Services\Games;

use Illuminate\Support\Facades\Storage;

/**
 * Game content is stored three different ways: a public-disk path
 * (`puzzles/a.png`), a site-relative path (`/image/02_Koodal_Find_It.png`) and
 * a full URL. A phone has no "site root", so everything leaves the API absolute.
 */
class GameAssets
{
    public static function url(?string $path): ?string
    {
        if ($path === null || $path === '') {
            return null;
        }

        if (preg_match('#^https?://#i', $path)) {
            return $path;
        }

        if (str_starts_with($path, '/')) {
            return url($path);
        }

        return Storage::disk('public')->url($path);
    }
}
