<?php

namespace App\Http\Controllers\Api\V1\User;

use App\Http\Controllers\Controller;
use App\Http\Resources\MembershipResource;
use App\Http\Resources\PlanResource;
use App\Http\Resources\SubscriptionResource;
use App\Models\MembershipPlan;
use App\Models\User;
use App\Services\Membership\FeatureGate;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MembershipController extends Controller
{
    public function __construct(protected FeatureGate $features) {}

    /**
     * Public — the pricing page has to render before anyone signs up.
     *
     * The free plan is included rather than filtered out. A comparison table
     * that starts at ₹1,999 with no "what you already have" column makes the
     * paid tiers look like the only option, and users bounce off it.
     */
    public function plans(): JsonResponse
    {
        $plans = MembershipPlan::query()->active()->get();

        return ApiResponse::success(
            PlanResource::collection($plans)->resolve(),
            'Membership plans retrieved successfully',
        );
    }

    /**
     * The header badge on every client. Returns a valid payload for a free
     * member too — MembershipResource renders "Free" from a null subscription,
     * so no client needs a special case for "has never paid".
     */
    public function current(Request $request): JsonResponse
    {
        $user = $request->user();

        return ApiResponse::success(
            new MembershipResource(
                $user->activeSubscription?->load('plan'),
                $this->features->features($user),
                $this->usageMap($user),
            ),
            'Membership retrieved successfully',
        );
    }

    /** The quota meters: used, limit and when the period rolls over. */
    public function usage(Request $request): JsonResponse
    {
        return ApiResponse::success($this->usageMap($request->user()), 'Usage retrieved successfully');
    }

    public function subscriptions(Request $request): JsonResponse
    {
        $subscriptions = $request->user()->subscriptions()
            ->with('plan:id,name,slug,price')
            ->latest('starts_at')
            ->paginate($request->integer('per_page', 20));

        return ApiResponse::paginated($subscriptions, SubscriptionResource::class, 'Subscriptions retrieved successfully');
    }

    // --- Internals ---------------------------------------------------------

    /**
     * Only quota features appear here. A capability is on or off and lives in
     * `features`; putting it in a meter would render a progress bar for
     * something that cannot be consumed.
     *
     * @return array<string, array<string, mixed>>
     */
    protected function usageMap(User $user): array
    {
        $usage = [];

        foreach (config('matrimony.membership.features') as $key => $schema) {
            if ($schema['type'] !== 'quota') {
                continue;
            }

            $limit = $this->features->limit($user, $key);
            $used = $this->features->used($user, $key);

            $usage[$key] = [
                'label' => $schema['label'],
                'used' => $used,
                'limit' => $limit,
                'unlimited' => $limit === -1,
                // Absent for an unlimited feature rather than sent as a
                // meaningless number — a gated field is absent, not null.
                'remaining' => $limit === -1 ? null : max(0, $limit - $used),
                'period' => $schema['period'],
            ];
        }

        return $usage;
    }
}
