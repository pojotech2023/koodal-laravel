<?php

namespace App\Services;

use Intervention\Image\Drivers\Gd\Driver as GdDriver;
use Intervention\Image\ImageManager;

class PuzzleImageService
{
    /**
     * Get dimensions [width, height] of an uploaded file or path using Intervention Image or getimagesize.
     */
    public static function getImageDimensions(string $path): array
    {
        if (function_exists('getimagesize')) {
            $info = @getimagesize($path);
            if ($info) {
                return [$info[0], $info[1]];
            }
        }

        try {
            $manager = new ImageManager(new GdDriver());
            $img = $manager->read($path);
            return [$img->width(), $img->height()];
        } catch (\Throwable $e) {
            return [0, 0];
        }
    }

    /**
     * Compare original and modified image and suggest hotspot clusters in percentage coordinates.
     * Uses GD to compute absolute pixel differences and cluster them.
     */
    public static function suggestHotspots(string $originalPath, string $modifiedPath): array
    {
        if (!extension_loaded('gd')) {
            return [];
        }

        $orig = @imagecreatefromstring(file_get_contents($originalPath));
        $mod = @imagecreatefromstring(file_get_contents($modifiedPath));

        if (!$orig || !$mod) {
            return [];
        }

        $w = imagesx($orig);
        $h = imagesy($orig);

        if ($w !== imagesx($mod) || $h !== imagesy($mod)) {
            imagedestroy($orig);
            imagedestroy($mod);
            return [];
        }

        // Downsample grid for performance (grid of cells e.g. 20x20px)
        $cellSize = max(10, (int) min($w / 40, $h / 40));
        $gridX = ceil($w / $cellSize);
        $gridY = ceil($h / $cellSize);

        $diffPoints = [];

        for ($gy = 0; $gy < $gridY; $gy++) {
            for ($gx = 0; $gx < $gridX; $gx++) {
                $cx = (int) min($w - 1, ($gx + 0.5) * $cellSize);
                $cy = (int) min($h - 1, ($gy + 0.5) * $cellSize);

                $rgb1 = imagecolorat($orig, $cx, $cy);
                $rgb2 = imagecolorat($mod, $cx, $cy);

                $r1 = ($rgb1 >> 16) & 0xFF;
                $g1 = ($rgb1 >> 8) & 0xFF;
                $b1 = $rgb1 & 0xFF;

                $r2 = ($rgb2 >> 16) & 0xFF;
                $g2 = ($rgb2 >> 8) & 0xFF;
                $b2 = $rgb2 & 0xFF;

                $diff = abs($r1 - $r2) + abs($g1 - $g2) + abs($b1 - $b2);

                if ($diff > 60) { // Difference threshold
                    $diffPoints[] = ['x' => $cx, 'y' => $cy, 'weight' => $diff];
                }
            }
        }

        imagedestroy($orig);
        imagedestroy($mod);

        if (empty($diffPoints)) {
            return [];
        }

        // Simple clustering of diffPoints into hotspot regions
        $clusters = [];
        $clusterRadius = max(40, $cellSize * 3);

        foreach ($diffPoints as $pt) {
            $foundCluster = false;
            foreach ($clusters as &$cluster) {
                $dist = sqrt(pow($pt['x'] - $cluster['x'], 2) + pow($pt['y'] - $cluster['y'], 2));
                if ($dist <= $clusterRadius) {
                    // Update centroid
                    $totalWeight = $cluster['weight'] + $pt['weight'];
                    $cluster['x'] = ($cluster['x'] * $cluster['weight'] + $pt['x'] * $pt['weight']) / $totalWeight;
                    $cluster['y'] = ($cluster['y'] * $cluster['weight'] + $pt['y'] * $pt['weight']) / $totalWeight;
                    $cluster['count']++;
                    $cluster['weight'] = $totalWeight;
                    $foundCluster = true;
                    break;
                }
            }
            if (!$foundCluster) {
                $clusters[] = [
                    'x' => (float)$pt['x'],
                    'y' => (float)$pt['y'],
                    'count' => 1,
                    'weight' => (float)$pt['weight'],
                ];
            }
        }

        // Convert clusters to percentage coordinates
        $suggestions = [];
        $sortOrder = 1;
        foreach ($clusters as $c) {
            if ($c['count'] >= 2) { // filter out single noisy pixels
                $suggestions[] = [
                    'label' => 'Suggested Spot ' . $sortOrder,
                    'x_pct' => round(($c['x'] / $w) * 100, 4),
                    'y_pct' => round(($c['y'] / $h) * 100, 4),
                    'radius_pct' => 6.0,
                    'animation_type' => 'pulse',
                    'animate_on' => 'modified',
                    'sort_order' => $sortOrder++,
                ];
            }
        }

        return $suggestions;
    }
}
