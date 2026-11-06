<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Banner;
use App\Models\Setting;
use App\Models\SuccessStory;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function index(): View
    {
        return view('home', [
            // 'home_hero' / 'home_mid' / 'app_home' are the three seeded
            // positions (see the banners migration) — the marketing home
            // page reads the first two.
            'banners' => Banner::live()->whereIn('position', ['home_hero', 'home_mid'])->get(),
            'stories' => SuccessStory::published()->where('is_featured', true)->take(6)->get(),
            'settings' => Setting::publicValues(),
        ]);
    }
}
