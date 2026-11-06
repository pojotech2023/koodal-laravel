<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\ContactApproval;
use App\Services\FirebaseNotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class ContactApprovalWebController extends Controller
{
    public function __construct(protected FirebaseNotificationService $fcm) {}

    public function index(Request $request): View
    {
        $user = $request->user();
        $tab = $request->query('tab', 'pending');

        $pending = ContactApproval::where('owner_id', $user->id)
            ->where('status', 'pending')
            ->with(['requester.profile.primaryPhoto', 'requester.profile.city', 'requester.profile.occupation'])
            ->latest()
            ->paginate(12, ['*'], 'pending_page');

        $approved = ContactApproval::where('owner_id', $user->id)
            ->where('status', 'approved')
            ->with(['requester.profile.primaryPhoto', 'requester.profile.city', 'requester.profile.occupation'])
            ->latest()
            ->paginate(12, ['*'], 'approved_page');

        $sent = ContactApproval::where('requester_id', $user->id)
            ->with(['owner.profile.primaryPhoto', 'owner.profile.city', 'owner.profile.occupation'])
            ->latest()
            ->paginate(12, ['*'], 'sent_page');

        return view('contact_requests.index', compact('user', 'tab', 'pending', 'approved', 'sent'));
    }

    public function approve(ContactApproval $approval, Request $request): RedirectResponse
    {
        abort_unless($approval->owner_id === $request->user()->id, 403, 'Unauthorized action.');

        $approval->update(['status' => 'approved']);

        $requester = $approval->requester;
        $owner = $request->user();

        if ($requester) {
            try {
                $this->fcm->sendToUser(
                    $requester->id,
                    'Contact Request Approved',
                    "{$owner->name} approved your contact request. You can now view their contact details.",
                    [
                        'type' => 'contact_approved',
                        'reference_id' => (string) $approval->id,
                        'owner_id' => (string) $owner->id,
                        'owner_code' => (string) $owner->profile_code,
                    ],
                    'contact_approved'
                );
            } catch (\Throwable $e) {
                Log::warning('Web contact approve push failed: '.$e->getMessage());
            }
        }

        return back()->with('success', "Contact visibility approved for {$requester?->name}.");
    }

    public function reject(ContactApproval $approval, Request $request): RedirectResponse
    {
        abort_unless($approval->owner_id === $request->user()->id, 403, 'Unauthorized action.');

        $approval->update(['status' => 'rejected']);

        $requester = $approval->requester;
        $owner = $request->user();

        if ($requester) {
            try {
                $this->fcm->sendToUser(
                    $requester->id,
                    'Contact Request Declined',
                    "{$owner->name} declined your contact request.",
                    [
                        'type' => 'contact_rejected',
                        'reference_id' => (string) $approval->id,
                        'owner_id' => (string) $owner->id,
                    ],
                    'contact_rejected'
                );
            } catch (\Throwable $e) {
                Log::warning('Web contact reject push failed: '.$e->getMessage());
            }
        }

        return back()->with('success', "Contact request declined for {$requester?->name}.");
    }
}
