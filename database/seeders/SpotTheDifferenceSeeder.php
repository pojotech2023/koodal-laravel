<?php

namespace Database\Seeders;

use App\Models\Puzzle;
use App\Models\PuzzleDifference;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

class SpotTheDifferenceSeeder extends Seeder
{
    public function run(): void
    {
        Storage::disk('public')->makeDirectory('puzzles');

        $origPath = 'puzzles/enchanted_garden_orig.png';
        $modPath = 'puzzles/enchanted_garden_mod.png';

        // Generate sample 800x600 images using GD if extension loaded, else simple SVG/PNG placeholder
        if (extension_loaded('gd')) {
            $w = 800;
            $h = 600;

            // Original Image
            $imgOrig = imagecreatetruecolor($w, $h);
            $bg = imagecolorallocate($imgOrig, 240, 248, 255);
            $green = imagecolorallocate($imgOrig, 34, 139, 34);
            $sun = imagecolorallocate($imgOrig, 255, 215, 0);
            $blue = imagecolorallocate($imgOrig, 70, 130, 180);
            $red = imagecolorallocate($imgOrig, 220, 20, 60);
            $purple = imagecolorallocate($imgOrig, 147, 112, 219);
            $orange = imagecolorallocate($imgOrig, 255, 140, 0);

            imagefilledrectangle($imgOrig, 0, 0, $w, $h, $bg);
            imagefilledellipse($imgOrig, 150, 120, 100, 100, $sun); // Sun
            imagefilledrectangle($imgOrig, 0, 400, $w, $h, $green); // Grass

            // Draw objects on original
            imagefilledellipse($imgOrig, 100, 480, 50, 50, $red); // Flower 1 (x:12.5%, y:80%)
            imagefilledellipse($imgOrig, 250, 470, 40, 40, $purple); // Butterfly (x:31.25%, y:78.33%)
            imagefilledrectangle($imgOrig, 400, 320, 450, 400, $blue); // House (x:53.12%, y:60%)
            imagefilledellipse($imgOrig, 600, 200, 60, 60, $orange); // Bird (x:75%, y:33.33%)
            imagefilledellipse($imgOrig, 700, 490, 45, 45, $red); // Apple (x:87.5%, y:81.67%)
            imagefilledrectangle($imgOrig, 550, 450, 580, 500, $purple); // Bench (x:70.62%, y:79.16%)
            imagefilledellipse($imgOrig, 320, 150, 70, 40, $blue); // Cloud (x:43.12%, y:25%)

            // Modified Image (differences added/removed/changed)
            $imgMod = imagecreatetruecolor($w, $h);
            imagecopy($imgMod, $imgOrig, 0, 0, 0, 0, $w, $h);

            $white = imagecolorallocate($imgMod, 240, 248, 255);
            $grassGreen = imagecolorallocate($imgMod, 34, 139, 34);
            $pink = imagecolorallocate($imgMod, 255, 105, 180);
            $yellow = imagecolorallocate($imgMod, 255, 255, 0);

            // Diff 1: Flower color changed to pink (x:12.5%, y:80%)
            imagefilledellipse($imgMod, 100, 480, 50, 50, $pink);

            // Diff 2: Butterfly removed (overwritten with grass) (x:31.25%, y:78.33%)
            imagefilledellipse($imgMod, 250, 470, 50, 50, $grassGreen);

            // Diff 3: House roof added yellow chimney (x:53.12%, y:60%)
            imagefilledrectangle($imgMod, 430, 290, 450, 330, $yellow);

            // Diff 4: Bird missing (x:75%, y:33.33%)
            imagefilledellipse($imgMod, 600, 200, 70, 70, $white);

            // Diff 5: Apple missing (x:87.5%, y:81.67%)
            imagefilledellipse($imgMod, 700, 490, 55, 55, $grassGreen);

            // Diff 6: Bench changed color to pink (x:70.62%, y:79.16%)
            imagefilledrectangle($imgMod, 550, 450, 580, 500, $pink);

            // Diff 7: Cloud missing (x:43.12%, y:25%)
            imagefilledellipse($imgMod, 320, 150, 80, 50, $white);

            ob_start();
            imagepng($imgOrig);
            $origContent = ob_get_clean();
            imagedestroy($imgOrig);

            ob_start();
            imagepng($imgMod);
            $modContent = ob_get_clean();
            imagedestroy($imgMod);

            Storage::disk('public')->put($origPath, $origContent);
            Storage::disk('public')->put($modPath, $modContent);
        }

        $puzzle = Puzzle::updateOrCreate(
            ['title' => 'Enchanted Garden'],
            [
                'original_image' => $origPath,
                'modified_image' => $modPath,
                'width' => 800,
                'height' => 600,
                'difficulty' => 'medium',
                'time_limit_seconds' => 180,
                'is_active' => true,
            ]
        );

        $differences = [
            [
                'label' => 'Magic Flower Color',
                'x_pct' => 12.5000,
                'y_pct' => 80.0000,
                'radius_pct' => 6.0000,
                'animation_type' => 'pulse',
                'animate_on' => 'modified',
                'sort_order' => 1,
            ],
            [
                'label' => 'Vanishing Butterfly',
                'x_pct' => 31.2500,
                'y_pct' => 78.3300,
                'radius_pct' => 6.0000,
                'animation_type' => 'fly_away',
                'animate_on' => 'original',
                'sort_order' => 2,
            ],
            [
                'label' => 'House Chimney',
                'x_pct' => 53.1200,
                'y_pct' => 55.0000,
                'radius_pct' => 5.5000,
                'animation_type' => 'swing_open',
                'animate_on' => 'modified',
                'sort_order' => 3,
            ],
            [
                'label' => 'Flying Bird',
                'x_pct' => 75.0000,
                'y_pct' => 33.3300,
                'radius_pct' => 6.5000,
                'animation_type' => 'drift',
                'animate_on' => 'original',
                'sort_order' => 4,
            ],
            [
                'label' => 'Golden Apple',
                'x_pct' => 87.5000,
                'y_pct' => 81.6700,
                'radius_pct' => 5.5000,
                'animation_type' => 'fall',
                'animate_on' => 'original',
                'sort_order' => 5,
            ],
            [
                'label' => 'Garden Bench Color',
                'x_pct' => 70.6200,
                'y_pct' => 79.1600,
                'radius_pct' => 6.0000,
                'animation_type' => 'blink',
                'animate_on' => 'modified',
                'sort_order' => 6,
            ],
            [
                'label' => 'Floating Cloud',
                'x_pct' => 43.1200,
                'y_pct' => 25.0000,
                'radius_pct' => 7.0000,
                'animation_type' => 'fade_out',
                'animate_on' => 'original',
                'sort_order' => 7,
            ],
        ];

        foreach ($differences as $diffData) {
            PuzzleDifference::updateOrCreate(
                [
                    'puzzle_id' => $puzzle->id,
                    'label' => $diffData['label'],
                ],
                $diffData
            );
        }
    }
}
