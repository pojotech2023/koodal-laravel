<?php

namespace Database\Seeders;

use App\Models\FlipSetting;
use App\Models\FlipTileImage;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

class FlipTileImageSeeder extends Seeder
{
    public function run(): void
    {
        // Ensure storage directory exists
        Storage::disk('public')->makeDirectory('flip-tiles');

        $samples = [
            ['name' => 'Red Rose', 'emoji' => '🌹', 'color' => '#f43f5e', 'bg' => '#ffe4e6'],
            ['name' => 'Sunflower', 'emoji' => '🌻', 'color' => '#eab308', 'bg' => '#fef9c3'],
            ['name' => 'Hibiscus', 'emoji' => '🌺', 'color' => '#ec4899', 'bg' => '#fce7f3'],
            ['name' => 'Tulip', 'emoji' => '🌷', 'color' => '#fb7185', 'bg' => '#fff1f2'],
            ['name' => 'Peacock', 'emoji' => '🦚', 'color' => '#0284c7', 'bg' => '#e0f2fe'],
            ['name' => 'Flamingo', 'emoji' => '🦩', 'color' => '#f43f5e', 'bg' => '#ffe4e6'],
            ['name' => 'Parrot', 'emoji' => '🦜', 'color' => '#16a34a', 'bg' => '#dcfce7'],
            ['name' => 'Dove', 'emoji' => '🕊️', 'color' => '#64748b', 'bg' => '#f1f5f9'],
            ['name' => 'Sports Car', 'emoji' => '🏎️', 'color' => '#dc2626', 'bg' => '#fee2e2'],
            ['name' => 'Classic Car', 'emoji' => '🚘', 'color' => '#2563eb', 'bg' => '#dbeafe'],
            ['name' => 'Police Car', 'emoji' => '🚔', 'color' => '#1d4ed8', 'bg' => '#eff6ff'],
            ['name' => 'Taxi', 'emoji' => '🚕', 'color' => '#ca8a04', 'bg' => '#fef08a'],
            ['name' => 'Diamond Gem', 'emoji' => '💎', 'color' => '#0284c7', 'bg' => '#e0f2fe'],
            ['name' => 'Gold Crown', 'emoji' => '👑', 'color' => '#d97706', 'bg' => '#fef3c7'],
            ['name' => 'Ring', 'emoji' => '💍', 'color' => '#4f46e5', 'bg' => '#e0e7ff'],
            ['name' => 'Sparkles', 'emoji' => '✨', 'color' => '#eab308', 'bg' => '#fef9c3'],
            ['name' => 'Strawberry', 'emoji' => '🍓', 'color' => '#e11d48', 'bg' => '#ffe4e6'],
            ['name' => 'Red Apple', 'emoji' => '🍎', 'color' => '#dc2626', 'bg' => '#fee2e2'],
            ['name' => 'Pineapple', 'emoji' => '🍍', 'color' => '#d97706', 'bg' => '#fef3c7'],
            ['name' => 'Mango', 'emoji' => '🥭', 'color' => '#ea580c', 'bg' => '#ffedd5'],
            ['name' => 'Dolphin', 'emoji' => '🐬', 'color' => '#0284c7', 'bg' => '#e0f2fe'],
            ['name' => 'Butterfly', 'emoji' => '🦋', 'color' => '#9333ea', 'bg' => '#f3e8ff'],
            ['name' => 'Gold Star', 'emoji' => '⭐', 'color' => '#eab308', 'bg' => '#fef9c3'],
            ['name' => 'Rainbow', 'emoji' => '🌈', 'color' => '#8b5cf6', 'bg' => '#ddd6fe'],
        ];

        foreach ($samples as $index => $sample) {
            $filename = 'flip-tiles/sample_' . sprintf('%02d', $index + 1) . '.svg';

            $svg = <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="300" height="300" viewBox="0 0 300 300">
  <rect width="300" height="300" rx="36" fill="{$sample['bg']}"/>
  <circle cx="150" cy="150" r="110" fill="#ffffff" opacity="0.8"/>
  <text x="150" y="175" font-size="110" text-anchor="middle" dominant-baseline="central">{$sample['emoji']}</text>
</svg>
SVG;

            Storage::disk('public')->put($filename, $svg);

            FlipTileImage::updateOrCreate(
                ['path' => $filename],
                [
                    'name' => $sample['name'],
                    'is_active' => true,
                ]
            );
        }

        // Initialize default settings
        FlipSetting::instance();
    }
}
