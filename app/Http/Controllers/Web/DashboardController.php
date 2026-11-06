<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Services\Profile\ProfileCompletion;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * The landing page every `auth:web` redirect ends up at
 * (`redirectGuestsTo`'s counterpart, `LoginController`, `OtpController`).
 *
 * Deliberately thin and link-light, same discipline as `<x-footer>`
 * (PROGRESS.md §6): this session only builds the profile wizard and the "my
 * profile" page, so this is the only member-area screen the dashboard may
 * link to yet. Search/matches/interests/shortlist/messages/membership land
 * in a later session and each earns its card here the commit its route is
 * registered — not before.
 */
class DashboardController extends Controller
{
    public function __construct(protected ProfileCompletion $completion) {}

    public function index(Request $request): View
    {
        // Eager-loaded here, not left to ProfileCompletion::report() to lazy
        // load — preventLazyLoading is on outside production (PROGRESS.md
        // §6) and $request->user() always resolves with no relations.
        $user = $request->user()->load('profile');

        return view('dashboard', [
            'user' => $user,
            'completion' => $this->completion->report($user),
        ]);
    }
}
