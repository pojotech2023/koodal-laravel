<?php

namespace App\Http\Controllers\Api\V1\User;

use App\Actions\Interest\AcceptInterest;
use App\Actions\Interest\DeclineInterest;
use App\Actions\Interest\SendInterest;
use App\Http\Controllers\Controller;
use App\Http\Requests\Interest\SendInterestRequest;
use App\Http\Resources\InterestResource;
use App\Models\Interest;
use App\Models\User;
use App\Models\UserProfile;
use App\Services\Profile\ViewerState;
use App\Support\ApiResponse;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Thin by design: validate, call one action, return one resource. Every
 * business rule — quota, visibility, duplicate handling, auto-match — lives in
 * the action, so the website, the app and any future client get identical
 * behaviour.
 */
class InterestController extends Controller
{
    public function __construct(protected ViewerState $viewerState) {}

    public function sent(Request $request): JsonResponse
    {
        $interests = $request->user()->sentInterests()
            ->status($request->query('status'))
            // Eager-loaded here, never inside the Resource — a Resource that
            // queries is an N+1 waiting to happen.
            ->with($this->counterpartRelations('receiver'))
            ->latest()
            ->paginate($request->integer('per_page', 20));

        return $this->respond($interests, $request, 'receiver', 'Sent interests retrieved successfully');
    }

    public function received(Request $request): JsonResponse
    {
        $interests = $request->user()->receivedInterests()
            ->status($request->query('status'))
            ->with($this->counterpartRelations('sender'))
            ->latest()
            ->paginate($request->integer('per_page', 20));

        return $this->respond($interests, $request, 'sender', 'Received interests retrieved successfully');
    }

    public function store(SendInterestRequest $request, SendInterest $sendInterest): JsonResponse
    {
        $interest = $sendInterest(
            sender: $request->user(),
            receiver: User::findOrFail($request->integer('receiver_id')),
            message: $request->input('message'),
        );

        // On the auto-match path the row that comes back is the RECIPROCAL
        // interest — the one they sent us — so the counterpart the resource
        // will render is its sender, not its receiver. Loading the wrong side
        // would drop the profile from the 201 at exactly the moment the client
        // wants to show who it just connected the user with.
        $counterpart = $interest->sender_id === $request->user()->id ? 'receiver' : 'sender';

        return ApiResponse::created(
            new InterestResource($interest->load($this->counterpartRelations($counterpart))),
            $interest->status === 'accepted'
                ? 'You both expressed interest — you are now connected!'
                : 'Interest sent successfully',
        );
    }

    public function accept(Interest $interest, AcceptInterest $acceptInterest, Request $request): JsonResponse
    {
        $accepted = $acceptInterest($interest, $request->user());

        return ApiResponse::success(
            new InterestResource($accepted->load($this->counterpartRelations('sender'))),
            'Interest accepted. You can now start a conversation.',
        );
    }

    public function decline(Interest $interest, DeclineInterest $declineInterest, Request $request): JsonResponse
    {
        $declined = $declineInterest($interest, $request->user());

        return ApiResponse::success(
            new InterestResource($declined->load($this->counterpartRelations('sender'))),
            'Interest declined',
        );
    }

    public function cancel(Interest $interest, Request $request): JsonResponse
    {
        abort_unless($interest->sender_id === $request->user()->id, 403);
        abort_unless($interest->isPending(), 409, 'This interest has already been answered.');

        $interest->update(['status' => 'cancelled']);

        return ApiResponse::success(null, 'Interest cancelled');
    }

    // --- Internals ---------------------------------------------------------

    /**
     * Everything ProfileResource reads, eager-loaded here rather than in the
     * resource. The `profile.user` hop matters more than it looks: the resource
     * reads `$this->user` for the name, the age and the privacy row on every
     * single field it gates, and without it preventLazyLoading throws on the
     * first row of the list.
     *
     * @return array<int, string>
     */
    protected function counterpartRelations(string $relation): array
    {
        return array_merge(
            [
                $relation.'.profile',
                $relation.'.profile.primaryPhoto',
                $relation.'.profile.user',
                $relation.'.profile.user.privacySettings',
            ],
            array_map(fn (string $lookup) => $relation.'.profile.'.$lookup, UserProfile::WITH_LOOKUPS),
        );
    }

    /**
     * Hydrates viewer_shortlisted / viewer_interest_status / viewer_is_matched
     * for the whole page in three queries, so the heart and the button on each
     * card render in their final state instead of flickering after mount.
     */
    protected function respond(LengthAwarePaginator $page, Request $request, string $relation, string $message): JsonResponse
    {
        $profiles = collect($page->items())
            ->map(fn (Interest $row) => $row->{$relation}?->profile)
            ->filter();

        $this->viewerState->hydrate($profiles, $request->user());

        return ApiResponse::paginated($page, InterestResource::class, $message);
    }
}
