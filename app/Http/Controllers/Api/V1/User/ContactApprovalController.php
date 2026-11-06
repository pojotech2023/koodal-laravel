<?php

namespace App\Http\Controllers\Api\V1\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\Contact\RequestContactAccessRequest;
use App\Http\Requests\Contact\RespondContactAccessRequest;
use App\Http\Resources\ContactApprovalResource;
use App\Models\ContactApproval;
use App\Models\User;
use App\Models\UserProfile;
use App\Services\FirebaseNotificationService;
use App\Services\Profile\ViewerState;
use App\Support\ApiResponse;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ContactApprovalController extends Controller
{
    public function __construct(protected FirebaseNotificationService $fcm) {}

    /**
     * Request access to view another member's contact details.
     */
    public function requestAccess(RequestContactAccessRequest $request): JsonResponse
    {
        $requester = $request->user();

        $ownerId = $request->integer('owner_id');
        if (! $ownerId && $request->filled('profile_code')) {
            $ownerId = (int) User::where('profile_code', $request->string('profile_code'))->value('id');
        }

        abort_unless($ownerId, 404, 'Profile owner not found.');
        abort_if($requester->id === $ownerId, 422, 'You cannot request contact access for your own profile.');

        $owner = User::findOrFail($ownerId);

        $approval = ContactApproval::where('requester_id', $requester->id)
            ->where('owner_id', $owner->id)
            ->first();

        if ($approval) {
            if ($approval->status === 'approved') {
                return ApiResponse::success($approval, 'Contact access has already been approved.');
            }

            if ($approval->status === 'pending') {
                return ApiResponse::success($approval, 'Contact request is already pending approval.');
            }

            // Re-request if previously rejected
            $approval->update(['status' => 'pending']);
        } else {
            $approval = ContactApproval::create([
                'requester_id' => $requester->id,
                'owner_id' => $owner->id,
                'status' => 'pending',
            ]);
        }

        // Send push notification to owner
        try {
            $this->fcm->sendToUser(
                $owner->id,
                'Contact Request',
                "{$requester->name} has requested access to view your contact details.",
                [
                    'type' => 'contact_request',
                    'reference_id' => (string) $approval->id,
                    'requester_id' => (string) $requester->id,
                    'requester_code' => (string) $requester->profile_code,
                ],
                'contact_request'
            );
        } catch (\Throwable $e) {
            Log::warning('Contact request push failed: '.$e->getMessage());
        }

        return ApiResponse::created($approval, 'Contact request sent successfully');
    }

    /**
     * Approve or reject a contact request.
     */
    public function respond(RespondContactAccessRequest $request): JsonResponse
    {
        $owner = $request->user();
        $status = $request->string('status')->toString();

        $query = ContactApproval::where('owner_id', $owner->id);

        if ($request->filled('request_id')) {
            $query->where('id', $request->integer('request_id'));
        } elseif ($request->filled('requester_id')) {
            $query->where('requester_id', $request->integer('requester_id'));
        }

        $approval = $query->first();
        abort_unless($approval, 404, 'Contact request not found.');

        $approval->update(['status' => $status]);

        $requester = User::find($approval->requester_id);

        if ($requester) {
            try {
                if ($status === 'approved') {
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
                } else {
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
                }
            } catch (\Throwable $e) {
                Log::warning('Contact response push failed: '.$e->getMessage());
            }
        }

        return ApiResponse::success($approval, "Contact request {$status} successfully");
    }

    /**
     * List pending contact requests received by the current user.
     */
    public function pendingRequests(Request $request): JsonResponse
    {
        $user = $request->user();

        $requests = ContactApproval::query()
            ->where('owner_id', $user->id)
            ->where('status', 'pending')
            ->with($this->cardRelations('requester'))
            ->latest()
            ->paginate(min(50, max(1, $request->integer('per_page', 20))));

        return $this->page($requests, $request, 'requester', 'Pending contact requests retrieved successfully');
    }

    /**
     * List approved contact requests: `type=sent` (default) — members who
     * approved mine, with their contact details; `type=received` — members I
     * approved.
     */
    public function approvedRequests(Request $request): JsonResponse
    {
        $user = $request->user();
        $received = $request->string('type')->toString() === 'received';
        $relation = $received ? 'requester' : 'owner';

        $approvals = ContactApproval::query()
            ->where('status', 'approved')
            ->where($received ? 'owner_id' : 'requester_id', $user->id)
            ->with($this->cardRelations($relation))
            ->latest('updated_at')
            ->paginate(min(50, max(1, $request->integer('per_page', 20))));

        return $this->page($approvals, $request, $relation, 'Approved contact requests retrieved successfully');
    }

    /**
     * Everything ProfileResource reads, loaded up front (preventLazyLoading
     * throws outside production otherwise) — the same set ProfileViewController
     * loads for its cards.
     */
    protected function cardRelations(string $relation): array
    {
        return array_merge(
            [
                $relation,
                $relation.'.profile',
                $relation.'.profile.primaryPhoto',
                $relation.'.profile.user',
                $relation.'.profile.user.privacySettings',
            ],
            array_map(fn (string $lookup) => $relation.'.profile.'.$lookup, UserProfile::WITH_LOOKUPS),
        );
    }

    protected function page(LengthAwarePaginator $page, Request $request, string $relation, string $message): JsonResponse
    {
        $profiles = collect($page->items())
            ->map(fn (ContactApproval $row) => $row->{$relation}?->profile)
            ->filter();

        app(ViewerState::class)->hydrate($profiles, $request->user());

        return ApiResponse::paginated($page, ContactApprovalResource::class, $message);
    }
}
