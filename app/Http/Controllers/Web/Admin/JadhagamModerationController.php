<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\UserJadhagam;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class JadhagamModerationController extends Controller
{
    public function index(Request $request): View
    {
        $status = $request->string('status')->toString() ?: 'pending';

        $jadhagams = UserJadhagam::query()
            ->where('approval_status', $status)
            ->with(['user:id,profile_code,name,gender', 'moderator:id,name'])
            ->latest('created_at')
            ->paginate(30)
            ->withQueryString();

        $pendingCount = UserJadhagam::query()->where('approval_status', 'pending')->count();
        $approvedCount = UserJadhagam::query()->where('approval_status', 'approved')->count();
        $rejectedCount = UserJadhagam::query()->where('approval_status', 'rejected')->count();

        $reasons = [
            'Image is blurry or illegible',
            'Not a horoscope / Jadhagam chart',
            'Personal contact details or phone number visible',
            'Incorrect orientation or cropped',
            'Duplicate or invalid document',
        ];

        return view('admin.jadhagam.index', [
            'jadhagams' => $jadhagams,
            'status' => $status,
            'reasons' => $reasons,
            'pendingCount' => $pendingCount,
            'approvedCount' => $approvedCount,
            'rejectedCount' => $rejectedCount,
        ]);
    }

    public function approve(Request $request, UserJadhagam $jadhagam): RedirectResponse
    {
        $jadhagam->update([
            'approval_status' => 'approved',
            'rejection_reason' => null,
            'moderated_by' => $request->user()?->id,
            'moderated_at' => now(),
        ]);

        try {
            app(\App\Services\FirebaseNotificationService::class)->sendToUser(
                $jadhagam->user_id,
                'Jadhagam Approved',
                'Your jadhagam has been approved. It is now visible on your profile.',
                [
                    'type' => 'jadhagam_approved',
                    'reference_id' => (string) $jadhagam->id,
                    'jadhagam_id' => (string) $jadhagam->id,
                ],
                'jadhagam_approved'
            );
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Jadhagam approval FCM error: '.$e->getMessage());
        }

        return back()->with('status', 'Jadhagam approved. It is now visible on the member profile.');
    }

    public function reject(Request $request, UserJadhagam $jadhagam): RedirectResponse
    {
        $request->validate([
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        $reason = $request->input('reason', 'Image unclear or invalid horoscope chart.');

        $jadhagam->update([
            'approval_status' => 'rejected',
            'rejection_reason' => $reason,
            'moderated_by' => $request->user()?->id,
            'moderated_at' => now(),
        ]);

        try {
            app(\App\Services\FirebaseNotificationService::class)->sendToUser(
                $jadhagam->user_id,
                'Jadhagam Rejected',
                $reason ?: 'Your jadhagam was rejected. Please upload a clear horoscope and try again.',
                [
                    'type' => 'jadhagam_rejected',
                    'reference_id' => (string) $jadhagam->id,
                    'jadhagam_id' => (string) $jadhagam->id,
                    'reason' => (string) $reason,
                ],
                'jadhagam_rejected'
            );
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Jadhagam rejection FCM error: '.$e->getMessage());
        }

        return back()->with('status', 'Jadhagam rejected. The member will be notified.');
    }
}
