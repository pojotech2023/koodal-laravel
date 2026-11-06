<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\MatchInvitation;
use App\Services\InvitationService;
use Illuminate\Http\Request;

class InvitationController extends Controller
{
    public function __construct(protected InvitationService $invitationService) {}

    public function show(Request $request, string $token)
    {
        if (!$request->hasValidSignature()) {
            abort(401, 'Invalid or expired invitation signature.');
        }

        $invitation = MatchInvitation::where('token', $token)->firstOrFail();

        if ($invitation->expires_at->isPast()) {
            abort(410, 'This invitation link has expired.');
        }

        if (!auth()->check()) {
            session(['pending_invitation_token' => $token]);
            return redirect()->route('login')->with([
                'email' => $invitation->email,
                'info' => 'Please log in or register with email ' . $invitation->email . ' to accept your game invitation.',
            ]);
        }

        $user = auth()->user();

        if (strtolower($user->email) !== strtolower($invitation->email)) {
            abort(403, 'This invitation was sent to ' . $invitation->email . '. You are logged in as ' . $user->email . '.');
        }

        $match = $this->invitationService->acceptInvitation($invitation, $user);

        return redirect()->route('spot.matches.show', $match->id)
            ->with('success', 'You have joined the match lobby!');
    }
}
